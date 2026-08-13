<?php

namespace Tests\Feature\Admin;

use App\Models\ContentMedia;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Adjuntos que en realidad son fotos.
 *
 * El portal mete fotos y documentos en la misma lista de archivos y los separa
 * con `isImage`: las primeras van al carrusel de la ficha y los segundos a las
 * descargas. El importador no miraba ese campo y lo guardaba todo como
 * descarga, así que las siete láminas de «Valores y Principios Corporativos»
 * salían como siete filas de «JPG · 856 Kb» en vez de como las imágenes que
 * son. Pasaba lo mismo en diecisiete fotos de ocho noticias.
 */
class ImportGalleryImagesTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://portal-anterior.example';

    /** @var list<array<string, mixed>> */
    private array $portalContents = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['huv.legacy_base' => self::BASE]);
        Storage::fake('public');
    }

    /**
     * @param  list<array{int, string, bool}>  $files  Identificador, nombre y si es imagen.
     */
    private function articulo(array $files, ?string $portada = null): array
    {
        return [
            'contentID' => 8100,
            'friendlyName' => 'valores-y-principios-corporativos',
            'name' => 'Valores y Principios Corporativos',
            'contentType' => 'Article',
            'body' => '<p>Principios corporativos.</p>',
            'creationDate' => '2023/06/27 16:05:21',
            'modifiedDate' => '2024/07/01 20:05:10',
            'published' => true,
            'labels' => [],
            'fileID' => 1043,
            'defaultImage' => $portada,
            'files' => array_map(fn (array $f) => [
                'fileID' => $f[0],
                'name' => $f[1],
                'filePath' => self::BASE.'/archivos/'.$f[1],
                'isImage' => $f[2],
                'size' => 1000 + $f[0],
            ], $files),
        ];
    }

    private function fakePortal(array $contents): void
    {
        $this->portalContents = $contents;

        Http::fake([
            self::BASE.'/api/v1/tags/entidad' => fn () => Http::response(['defaultContentTemplate' => null]),

            self::BASE.'/api/v1/tags/*' => function (Request $request) {
                $page = (int) ($request->data()['page'] ?? 0);

                return Http::response([
                    'results' => $page === 0 ? [[
                        'tagID' => 2,
                        'name' => 'Entidad',
                        'friendlyName' => 'entidad',
                        'validContentTypes' => ['Article'],
                        'templateType' => 'Sortable',
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

            self::BASE.'/archivos/*' => fn () => Http::response('contenido de prueba'),
            self::BASE.'/portada.jpg' => fn () => Http::response('portada de prueba'),
        ]);
    }

    /* ------------------------------------------------------------------ */

    public function test_una_foto_adjunta_va_a_la_galeria_y_un_pdf_a_las_descargas(): void
    {
        $this->fakePortal([$this->articulo([
            [335, 'valores-respeto.jpg', true],
            [25, 'acuerdo-003-2015.pdf', false],
        ])]);

        $this->artisan('huv:importar entidad')->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertCount(1, $item->images());
        $this->assertCount(1, $item->files());

        $foto = $item->images()->sole();
        $this->assertSame(ContentMedia::TYPE_IMAGE, $foto->type);
        $this->assertSame('valores-respeto.jpg', $foto->original_name);

        // El nombre del fichero no es texto alternativo: describe el archivo,
        // no lo que se ve. Se deja vacío para que quien edita lo escriba, y el
        // comando lo avisa.
        $this->assertNull($foto->alt);

        $pdf = $item->files()->sole();
        $this->assertSame(ContentMedia::TYPE_FILE, $pdf->type);
        $this->assertSame('acuerdo-003-2015.pdf', $pdf->alt);
    }

    public function test_la_ficha_publica_la_foto_como_imagen_y_no_como_descarga(): void
    {
        // Con portada, como el artículo real: sin ella la única foto sería la
        // principal y no habría galería que comprobar.
        $this->fakePortal([$this->articulo(
            [[335, 'valores-respeto.jpg', true]],
            portada: self::BASE.'/portada.jpg'
        )]);

        $this->artisan('huv:importar entidad')->assertSuccessful();

        $item = TopicItem::sole();
        $topic = Topic::sole();

        $respuesta = $this->get(route('topics.items.show', [$topic, $item]))->assertOk();

        $respuesta->assertSee('Galería');
        $respuesta->assertDontSee('Archivos para descargar');

        // La prueba que de verdad discrimina: la foto se sirve dentro de un
        // <img>, no dentro de un enlace de descarga con su peso al lado.
        $foto = $item->images()->firstWhere('original_name', 'valores-respeto.jpg');
        $ruta = $foto->fileUrl();

        $respuesta->assertSee('<img src="'.e($ruta), false);
        $respuesta->assertDontSee('<a href="'.e($ruta).'" download', false);
    }

    /**
     * Sin portada no hay portada.
     *
     * Es el perfil de «Valores y Principios Corporativos»: siete láminas y
     * ninguna imagen de cabecera. Ascender la primera a principal la sacaba de
     * la galería —se pintaba arriba, sin enlace para ampliarla y anunciada como
     * decorativa—, la convertía en la miniatura de la tarjeta y descuadraba el
     * recuento de integridad, que avisaba de «6 de 7» en cada pasada teniendo
     * las siete.
     */
    public function test_sin_portada_ninguna_foto_se_asciende_a_principal(): void
    {
        $files = [];

        foreach (range(1, 7) as $n) {
            $files[] = [400 + $n, 'lamina-'.$n.'.jpg', true];
        }

        $this->fakePortal([$this->articulo($files)]);

        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('les faltan archivos')
            ->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertCount(7, $item->images());
        $this->assertNull($item->images()->firstWhere('is_main', true));

        // Las siete en la galería, las siete con su enlace para ampliarlas.
        $respuesta = $this->get(route('topics.items.show', [Topic::sole(), $item]))->assertOk();

        foreach ($item->images() as $foto) {
            $respuesta->assertSee('<a href="'.e($foto->fileUrl()).'" class="block">', false);
        }

        // La tarjeta del listado sí necesita una miniatura: ahí mainImage()
        // sigue echando mano de la primera que haya.
        $this->assertSame($item->images()->first()->fileUrl(), $item->imageUrl());
    }

    /**
     * Al reclasificar, el nombre del fichero deja de hacer de descripción.
     *
     * Los adjuntos importados antes de mirar `isImage` se guardaron como
     * descarga y con el nombre del fichero por rótulo. Al volverse foto ese
     * texto pasa a ser el alternativo y el pie de la imagen, y
     * «valores-respeto.jpg» no describe nada a quien no la ve (WCAG 1.1.1).
     */
    public function test_al_pasar_de_descarga_a_foto_se_limpia_el_nombre_del_fichero(): void
    {
        // Primera pasada: el origen todavía no la marca como imagen.
        $this->fakePortal([$this->articulo([[335, 'valores-respeto.jpg', false]])]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $this->assertSame('valores-respeto.jpg', TopicItem::sole()->files()->sole()->alt);

        // Segunda: ya la marca.
        $this->fakePortal([$this->articulo([[335, 'valores-respeto.jpg', true]])]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $foto = TopicItem::sole()->images()->sole();

        $this->assertSame(ContentMedia::TYPE_IMAGE, $foto->type);
        $this->assertNull($foto->alt);
        $this->assertSame('valores-respeto.jpg', $foto->original_name);
    }

    /**
     * Reimportar no borra la descripción que alguien escribió.
     *
     * El origen manda las fotos sin texto alternativo, y el resumen de la
     * importación pide escribirlo a mano. Si la siguiente pasada lo sobrescribe
     * con el vacío que manda el origen, ese aviso pide una y otra vez un
     * trabajo que el propio comando deshace.
     */
    public function test_reimportar_conserva_el_texto_alternativo_escrito_a_mano(): void
    {
        $this->fakePortal([$this->articulo(
            [[335, 'valores-respeto.jpg', true]],
            portada: self::BASE.'/portada.jpg'
        )]);

        $this->artisan('huv:importar entidad')->assertSuccessful();

        $foto = TopicItem::sole()->images()->firstWhere('original_name', 'valores-respeto.jpg');
        $foto->update(['alt' => 'Lámina del valor Respeto']);

        $this->artisan('huv:importar entidad')->assertSuccessful();

        $this->assertSame('Lámina del valor Respeto', $foto->fresh()->alt);
    }

    /**
     * La ficha de un documento también enseña sus fotos.
     *
     * Se arma con modelos y no importando: la rama de documento de
     * `topics/item` solo pinta descargas —la de artículo va por otro camino—, y
     * hasta ahora una foto adjunta a un documento no aparecía en ninguna de las
     * dos listas y se perdía de vista sin borrarse de la base.
     */
    public function test_la_ficha_de_un_documento_tambien_ensena_sus_fotos(): void
    {
        $topic = Topic::create([
            'name' => 'Rendición de cuentas',
            'slug' => 'rendicion-de-cuentas',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe de gestión',
            'slug' => 'informe-de-gestion',
            'published_at' => now(),
            'file_path' => 'documentos/1/informe.pdf',
            'file_name' => 'informe.pdf',
            'file_extension' => 'pdf',
        ]);

        // Marcada como principal a propósito: es lo que dejaban las
        // importaciones antiguas cuando la foto era la única imagen. La ficha
        // de un documento no tiene bloque de portada donde enseñarla, así que
        // apartarla por esa marca la dejaba sin salir por ningún lado.
        $foto = $item->media()->create([
            'legacy_file_id' => 900,
            'type' => ContentMedia::TYPE_IMAGE,
            'is_main' => true,
            'position' => 1,
            'original_name' => 'grafica-de-ejecucion.jpg',
            'path' => 'temas/1/grafica-de-ejecucion.jpg',
        ]);

        $this->get(route('topics.items.show', [$topic, $item]))
            ->assertOk()
            ->assertSee('Galería')
            ->assertSee('<img src="'.e($foto->fileUrl()), false);
    }

    /**
     * El recuento de integridad cuenta los dos tipos.
     *
     * Cuenta los archivos que publica el origen contra los que han quedado
     * aquí. Mirando solo las descargas, un artículo con siete fotos avisaría de
     * «0 de 7» archivos perdidos en cada pasada teniéndolas todas, y ese aviso
     * es el que tiene que seguir sirviendo cuando de verdad se pierda algo.
     */
    public function test_las_fotos_cuentan_para_la_comprobacion_de_integridad(): void
    {
        $this->fakePortal([$this->articulo([
            [335, 'una.jpg', true],
            [336, 'otra.jpg', true],
            [25, 'acuerdo.pdf', false],
        ], portada: self::BASE.'/portada.jpg')]);

        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('les faltan archivos')
            ->assertSuccessful();

        $item = TopicItem::sole();

        // La portada no viene de `files`: son dos fotos de galería más ella.
        $this->assertCount(3, $item->images());
        $this->assertTrue($item->mainImage()->is_main);
        $this->assertCount(1, $item->files());
    }
}
