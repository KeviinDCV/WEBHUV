<?php

namespace Tests\Feature\Admin;

use App\Models\Topic;
use App\Support\CommentWall;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportTopicTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://portal-anterior.example';

    /** @var list<array<string, mixed>> Lo que publica el portal falso ahora mismo. */
    private array $portalContents = [];

    /** Cuántos 403 seguidos devolverá el portal falso antes de responder. */
    private int $rechazosPendientes = 0;

    private int $intentosDeDetalle = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['huv.legacy_base' => self::BASE]);
        Storage::fake('public');
    }

    /**
     * Simula la API del portal anterior.
     *
     * Lo importante es que las páginas empiezan en CERO, como en el portal real:
     * pedir la página 1 devuelve la segunda tanda y la primera se pierde sin dar
     * ningún error.
     *
     * @param  int  $total  Documentos que declara el origen.
     */
    private function fakePortal(int $total = 25, int $rechazos = 0): void
    {
        $this->rechazosPendientes = $rechazos;

        $pageSize = 20;

        $documento = fn (int $n): array => [
            'contentID' => 1000 + $n,
            'friendlyName' => 'documento-'.$n,
            'name' => 'Documento número '.$n,
        ];

        Http::fake([
            self::BASE.'/api/v1/tags/*' => function (Request $request) {
                $page = (int) $request->data()['page'];

                return Http::response([
                    'results' => $page === 0 ? [[
                        'tagID' => 27,
                        'name' => 'Presupuesto',
                        'friendlyName' => 'presupuesto',
                        'description' => 'Así organizamos nuestras finanzas.',
                    ]] : [],
                    'meta' => ['hasNextPage' => $page === 0],
                ]);
            },

            self::BASE.'/api/v1/contents?*' => function (Request $request) use ($total, $pageSize, $documento) {
                $page = (int) $request->data()['page'];
                $from = $page * $pageSize;

                return Http::response([
                    'results' => collect(range(1, $total))
                        ->slice($from, $pageSize)
                        ->map($documento)
                        ->values()
                        ->all(),
                    'meta' => [
                        'hasNextPage' => $from + $pageSize < $total,
                        'totalCount' => $total,
                    ],
                ]);
            },

            self::BASE.'/api/v1/contents/documento-*' => function (Request $request) {
                // El portal de origen frena con un 403 cuando se le piden
                // cientos de contenidos seguidos.
                if ($this->rechazosPendientes > 0) {
                    $this->rechazosPendientes--;
                    $this->intentosDeDetalle++;

                    return Http::response('Forbidden', 403);
                }

                $this->intentosDeDetalle++;
                $n = (int) Str::afterLast($request->url(), '-');

                return Http::response([
                    'contentID' => 1000 + $n,
                    'friendlyName' => 'documento-'.$n,
                    'name' => 'Documento número '.$n,
                    'body' => '<p>Cuerpo del documento '.$n.'</p>',
                    'startingDate' => '2026/01/15 08:00:00',
                    'creationDate' => '2026/02/01 10:00:00',
                    'modifiedDate' => '2026/02/02 11:00:00',
                    'published' => true,
                    'isFeatured' => false,
                    'labels' => [[
                        'labelID' => 62,
                        'name' => 'Ejecución Presupuestal 2026',
                        'friendlyName' => '2026-1',
                    ]],
                    'files' => [[
                        'fileID' => $n,
                        'name' => 'documento-'.$n.'.pdf',
                        'filePath' => self::BASE.'/archivos/documento-'.$n.'.pdf',
                        'size' => 2048,
                    ]],
                ]);
            },

            // Con una función y no con una respuesta fija: Http::fake reutiliza la
            // misma instancia, y el cuerpo se consume en la primera descarga.
            self::BASE.'/archivos/*' => fn () => Http::response('%PDF-1.4 contenido de prueba'),
        ]);
    }

    /**
     * Regresión: la API pagina desde cero. Empezar en la página 1 importaba todo
     * menos los veinte primeros, y sin síntoma visible.
     */
    public function test_se_importan_tambien_los_de_la_primera_pagina(): void
    {
        $this->fakePortal(total: 25);

        $this->artisan('huv:importar presupuesto')->assertSuccessful();

        $this->assertSame(25, TopicItem::count());
        $this->assertNotNull(TopicItem::where('legacy_content_id', 1001)->first());
    }

    public function test_se_guardan_los_datos_y_el_archivo(): void
    {
        $this->fakePortal(total: 3);

        $this->artisan('huv:importar presupuesto')->assertSuccessful();

        $topic = Topic::sole();
        $document = TopicItem::where('legacy_content_id', 1001)->sole();

        $this->assertSame('presupuesto', $topic->slug);
        $this->assertNotNull($topic->imported_at);

        $this->assertSame('Documento número 1', $document->title);
        $this->assertSame('2026-01-15', $document->issued_at->format('Y-m-d'));
        $this->assertSame('2026-02-01', $document->published_at->format('Y-m-d'));
        $this->assertSame('2026-02-02', $document->modified_at->format('Y-m-d'));
        $this->assertSame('pdf', $document->file_extension);
        // Sin `validContentTypes` en el origen, el tema se toma por documental:
        // es lo que había antes de admitir artículos.
        $this->assertSame(TopicItem::KIND_DOCUMENT, $document->kind);
        $this->assertSame('Ejecución Presupuestal 2026', $document->categories->first()->name);
        Storage::disk('public')->assertExists($document->file_path);
    }

    /** Volver a ejecutarlo actualiza; no duplica ni vuelve a descargar. */
    public function test_la_importacion_es_idempotente(): void
    {
        $this->fakePortal(total: 3);

        $this->artisan('huv:importar presupuesto')->assertSuccessful();
        $path = TopicItem::where('legacy_content_id', 1001)->sole()->file_path;

        $this->artisan('huv:importar presupuesto')->assertSuccessful();

        $this->assertSame(3, TopicItem::count());
        $this->assertSame(1, Topic::count());
        $this->assertSame($path, TopicItem::where('legacy_content_id', 1001)->sole()->file_path);
    }

    /**
     * El portal de origen devuelve 403 cuando se le piden cientos de contenidos
     * seguidos: es un freno, no un permiso denegado. Sin reintentar con espera
     * creciente, una importación grande termina con fallos que no lo son.
     */
    public function test_un_frenazo_del_origen_se_reintenta_en_vez_de_darse_por_perdido(): void
    {
        $this->fakePortal(total: 1, rechazos: 2);

        $this->artisan('huv:importar presupuesto')
            ->doesntExpectOutputToContain('con problemas')
            ->assertSuccessful();

        // Dos rechazos y la buena: el contenido entra igual.
        $this->assertSame(3, $this->intentosDeDetalle);
        $this->assertSame(1, TopicItem::count());
    }

    public function test_un_tema_inexistente_falla_sin_crear_nada(): void
    {
        $this->fakePortal();

        $this->artisan('huv:importar inventado')->assertFailed();

        $this->assertSame(0, Topic::count());
    }

    /* ------------------------------------------------------------------ */
    /* Temas de artículos y temas mixtos                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Portal falso de un tema de artículos, con lo que distingue a estos de los
     * documentos: imagen principal, varios adjuntos, varias categorías, muro de
     * participación y la fecha imposible con la que el origen dice «sin fin».
     *
     * @param  list<array<string, mixed>>  $extra  Contenidos añadidos al listado.
     */
    private function fakeArticlePortal(array $extra = [], array $replace = []): void
    {
        $article = $replace + [
            'contentID' => 5199,
            'friendlyName' => 'programa-y-plan-ptee-huv',
            'name' => 'Programa de Transparencia y Ética Empresarial- PTEE',
            'contentType' => 'Article',
            'body' => '<div><p>Resumen <b>del</b> programa</p></div>',
            'creationDate' => '2026/01/20 11:32:00',
            'modifiedDate' => '2026/02/02 09:00:00',
            'closingDate' => '2038/01/01 00:00:00',
            'commentWallType' => 0,
            'showOnHome' => true,
            'displayOrder' => 5199,
            'published' => true,
            'isFeatured' => false,
            'fileID' => 6847,
            'defaultImage' => self::BASE.'/archivos/ptee_1024x600.jpeg',
            'labels' => [
                ['labelID' => 149, 'name' => 'Programa PTEE'],
                ['labelID' => 150, 'name' => '2025'],
            ],
            'files' => [
                ['fileID' => 3210, 'name' => 'programa.pdf', 'filePath' => self::BASE.'/archivos/programa.pdf', 'size' => 100],
                ['fileID' => 3211, 'name' => 'encuesta.pdf', 'filePath' => self::BASE.'/archivos/encuesta.pdf', 'size' => 200],
                ['fileID' => 3212, 'name' => 'plan.xlsx', 'filePath' => self::BASE.'/archivos/plan.xlsx', 'size' => 300],
                ['fileID' => 3213, 'name' => 'cierre.pdf', 'filePath' => self::BASE.'/archivos/cierre.pdf', 'size' => 400],
            ],
        ];

        // Los contenidos viven en una propiedad y no en una variable capturada:
        // así se puede cambiar lo que publica el origen sin volver a registrar
        // los dobles, que Http::fake acumula en lugar de sustituir.
        $this->portalContents = array_merge([$article], $extra);

        Http::fake([
            self::BASE.'/api/v1/tags/programas' => fn () => Http::response([
                'defaultContentTemplate' => '{"template": "<p>Resumen del programa:</p>"}',
            ]),

            self::BASE.'/api/v1/tags/*' => function (Request $request) {
                $page = (int) ($request->data()['page'] ?? 0);

                return Http::response([
                    'results' => $page === 0 ? [[
                        'tagID' => 29,
                        'name' => 'Programas',
                        'friendlyName' => 'programas',
                        'validContentTypes' => ['Article', 'Document'],
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
        ]);
    }

    /** Cambia lo que publica el origen para el contenido ya registrado. */
    private function cambiarOrigen(array $replace): void
    {
        $this->portalContents[0] = $replace + $this->portalContents[0];
    }

    public function test_se_importa_un_tema_de_articulos(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertSame(TopicItem::KIND_ARTICLE, $item->kind);
        $this->assertSame(CommentWall::PUBLICA, $item->comment_wall);
        $this->assertTrue($item->legacy_show_on_home);
        $this->assertSame(['2025', 'Programa PTEE'], $item->categories->pluck('name')->all());
        $this->assertSame('2026-01-20', $item->published_at->format('Y-m-d'));
        $this->assertSame('2026-02-02', $item->modified_at->format('Y-m-d'));
    }

    /**
     * El origen guarda «sin fecha final» como el año 2038. Importarla dejaría el
     * contenido con una caducidad que nadie puso.
     */
    public function test_la_fecha_final_imposible_no_se_importa(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $this->assertNull(TopicItem::sole()->expires_at);
    }

    /**
     * Regresión: quedarse con el primer archivo perdía tres de los cuatro
     * adjuntos de este contenido.
     */
    public function test_se_guardan_todos_los_adjuntos_y_la_imagen(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertCount(4, $item->files());
        $this->assertNotNull($item->mainImage());
        $this->assertSame(5, $item->media()->count());
        Storage::disk('public')->assertExists($item->mainImage()->path);
    }

    public function test_reimportar_no_duplica_ni_vuelve_a_descargar(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();
        $paths = TopicItem::sole()->media()->pluck('path')->sort()->values()->all();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $this->assertSame(1, TopicItem::count());
        $this->assertSame(5, TopicItem::sole()->media()->count());
        $this->assertSame($paths, TopicItem::sole()->media()->pluck('path')->sort()->values()->all());
    }

    /**
     * El cuerpo llega con etiquetas que el saneador no admite. Sin normalizarlo
     * antes, se perdería entero y sin avisar.
     */
    public function test_el_cuerpo_sobrevive_a_las_etiquetas_del_portal_anterior(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $body = TopicItem::sole()->body;

        $this->assertStringContainsString('Resumen', $body);
        $this->assertStringContainsString('<strong>del</strong>', $body);
        $this->assertStringNotContainsString('<div', $body);
    }

    public function test_se_guarda_la_plantilla_del_tema(): void
    {
        $this->fakeArticlePortal();

        $this->artisan('huv:importar programas')->assertSuccessful();

        $this->assertSame('<p>Resumen del programa:</p>', Topic::sole()->content_template);
    }

    /** Un tema mixto guarda cada contenido con el tipo que le corresponde. */
    public function test_un_tema_mixto_reparte_los_tipos(): void
    {
        $this->fakeArticlePortal([[
            'contentID' => 6000,
            'friendlyName' => 'informe-de-gestion',
            'name' => 'Informe de gestión',
            'contentType' => 'Document',
            'startingDate' => '2026/03/01 08:00:00',
            'creationDate' => '2026/03/02 08:00:00',
            'published' => true,
            'labels' => [],
            'files' => [[
                'fileID' => 7000,
                'name' => 'informe.pdf',
                'filePath' => self::BASE.'/archivos/informe.pdf',
                'size' => 500,
            ]],
        ]]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $this->assertSame(2, TopicItem::count());

        $document = TopicItem::where('legacy_content_id', 6000)->sole();

        $this->assertSame(TopicItem::KIND_DOCUMENT, $document->kind);
        $this->assertSame('2026-03-01', $document->issued_at->format('Y-m-d'));
        $this->assertSame('pdf', $document->file_extension);
        Storage::disk('public')->assertExists($document->file_path);
    }

    /**
     * Lo que se descarga acaba bajo el directorio que sirve el servidor web.
     *
     * Un «.php» ahí dentro es ejecución de código, y un «.html» o un «.svg», un
     * XSS con el origen del portal. La extensión la pone el portal de origen,
     * así que no puede pasar tal cual.
     */
    public function test_una_extension_peligrosa_no_se_guarda_tal_cual(): void
    {
        $this->fakeArticlePortal([[
            'contentID' => 6200,
            'friendlyName' => 'nota-con-trampa',
            'name' => 'Nota con trampa',
            'contentType' => 'Document',
            'creationDate' => '2026/03/02 08:00:00',
            'published' => true,
            'labels' => [],
            'files' => [[
                'fileID' => 7100,
                'name' => 'nota.php',
                'filePath' => self::BASE.'/archivos/nota.php',
                'size' => 100,
            ]],
        ]]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::where('legacy_content_id', 6200)->sole();

        $this->assertStringEndsWith('.bin', $item->file_path);
        $this->assertStringNotContainsString('.php', $item->file_path);
    }

    /**
     * Si el origen sustituye el archivo de un documento, los metadatos se
     * refrescan; el archivo tiene que refrescarse con ellos, o la ficha anuncia
     * uno y entrega otro.
     */
    public function test_un_archivo_sustituido_en_el_origen_se_vuelve_a_descargar(): void
    {
        $this->fakeArticlePortal(replace: [
            'contentType' => 'Document',
            'defaultImage' => null,
            'startingDate' => '2026/03/01 08:00:00',
            'files' => [[
                'fileID' => 4001,
                'name' => 'ejecucion-marzo.pdf',
                'filePath' => self::BASE.'/archivos/ejecucion-marzo.pdf',
                'size' => 100,
            ]],
        ]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $anterior = TopicItem::sole()->file_path;

        // El hospital sustituye el PDF por una hoja corregida.
        $this->cambiarOrigen(['files' => [[
            'fileID' => 4002,
            'name' => 'ejecucion-marzo-corregida.xlsx',
            'filePath' => self::BASE.'/archivos/ejecucion-marzo-corregida.xlsx',
            'size' => 999,
        ]]]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertNotSame($anterior, $item->file_path);
        $this->assertStringEndsWith('.xlsx', $item->file_path);
        $this->assertSame('xlsx', $item->file_extension);
        // El anterior no se queda ocupando disco.
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($item->file_path);
    }

    /**
     * Los medios que el origen retira o sustituye tienen que irse: si no, un
     * adjunto retirado sigue descargable y el artículo acaba con dos imágenes
     * marcadas como principal.
     */
    public function test_los_medios_retirados_en_el_origen_dejan_de_publicarse(): void
    {
        $this->fakeArticlePortal();
        $this->artisan('huv:importar programas')->assertSuccessful();

        $this->assertSame(5, TopicItem::sole()->media()->count());

        // El origen sustituye la imagen y deja un solo adjunto.
        $this->cambiarOrigen([
            'fileID' => 9001,
            'defaultImage' => self::BASE.'/archivos/ptee-nueva_1024x600.jpeg',
            'files' => [[
                'fileID' => 3210,
                'name' => 'programa.pdf',
                'filePath' => self::BASE.'/archivos/programa.pdf',
                'size' => 100,
            ]],
        ]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::sole()->fresh('media');

        $this->assertSame(2, $item->media()->count());
        $this->assertCount(1, $item->images());
        $this->assertCount(1, $item->files());
        $this->assertSame(1, $item->media()->where('is_main', true)->count());
        $this->assertSame(9001, $item->mainImage()->legacy_file_id);
    }

    /**
     * Sin identificador de origen, buscar «legacy_file_id = null» emparejaba
     * entre sí todos los medios y los colapsaba en uno solo.
     */
    public function test_los_medios_sin_identificador_no_se_colapsan(): void
    {
        $this->fakeArticlePortal(replace: [
            'fileID' => null,
            'files' => [
                ['name' => 'uno.pdf', 'filePath' => self::BASE.'/archivos/uno.pdf', 'size' => 10],
                ['name' => 'dos.pdf', 'filePath' => self::BASE.'/archivos/dos.pdf', 'size' => 20],
                ['name' => 'tres.pdf', 'filePath' => self::BASE.'/archivos/tres.pdf', 'size' => 30],
            ],
        ]);

        $this->artisan('huv:importar programas')->assertSuccessful();

        $item = TopicItem::sole();

        $this->assertCount(3, $item->files());
        $this->assertCount(1, $item->images());
    }

    /** Un documento sin archivo en el origen no es un fallo que reintentar. */
    public function test_un_documento_sin_archivo_no_se_cuenta_como_fallo(): void
    {
        $this->fakeArticlePortal(replace: [
            'contentType' => 'Document',
            'defaultImage' => null,
            'files' => [],
        ]);

        $this->artisan('huv:importar programas')
            ->expectsOutputToContain('Sin archivo en el origen')
            ->doesntExpectOutputToContain('Vuelva a ejecutar el comando')
            ->assertSuccessful();
    }

    /** Encuestas, preguntas frecuentes y trámites todavía no se publican aquí. */
    public function test_un_tipo_no_soportado_se_omite_con_aviso(): void
    {
        $this->fakeArticlePortal([[
            'contentID' => 6100,
            'friendlyName' => 'encuesta-de-satisfaccion',
            'name' => 'Encuesta de satisfacción',
            'contentType' => 'Poll',
            'published' => true,
            'labels' => [],
            'files' => [],
        ]]);

        $this->artisan('huv:importar programas')
            ->expectsOutputToContain('«Poll»')
            ->assertSuccessful();

        $this->assertSame(1, TopicItem::count());
        $this->assertNull(TopicItem::where('legacy_content_id', 6100)->first());
    }
}
