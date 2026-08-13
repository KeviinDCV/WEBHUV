<?php

namespace Tests\Feature\Admin;

use App\Models\ContentMedia;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Documentos con varios archivos.
 *
 * El portal publica hasta veinticinco archivos en un mismo documento
 * —«Informes a organismos de inspección, vigilancia y control» de Rendición de
 * cuentas—. El importador se quedaba con el primero y tiraba el resto sin decir
 * nada: dieciocho archivos se habían perdido ya así en cinco temas antes de que
 * nadie lo notara.
 */
class ImportDocumentFilesTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://portal-anterior.example';

    /** @var list<array<string, mixed>> Lo que publica el portal falso ahora mismo. */
    private array $portalContents = [];

    /** @var list<string> Direcciones de archivo que el origen rechazará. */
    private array $rechazadas = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['huv.legacy_base' => self::BASE]);
        Storage::fake('public');
    }

    /**
     * @param  list<array{int, string}>  $files  Pares de identificador y nombre.
     */
    private function documento(array $files, array $replace = []): array
    {
        return $replace + [
            'contentID' => 7100,
            'friendlyName' => 'informes-a-organismos-de-inspeccion',
            'name' => 'Informes a organismos de inspección, vigilancia y control',
            'contentType' => 'Document',
            'body' => '<p>Índice de informes.</p>',
            'creationDate' => '2023/04/12 10:47:30',
            'modifiedDate' => '2026/08/03 15:29:23',
            'startingDate' => '2022/12/05 00:00:00',
            'published' => true,
            'labels' => [],
            'files' => array_map(fn (array $f) => [
                'fileID' => $f[0],
                'name' => $f[1],
                'filePath' => self::BASE.'/archivos/'.$f[1],
                'size' => 1000 + $f[0],
            ], $files),
        ];
    }

    private function fakePortal(array $contents): void
    {
        $this->portalContents = $contents;

        Http::fake([
            self::BASE.'/api/v1/tags/rendicion-de-cuentas' => fn () => Http::response([
                'defaultContentTemplate' => null,
            ]),

            self::BASE.'/api/v1/tags/*' => function (Request $request) {
                $page = (int) ($request->data()['page'] ?? 0);

                return Http::response([
                    'results' => $page === 0 ? [[
                        'tagID' => 5,
                        'name' => 'Rendición de cuentas',
                        'friendlyName' => 'rendicion-de-cuentas',
                        'validContentTypes' => ['Document'],
                        'templateType' => 'Default',
                    ]] : [],
                    'meta' => ['hasNextPage' => false],
                ]);
            },

            self::BASE.'/api/v1/contents?*' => fn () => Http::response([
                'results' => array_map(
                    fn (array $item) => ['friendlyName' => $item['friendlyName']],
                    $this->portalContents
                ),
                'meta' => ['hasNextPage' => false, 'totalCount' => count($this->portalContents)],
            ]),

            self::BASE.'/api/v1/contents/*' => function (Request $request) {
                $name = rawurldecode(Str::afterLast($request->url(), '/'));

                return Http::response(collect($this->portalContents)->firstWhere('friendlyName', $name));
            },

            // Un solo registro para los archivos, leyendo una propiedad mutable:
            // Http::fake acumula los dobles en vez de sustituirlos, así que
            // registrarlo dos veces no serviría para cambiar lo que responde.
            self::BASE.'/archivos/*' => function (Request $request) {
                $name = Str::afterLast($request->url(), '/');

                return in_array($name, $this->rechazadas, true)
                    ? Http::response('nada', 403)
                    : Http::response('contenido de prueba');
            },
        ]);
    }

    private function importar(): void
    {
        $this->artisan('huv:importar rendicion-de-cuentas')->assertSuccessful();
    }

    /* ------------------------------------------------------------------ */

    /**
     * La regresión que da nombre a este fichero: veinticinco archivos entran,
     * veinticinco archivos quedan.
     */
    public function test_un_documento_conserva_todos_sus_archivos(): void
    {
        $files = [];

        foreach (range(1, 25) as $n) {
            $files[] = [450 + $n, 'informe-'.$n.'.pdf'];
        }

        $this->fakePortal([$this->documento($files)]);
        $this->importar();

        $item = TopicItem::sole();

        // El primero sigue en las columnas propias: es el que da el icono y el
        // peso de la tarjeta del listado, como en el portal.
        $this->assertSame('informe-1.pdf', $item->file_name);
        $this->assertTrue($item->isDownloaded());

        // Y los otros veinticuatro existen de verdad, con su fichero en disco.
        $this->assertCount(24, $item->files());

        foreach ($item->files() as $file) {
            $this->assertTrue(Storage::disk('public')->exists($file->path));
        }

        // La ficha los publica todos juntos y en el orden del origen.
        $this->assertSame(
            array_map(fn (array $f) => $f[1], $files),
            $item->attachments()->pluck('name')->all()
        );
    }

    public function test_reimportar_no_duplica_ni_vuelve_a_pedir_los_archivos(): void
    {
        $this->fakePortal([$this->documento([[451, 'uno.pdf'], [452, 'dos.pdf'], [453, 'tres.pdf']])]);

        $this->importar();
        $this->importar();

        $item = TopicItem::sole();

        $this->assertCount(2, $item->files());
        $this->assertCount(3, $item->attachments());

        // Tres archivos, tres descargas: la segunda pasada no pidió ninguna.
        $descargas = collect(Http::recorded())
            ->filter(fn ($pair) => Str::contains($pair[0]->url(), '/archivos/'))
            ->count();

        $this->assertSame(3, $descargas);
    }

    /**
     * El origen puede sustituir un archivo conservando su identificador. Antes
     * de guardar la dirección de origen no había forma de notarlo y el adjunto
     * se quedaba congelado para siempre.
     */
    public function test_un_adjunto_sustituido_en_el_origen_se_vuelve_a_descargar(): void
    {
        $this->fakePortal([$this->documento([[451, 'principal.pdf'], [452, 'anexo.pdf']])]);
        $this->importar();

        $anterior = TopicItem::sole()->files()->first();

        // Mismo fileID, otro archivo detrás.
        $this->portalContents[0]['files'][1] = [
            'fileID' => 452,
            'name' => 'anexo-corregido.pdf',
            'filePath' => self::BASE.'/archivos/anexo-corregido.pdf',
            'size' => 9999,
        ];

        Artisan::call('huv:importar rendicion-de-cuentas');

        // Y el resumen lo cuenta: contar solo las altas hacía que dijera
        // «0 archivos» después de haber descargado y sustituido nueve.
        $this->assertMatchesRegularExpression(
            '/Archivos descargados\D+1\s/',
            preg_replace('/\e\[[\d;]*m/', '', Artisan::output()),
            'El resumen no cuenta el archivo que se volvió a descargar.'
        );

        $item = TopicItem::sole();
        $actual = $item->files()->first();

        $this->assertCount(1, $item->files(), 'Se duplicó el adjunto en vez de sustituirlo.');
        $this->assertSame('anexo-corregido.pdf', $actual->original_name);
        $this->assertNotSame($anterior->path, $actual->path);
        $this->assertFalse(
            Storage::disk('public')->exists($anterior->path),
            'El archivo viejo se quedó ocupando disco sin que nada lo enlace.'
        );
    }

    /** Y una reordenación en el origen tiene que llegar hasta la ficha. */
    public function test_un_adjunto_reordenado_en_el_origen_cambia_de_sitio(): void
    {
        $this->fakePortal([$this->documento([
            [451, 'principal.pdf'],
            [452, 'segundo.pdf'],
            [453, 'tercero.pdf'],
        ])]);

        $this->importar();

        $this->assertSame(
            ['principal.pdf', 'segundo.pdf', 'tercero.pdf'],
            TopicItem::sole()->attachments()->pluck('name')->all()
        );

        // El origen los intercambia sin cambiar los archivos.
        $files = $this->portalContents[0]['files'];
        [$files[1], $files[2]] = [$files[2], $files[1]];
        $this->portalContents[0]['files'] = $files;

        $this->importar();

        $item = TopicItem::sole()->fresh();
        $item->load('media');

        $this->assertSame(
            ['principal.pdf', 'tercero.pdf', 'segundo.pdf'],
            $item->attachments()->pluck('name')->all()
        );
    }

    /**
     * Un frenazo del origen a mitad de un documento no puede llevarse por
     * delante los adjuntos que ya estaban bien.
     */
    public function test_un_adjunto_que_falla_no_borra_los_que_si_estaban(): void
    {
        $this->fakePortal([$this->documento([
            [451, 'principal.pdf'],
            [452, 'segundo.pdf'],
            [453, 'tercero.pdf'],
        ])]);

        $this->importar();
        $this->assertCount(2, TopicItem::sole()->files());

        // Ahora el origen publica uno más, pero lo rechaza.
        $this->portalContents[0]['files'][] = [
            'fileID' => 454,
            'name' => 'cuarto.pdf',
            'filePath' => self::BASE.'/archivos/cuarto.pdf',
            'size' => 400,
        ];
        $this->rechazadas = ['cuarto.pdf'];

        $this->artisan('huv:importar rendicion-de-cuentas')
            ->expectsOutputToContain('faltan archivos')
            ->assertSuccessful();

        $item = TopicItem::sole()->fresh();
        $item->load('media');

        // Los dos de antes siguen ahí: podar con información incompleta los
        // habría borrado por no aparecer en la lista de esta pasada.
        $this->assertCount(2, $item->files());
        $this->assertSame(
            ['principal.pdf', 'segundo.pdf', 'tercero.pdf'],
            $item->attachments()->pluck('name')->all()
        );
    }

    /**
     * El recuento que impide que esto vuelva a pasar en silencio: el resumen
     * dice cuántos archivos publica el origen y cuántos han quedado aquí.
     */
    public function test_el_resumen_nombra_los_documentos_a_los_que_les_faltan_archivos(): void
    {
        $this->fakePortal([$this->documento([[451, 'principal.pdf'], [452, 'anexo.pdf']])]);
        $this->rechazadas = ['anexo.pdf'];

        $this->artisan('huv:importar rendicion-de-cuentas')
            ->expectsOutputToContain('informes-a-organismos-de-inspeccion (1 de 2)')
            ->assertSuccessful();
    }

    /** Cuando cuadran, no se dice nada. */
    public function test_un_documento_completo_no_aparece_en_el_aviso(): void
    {
        $this->fakePortal([$this->documento([[451, 'principal.pdf'], [452, 'anexo.pdf']])]);

        $this->artisan('huv:importar rendicion-de-cuentas')
            ->doesntExpectOutputToContain('faltan archivos')
            ->assertSuccessful();
    }

    /**
     * Si el origen retira el archivo, la ficha no puede seguir ofreciendo una
     * descarga cuyo nombre y peso ya nadie conoce.
     */
    public function test_un_documento_que_se_queda_sin_archivo_deja_de_ofrecerlo(): void
    {
        $this->fakePortal([$this->documento([[451, 'principal.pdf']])]);
        $this->importar();

        $anterior = TopicItem::sole()->file_path;
        $this->assertNotNull($anterior);

        $this->portalContents[0]['files'] = [];
        $this->importar();

        $item = TopicItem::sole()->fresh();

        $this->assertNull($item->file_path);
        $this->assertFalse($item->isDownloaded());
        $this->assertCount(0, $item->attachments());
        $this->assertFalse(Storage::disk('public')->exists($anterior));
    }

    /** Y un adjunto retirado en el origen sí deja de publicarse. */
    public function test_un_adjunto_retirado_en_el_origen_desaparece(): void
    {
        $this->fakePortal([$this->documento([
            [451, 'principal.pdf'],
            [452, 'segundo.pdf'],
            [453, 'tercero.pdf'],
        ])]);

        $this->importar();

        $retirado = TopicItem::sole()->files()->firstWhere('legacy_file_id', 453);

        array_pop($this->portalContents[0]['files']);
        $this->importar();

        $item = TopicItem::sole()->fresh();
        $item->load('media');

        $this->assertCount(1, $item->files());
        $this->assertSame(['principal.pdf', 'segundo.pdf'], $item->attachments()->pluck('name')->all());
        $this->assertFalse(Storage::disk('public')->exists($retirado->path));
        $this->assertSame(0, ContentMedia::whereKey($retirado->id)->count());
    }

    /**
     * El recuento mide lo que trajo la importación, no lo que añadió el editor.
     *
     * Sumarlo todo daba un «4 de 2» permanente: el aviso salía en cada pasada,
     * invitaba a reejecutar el comando —que revierte las correcciones hechas a
     * mano— y enterraba el aviso de verdad en un tema de cientos de documentos.
     */
    public function test_un_adjunto_puesto_a_mano_no_descuadra_el_recuento(): void
    {
        $this->fakePortal([$this->documento([[451, 'principal.pdf'], [452, 'anexo.pdf']])]);
        $this->importar();

        $item = TopicItem::sole();

        // Lo que añadiría alguien desde el editor: sin identificador de origen.
        $item->media()->create([
            'type' => ContentMedia::TYPE_FILE,
            'path' => 'temas/'.$item->topic_id.'/anexo-propio.pdf',
            'original_name' => 'anexo-propio.pdf',
            'alt' => 'Anexo propio',
            'size' => 1234,
            'position' => 9,
        ]);

        $this->artisan('huv:importar rendicion-de-cuentas')
            ->doesntExpectOutputToContain('faltan archivos')
            ->assertSuccessful();

        // Y sigue ahí: la importación respeta lo que no es suyo.
        $this->assertSame(1, $item->media()->whereNull('legacy_file_id')->count());
    }

    /** La ficha los publica en una sola lista, sin costura visible. */
    public function test_la_ficha_publica_todos_los_archivos_en_una_lista(): void
    {
        $this->fakePortal([$this->documento([
            [451, 'principal.pdf'],
            [452, 'segundo.pdf'],
            [453, 'tercero.xlsx'],
        ])]);

        $this->importar();

        $item = TopicItem::sole();

        $this->get(route('topics.items.show', [$item->topic, $item]))
            ->assertOk()
            ->assertSee('Archivos para descargar')
            ->assertSee('principal.pdf')
            ->assertSee('segundo.pdf')
            ->assertSee('tercero.xlsx')
            // Una sola sección, no una por cada procedencia.
            ->assertDontSee('Documentos adjuntos');
    }

    /**
     * Un documento sin archivo puede tener enlace, y no se descarga.
     *
     * «Decreto Único Reglamentario del Sector Salud» no sube el decreto: apunta
     * al PDF que publica MinSalud, y «Gaceta Departamental» a la página de la
     * gaceta. La importación se quedaba solo con `filePath` y tiraba ese
     * destino, así que las dos fichas no ofrecían nada. Y traérselo tampoco
     * vale: guardaría una página web haciéndose pasar por el documento.
     */
    public function test_un_documento_sin_archivo_guarda_su_enlace_y_no_lo_descarga(): void
    {
        $this->fakePortal([$this->documento([], [
            'friendlyName' => 'decreto-unico-reglamentario',
            'name' => 'Decreto Único Reglamentario del Sector Salud',
            'embedUrl' => 'https://www.minsalud.gov.co/rid/decreto-780-unico-modificado-2016.pdf',
        ])]);

        $this->importar();

        $item = TopicItem::sole();

        $this->assertSame(
            'https://www.minsalud.gov.co/rid/decreto-780-unico-modificado-2016.pdf',
            $item->source_url
        );
        $this->assertFalse($item->isDownloaded());
        $this->assertNull($item->file_name);

        // La extensión sale de la dirección, no de un valor por omisión.
        $this->assertSame('PDF', $item->extension());

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'minsalud'));
    }
}
