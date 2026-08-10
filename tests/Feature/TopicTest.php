<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Models\User;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El resolutor recuerda los temas migrados durante la petición, y en las
        // pruebas todas comparten proceso.
        LegacyLink::forget();
    }

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function tema(array $overrides = []): Topic
    {
        return Topic::create(array_merge([
            'name' => 'Presupuesto',
            'slug' => 'presupuesto',
            'imported_at' => now(),
        ], $overrides));
    }

    private function documento(Topic $topic, array $overrides = []): TopicItem
    {
        return $topic->items()->create(array_merge([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Ejecución presupuestal de enero',
            'body' => '<p>Ejecución presupuestal del mes de enero.</p>',
            'issued_at' => now()->subMonths(2),
            'published_at' => now()->subDay(),
            'file_path' => 'documentos/1/enero.pdf',
            'file_name' => 'enero.pdf',
            'file_size' => 1048576,
            'file_extension' => 'pdf',
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* Listado público                                                     */
    /* ------------------------------------------------------------------ */

    public function test_el_tema_muestra_sus_documentos(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Presupuesto')
            ->assertSee($document->title)
            ->assertSee('Busca en Presupuesto')
            ->assertSee('Cargar más contenidos', false);
    }

    public function test_las_categorias_se_listan_con_su_recuento(): void
    {
        $topic = $this->tema();

        $category = TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => 'Ejecución Presupuestal 2026',
            'slug' => 'ejecucion-presupuestal-2026',
        ]);

        $this->documento($topic)->categories()->attach($category);
        $this->documento($topic, ['title' => 'Otro'])->categories()->attach($category);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Ejecución Presupuestal 2026')
            ->assertViewHas(
                'categories',
                fn ($categories) => $categories->firstWhere('name', 'Ejecución Presupuestal 2026')['count'] === 2
            );
    }

    public function test_una_categoria_sin_documentos_visibles_no_aparece(): void
    {
        $topic = $this->tema();

        TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => 'Categoría vacía',
            'slug' => 'categoria-vacia',
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertDontSee('Categoría vacía');
    }

    public function test_el_visitante_no_ve_lo_inactivo_lo_oculto_ni_lo_programado(): void
    {
        $topic = $this->tema();

        $this->documento($topic, ['title' => 'Documento inactivo', 'is_active' => false]);
        $this->documento($topic, ['title' => 'Documento oculto', 'is_hidden' => true]);
        $this->documento($topic, ['title' => 'Documento programado', 'published_at' => now()->addWeek()]);
        $this->documento($topic, ['title' => 'Documento publicado']);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Documento publicado')
            ->assertDontSee('Documento inactivo')
            ->assertDontSee('Documento oculto')
            ->assertDontSee('Documento programado');
    }

    public function test_quien_administra_si_los_ve_marcados(): void
    {
        $topic = $this->tema();
        $this->documento($topic, ['title' => 'Documento oculto', 'is_hidden' => true]);

        $this->actingAs($this->editor())
            ->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Documento oculto')
            ->assertSee('Oculto en el listado');
    }

    /* ------------------------------------------------------------------ */
    /* Ficha del documento                                                 */
    /* ------------------------------------------------------------------ */

    public function test_la_ficha_muestra_la_fecha_de_expedicion_y_el_archivo(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('topics.items.show', [$topic, $document]))
            ->assertOk()
            ->assertSee($document->title)
            ->assertSee('Fecha de expedición')
            ->assertSee('Archivos para descargar')
            ->assertSee('1 Mb')
            ->assertSee('enero.pdf');
    }

    public function test_la_ficha_de_un_documento_oculto_no_existe_para_el_visitante(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic, ['is_hidden' => true]);

        $this->get(route('topics.items.show', [$topic, $document]))->assertNotFound();

        $this->actingAs($this->editor())
            ->get(route('topics.items.show', [$topic, $document]))
            ->assertOk()
            ->assertSee('noindex, nofollow', false);
    }

    public function test_un_documento_de_otro_tema_no_se_sirve_bajo_este(): void
    {
        $topic = $this->tema();
        $other = $this->tema(['name' => 'Planes', 'slug' => 'planes']);

        $document = $this->documento($other);

        $this->get(route('topics.items.show', [$topic->slug, $document->slug]))->assertNotFound();
    }

    /**
     * La dirección del archivo se arma con asset(): Storage::url() la construye
     * a partir de APP_URL y devolvería un enlace roto en cuanto el aplicativo se
     * sirva en otro puerto o dominio.
     */
    public function test_el_enlace_del_archivo_no_depende_de_app_url(): void
    {
        config(['app.url' => 'https://otro-dominio.example']);

        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('topics.items.show', [$topic, $document]))
            ->assertOk()
            ->assertSee(url('storage/documentos/1/enero.pdf'))
            ->assertDontSee('otro-dominio.example');
    }

    /* ------------------------------------------------------------------ */
    /* Enlaces del menú durante la migración                               */
    /* ------------------------------------------------------------------ */

    public function test_un_tema_migrado_se_enlaza_dentro_del_aplicativo(): void
    {
        $this->tema();

        $resolved = LegacyLink::resolve(['label' => 'Presupuesto', 'path' => '/tema/presupuesto']);

        $this->assertSame(route('topics.show', 'presupuesto'), $resolved['href']);
        $this->assertFalse($resolved['external']);
    }

    public function test_un_tema_sin_migrar_sigue_apuntando_al_portal_actual(): void
    {
        $resolved = LegacyLink::resolve(['label' => 'Planes', 'path' => '/tema/planes']);

        $this->assertSame(
            rtrim((string) config('huv.legacy_base'), '/').'/tema/planes',
            $resolved['href']
        );
        $this->assertTrue($resolved['external']);
    }

    public function test_el_menu_lleva_al_tema_migrado(): void
    {
        $this->tema();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('topics.show', 'presupuesto'), false);
    }

    /**
     * Regresión: la correspondencia entre los tipos del portal y los de aquí
     * estaba escrita dos veces —en el modelo y en el importador—, y al añadir
     * las preguntas frecuentes se actualizó una sola. El tema decía «Admite:
     * pregunta» y la importación se saltaba las once por no conocer el tipo.
     */
    public function test_todo_tipo_que_un_tema_admite_lo_reconoce_la_importacion(): void
    {
        $tipos = ['Document', 'Article', 'Ad', 'Link', 'Faq'];

        foreach ($tipos as $tipo) {
            $topic = new Topic(['legacy_content_types' => [$tipo]]);
            $kind = Topic::kindForLegacyType($tipo);

            $this->assertNotNull($kind, "El importador no sabe qué es un «{$tipo}».");
            $this->assertSame(
                [$kind],
                $topic->supportedKinds(),
                "El tema y la importación no se ponen de acuerdo sobre «{$tipo}»."
            );
            $this->assertNotSame('', $topic->itemNoun($kind));
        }

        // Y lo que no publicamos se rechaza, en vez de guardarse como otra cosa.
        $this->assertNull(Topic::kindForLegacyType('Poll'));
        $this->assertNull(Topic::kindForLegacyType(null));
    }

    /**
     * El resumen de las tarjetas se recorta como en el portal.
     *
     * Doscientos caracteres, por palabra y sin puntos suspensivos. Antes se
     * cortaba en ciento sesenta y con puntos, así que las tarjetas decían menos
     * que las del original y encima partían la última palabra.
     */
    public function test_el_resumen_se_recorta_como_en_el_portal(): void
    {
        $topic = $this->tema();

        $largo = trim(str_repeat('palabra ', 40)); // 319 caracteres
        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Con cuerpo largo',
            'body' => '<p>'.$largo.'</p>',
            'published_at' => now(),
        ]);

        $resumen = $item->summary();

        $this->assertLessThanOrEqual(200, mb_strlen($resumen));
        $this->assertStringEndsWith('palabra', $resumen, 'Se cortó a mitad de palabra.');
        $this->assertStringNotContainsString('...', $resumen);
        $this->assertStringNotContainsString('…', $resumen);

        // Y es un prefijo exacto del cuerpo, como la descripción del portal.
        $this->assertStringStartsWith($resumen, $largo);

        // Un cuerpo corto no se toca.
        $corto = $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Con cuerpo corto',
            'body' => '<p>Dos frases. Nada más.</p>',
            'published_at' => now(),
        ]);

        $this->assertSame('Dos frases. Nada más.', $corto->summary());
    }

    /**
     * Ni un solo destino de la configuración puede ser «#».
     *
     * Había treinta y ocho. Un enlace muerto no rompe nada y por eso se queda
     * meses: se ve igual que uno bueno hasta que alguien lo pulsa. Un ancla con
     * nombre —«#transparencia»— sí vale: lleva a una sección de la propia
     * página.
     */
    public function test_ningun_destino_de_la_configuracion_es_un_enlace_muerto(): void
    {
        $muertos = [];

        $revisar = function (mixed $valor, string $ruta) use (&$revisar, &$muertos): void {
            if (is_array($valor)) {
                foreach ($valor as $clave => $hijo) {
                    $revisar($hijo, $ruta.'.'.$clave);
                }

                return;
            }

            if ($valor === '#') {
                $muertos[] = $ruta;
            }
        };

        $revisar(config('huv'), 'huv');

        $this->assertSame([], $muertos, 'Hay destinos sin resolver en config/huv.php.');
    }

    /** Y tampoco puede llegar ninguno al HTML de la portada. */
    public function test_la_portada_no_publica_enlaces_muertos(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(
            0,
            preg_match_all('/href="#"/', $html),
            'La portada publica enlaces que no llevan a ninguna parte.'
        );
    }

    /**
     * Los siete enlaces de «Participa» apuntaban a «#».
     *
     * Un enlace muerto en el menú principal no rompe nada y por eso puede
     * quedarse ahí meses: se ve igual que uno bueno hasta que alguien lo pulsa.
     * Con 'path' se resuelven solos según lo que esté migrado.
     */
    public function test_el_menu_de_participa_no_tiene_enlaces_muertos(): void
    {
        $participa = collect(config('huv.nav'))->firstWhere('key', 'participa');

        $this->assertNotNull($participa, 'Desapareció el menú «Participa».');
        $this->assertCount(7, $participa['children']);

        foreach ($participa['children'] as $link) {
            $this->assertArrayNotHasKey('url', $link, "«{$link['label']}» sigue sin destino.");
            $this->assertStringStartsWith('/tema/', $link['path']);
            $this->assertNotSame('#', LegacyLink::resolve($link)['href']);
        }
    }
}
