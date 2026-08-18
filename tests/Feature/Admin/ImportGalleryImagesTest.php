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
    private function articulo(array $files, ?string $portada = null, ?string $body = null): array
    {
        return [
            'contentID' => 8100,
            'friendlyName' => 'valores-y-principios-corporativos',
            'name' => 'Valores y Principios Corporativos',
            'contentType' => 'Article',
            'body' => $body ?? '<p>Principios corporativos.</p>',
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

    /* ------------------------------------------------------------------ */

    /** Un PNG de un píxel, para tener bytes que de verdad sean una imagen. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function conImagenPegada(string $datos = self::PNG, string $tipo = 'image/png'): array
    {
        return $this->articulo([], body: '<p>La casa de la humanización.</p>'
            .'<img src="data:'.$tipo.';base64,'.$datos.'">'
            .'<p>Sus cinco pilares.</p>');
    }

    /**
     * Una imagen pegada dentro del texto se rescata a la galería.
     *
     * El editor del portal deja soltar una imagen en el cuerpo, y entonces
     * viaja incrustada en el HTML como base64. El saneador la descarta —un
     * `src` con los datos dentro es la vía habitual de colar un SVG con
     * scripts—, y hasta aquí eso significaba perderla: el diagrama de la «Casa
     * de la Humanización» no es ninguna de las seis fotos adjuntas y no está en
     * ningún otro sitio del que recuperarlo.
     */
    public function test_una_imagen_pegada_en_el_texto_se_rescata_a_la_galeria(): void
    {
        $this->fakePortal([$this->conImagenPegada()]);

        // El aviso de imágenes perdidas es para las que enlazan a una
        // dirección; estas ya no se pierden.
        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('no se conservan')
            ->assertSuccessful();

        $item = TopicItem::sole();
        $foto = $item->images()->sole();

        $this->assertSame(ContentMedia::TYPE_IMAGE, $foto->type);
        $this->assertFalse((bool) $foto->is_main);
        $this->assertTrue(Storage::disk('public')->exists($foto->path));
        $this->assertSame(base64_decode(self::PNG), Storage::disk('public')->get($foto->path));

        // Y no se queda además dentro del cuerpo: son 50 KB de base64 al lado
        // de tres de texto, y el saneador los tira de todas formas.
        $this->assertStringNotContainsString('base64', (string) $item->body);
        $this->assertStringContainsString('Sus cinco pilares', (string) $item->body);

        $this->get(route('topics.items.show', [Topic::sole(), $item]))
            ->assertOk()
            ->assertSee('Galería')
            ->assertSee('<img src="'.e($foto->fileUrl()), false);
    }

    /**
     * Reimportar no cuelga otra copia de la misma imagen.
     *
     * El origen no le da identificador —no es un archivo suyo, es parte del
     * texto—, así que la identidad tiene que salir de su contenido. Sin eso,
     * cada pasada del comando añadiría el mismo diagrama a la galería.
     */
    public function test_reimportar_no_duplica_la_imagen_pegada_en_el_texto(): void
    {
        $this->fakePortal([$this->conImagenPegada()]);

        $this->artisan('huv:importar entidad')->assertSuccessful();
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $this->assertCount(1, TopicItem::sole()->images());
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    /**
     * El mismo PNG, engordado hasta el tamaño que se pida.
     *
     * Se le cuelga un bloque de texto —«tEXt», que el formato admite para
     * metadatos— justo antes del final. Sigue siendo un PNG válido y de un
     * píxel; lo único que cambia es lo que ocupa, que es de lo que va la
     * prueba.
     */
    private function pngDe(int $bytes): string
    {
        $png = base64_decode(self::PNG);
        $datos = "relleno\0".str_repeat('x', max(0, $bytes - strlen($png) - 20));
        $bloque = pack('N', strlen($datos)).'tEXt'.$datos.pack('N', crc32('tEXt'.$datos));

        // El chunk final —IEND, doce bytes— tiene que seguir siendo el último.
        return substr($png, 0, -12).$bloque.substr($png, -12);
    }

    /**
     * Una imagen pegada grande no se lleva por delante el resto del texto.
     *
     * Es el fallo de verdad, y era mudo. El analizador de HTML5 abandona el
     * documento cuando se topa con un atributo de decenas de miles de
     * caracteres, así que el cuerpo de «Humanización» —3.485 caracteres, con el
     * diagrama de 67 KB en mitad— entraba entero y salía con 1.071: se perdían
     * las tres líneas de acción, y lo que quedaba estaba bien formado y
     * terminaba en un punto, sin nada que delatara el corte.
     */
    public function test_una_imagen_pegada_grande_no_se_come_el_resto_del_texto(): void
    {
        // Por encima de los ~15 KB de atributo en que el analizador se rinde.
        $this->fakePortal([$this->conImagenPegada(base64_encode($this->pngDe(30_000)))]);

        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('han perdido parte del texto')
            ->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertStringContainsString('La casa de la humanización', (string) $item->body);
        $this->assertStringContainsString('Sus cinco pilares', (string) $item->body);

        // Y la imagen, que es lo que se estaba rescatando, sigue llegando.
        $this->assertCount(1, $item->images());
    }

    /**
     * Cuando de verdad se pierde texto, el resumen lo dice.
     *
     * Hermana de la comprobación de archivos que faltan: un cuerpo recortado se
     * parece en la base a uno corto, así que sin este recuento no hay forma de
     * enterarse. Aquí el saneador se lleva un pie de figura entero —descarta
     * las etiquetas que no conoce junto con lo que envuelven—, que es
     * exactamente la clase de merma que hay que ver.
     */
    public function test_el_resumen_avisa_cuando_el_cuerpo_pierde_texto(): void
    {
        $perdido = str_repeat('Texto que el saneador se lleva por delante. ', 20);

        $this->fakePortal([$this->articulo([], body: '<p>Un párrafo corto.</p>'
            .'<figure><figcaption>'.$perdido.'</figcaption></figure>')]);

        $this->artisan('huv:importar entidad')
            ->expectsOutputToContain('han perdido parte del texto')
            ->assertSuccessful();

        $this->assertStringNotContainsString('se lleva por delante', (string) TopicItem::sole()->body);
    }

    /**
     * Cambiar la imagen pegada la sustituye; quitarla la retira.
     *
     * La identidad de una imagen rescatada es su contenido, así que la de ayer
     * y la de hoy son dos filas distintas. Como no tienen identificador de
     * origen —no son un archivo del portal, son parte del texto—, la poda las
     * dejaba pasar: retocar el diagrama de un artículo dejaba aquí los dos, y
     * quitarlo del cuerpo no lo quitaba de la galería. Reimportar no lo
     * arreglaba nunca.
     */
    public function test_al_cambiar_la_imagen_pegada_la_anterior_se_retira(): void
    {
        $this->fakePortal([$this->conImagenPegada()]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $primera = TopicItem::sole()->images()->sole();

        // El portal retoca el diagrama: mismos <p> alrededor, otros bytes.
        $this->fakePortal([$this->conImagenPegada(base64_encode($this->pngDe(400)))]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $segunda = TopicItem::sole()->images()->sole();

        $this->assertNotSame($primera->id, $segunda->id);
        $this->assertFalse(Storage::disk('public')->exists($primera->path));
        $this->assertTrue(Storage::disk('public')->exists($segunda->path));

        // Y al desaparecer del cuerpo, desaparece de la galería.
        $this->fakePortal([$this->articulo([], body: '<p>Ya sin diagrama.</p>')]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $this->assertCount(0, TopicItem::sole()->images());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * Lo que añade quien edita no lo toca la importación.
     *
     * Es la otra mitad de la poda: se reconoce lo suyo por el identificador de
     * origen o por la marca de las incrustadas, y todo lo demás se respeta.
     */
    public function test_la_poda_respeta_las_fotos_anadidas_desde_el_editor(): void
    {
        $this->fakePortal([$this->conImagenPegada()]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $aMano = TopicItem::sole()->media()->create([
            'type' => ContentMedia::TYPE_IMAGE,
            'path' => 'temas/1/foto-de-la-jornada.jpg',
            'alt' => 'Foto de la jornada',
            'position' => 9,
        ]);

        $this->fakePortal([$this->articulo([], body: '<p>Ya sin diagrama.</p>')]);
        $this->artisan('huv:importar entidad')->assertSuccessful();

        $this->assertNotNull($aMano->fresh());
        $this->assertCount(1, TopicItem::sole()->images());
    }

    /**
     * El aviso de texto perdido no cuenta como texto lo que nunca lo fue.
     *
     * `strip_tags()` quita la etiqueta <style> y deja dentro el CSS; el
     * saneador descarta el bloque entero, y hace bien. Midiendo así, un cuerpo
     * pegado desde Word —que arrastra hojas de estilo de Mso más largas que el
     * párrafo que acompañan— salía listado en rojo como si hubiera perdido
     * media página teniéndola entera. Un aviso que grita en falso deja de
     * mirarse, y este existe justo para que se mire.
     */
    public function test_el_aviso_no_confunde_una_hoja_de_estilos_con_texto(): void
    {
        $estilos = '<style>'.str_repeat('p.MsoNormal{margin:0cm;font-size:11.0pt;font-family:"Calibri",sans-serif;}', 6).'</style>';

        $this->fakePortal([$this->articulo([], body: $estilos.'<p>El texto llega entero, con sus estilos de Word delante.</p>')]);

        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('han perdido parte del texto')
            ->assertSuccessful();

        $this->assertStringContainsString('llega entero', (string) TopicItem::sole()->body);
    }

    /**
     * Una portada en ruta relativa tambien se trae.
     *
     * El portal las publica absolutas casi siempre, pero la de «HUV e-Learn»
     * llega como «/sites/hospital-universitario-.../422_huv_learn_200x200.jpg»,
     * sin esquema ni servidor. El cliente HTTP la rechazaba —«URI must include
     * a scheme and host»—, el contenido entraba sin imagen y la importacion
     * terminaba con «1 con problemas». Barriendo los 2.369 contenidos del
     * portal es el unico caso, pero es el unico que hay que atender.
     */
    public function test_una_portada_en_ruta_relativa_tambien_se_trae(): void
    {
        $this->fakePortal([$this->articulo([], portada: '/archivos/portada-relativa.jpg')]);

        $this->artisan('huv:importar entidad')
            ->doesntExpectOutputToContain('con problemas')
            ->assertSuccessful();

        $portada = TopicItem::sole()->images()->sole();

        $this->assertTrue((bool) $portada->is_main);
        $this->assertTrue(Storage::disk('public')->exists($portada->path));

        // Y se guarda de donde vino, ya completa: si quedara relativa, la
        // siguiente pasada no sabria reconocer que es la misma.
        $this->assertSame(self::BASE.'/archivos/portada-relativa.jpg', $portada->source_url);
    }

    /**
     * El tipo declarado no basta: se miran los bytes.
     *
     * Lo que va entre `data:` y `;base64` lo escribe quien publica en el portal
     * anterior. Fiarse de él convertiría esto en una puerta para dejar
     * cualquier cosa en el disco que sirve el servidor web con solo llamarla
     * «image/png».
     */
    public function test_lo_que_no_es_una_imagen_no_entra_aunque_el_tipo_lo_diga(): void
    {
        $this->fakePortal([$this->conImagenPegada(base64_encode('<?php echo "hola";'))]);

        $this->artisan('huv:importar entidad')
            ->expectsOutputToContain('no es una imagen que se admita')
            ->assertSuccessful();

        $this->assertCount(0, TopicItem::sole()->images());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
