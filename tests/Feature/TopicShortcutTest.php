<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Temas de atajos: «Población vulnerable».
 *
 * Son temas de enlaces, pero de orden manual y con cuatro tarjetas puestas a
 * mano que llevan a otros temas del mismo sitio. No son un archivo que paginar,
 * así que el portal los publica con la plantilla de tarjetas y sin fila de
 * orden, y cada tarjeta lleva derecha a su destino sin ficha intermedia.
 */
class TopicShortcutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LegacyLink::forget();

        config(['huv.legacy_base' => 'https://portal-anterior.gov.co']);
    }

    private function atajos(): Topic
    {
        return Topic::create([
            'name' => 'Población vulnerable',
            'slug' => 'poblacion-vulnerable',
            'legacy_content_types' => ['Link'],
            'legacy_template_type' => Topic::TEMPLATE_SORTABLE,
            'imported_at' => now(),
        ]);
    }

    private function atajo(Topic $topic, string $title, string $url, int $order): TopicItem
    {
        return $topic->items()->create([
            'kind' => TopicItem::KIND_LINK,
            'title' => $title,
            'body' => '<p>Aquí encuentras todo.</p>',
            'source_url' => $url,
            'legacy_display_order' => $order,
            'published_at' => now()->subYear(),
            'modified_at' => now()->subYear(),
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Un tema de enlaces de orden manual no es «Contrataciones»: no se pagina
     * en el servidor ni se imprime como filas, sino como las tarjetas del resto
     * de temas.
     */
    public function test_un_tema_de_atajos_no_usa_el_listado_compacto(): void
    {
        $topic = $this->atajos();

        $this->assertFalse($topic->isLinkList());

        $this->atajo($topic, 'Normatividad para población vulnerable', '/tema/normatividad/poblacion-vulnerable', 33);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewIs('topics.show')
            // De orden manual: el portal no ofrece reordenar por fecha.
            ->assertViewHas('tabs', []);
    }

    /** Y sigue siendo compacto el tema de enlaces que sí es un archivo. */
    public function test_el_archivo_de_enlaces_conserva_el_listado_compacto(): void
    {
        $contrataciones = Topic::create([
            'name' => 'Contrataciones',
            'slug' => 'contrataciones',
            'legacy_content_types' => ['Link'],
            'imported_at' => now(),
        ]);

        $this->assertTrue($contrataciones->isLinkList());
    }

    /**
     * La tarjeta lleva al destino, no a una ficha propia: es lo que hace el
     * portal y ahorra un clic para leer el mismo párrafo.
     */
    public function test_la_tarjeta_lleva_derecha_al_destino(): void
    {
        $topic = $this->atajos();

        $destino = Topic::create([
            'name' => 'Proyectos en ejecución',
            'slug' => 'proyectos-en-ejecucion',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $categoria = TopicCategory::create([
            'topic_id' => $destino->id,
            'name' => 'Población vulnerable',
            'slug' => 'poblacion-vulnerable',
        ]);

        $item = $this->atajo(
            $topic,
            'Proyectos para población vulnerable',
            '/tema/proyectos-en-ejecucion/poblacion-vulnerable-423595',
            36
        );

        $this->assertSame(
            route('topics.show', [$destino, 'categoria' => $categoria->id]),
            $item->url()
        );

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee(route('topics.show', [$destino, 'categoria' => $categoria->id]), false)
            // Y por tanto no hay enlace a una ficha del atajo.
            ->assertDontSee(route('topics.items.show', [$topic, $item]), false);
    }

    /** El orden es el que colocó quien edita, no el de la fecha. */
    public function test_los_atajos_salen_en_el_orden_colocado_a_mano(): void
    {
        $topic = $this->atajos();

        $this->atajo($topic, 'Normatividad', '/tema/normatividad', 33);
        $this->atajo($topic, 'Proyectos', '/tema/proyectos-en-ejecucion', 36);
        $this->atajo($topic, 'Políticas', '/tema/politicas-y-lineamientos', 34);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('title')->all()
                === ['Proyectos', 'Políticas', 'Normatividad']);
    }

    /* ---------------- Traducción de destinos ---------------- */

    public function test_un_destino_de_un_tema_migrado_apunta_aqui(): void
    {
        Topic::create([
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $this->assertSame(route('topics.show', 'programas'), LegacyLink::rewrite('/tema/programas'));
    }

    /**
     * El portal desempata con un número los nombres de categoría que se repiten
     * entre temas. Aquí las categorías cuelgan de su tema, así que el sufijo
     * sobra y hay que quitarlo para reconocerla.
     */
    public function test_se_reconoce_la_categoria_aunque_el_portal_le_ponga_un_numero(): void
    {
        $topic = Topic::create([
            'name' => 'Políticas y lineamientos',
            'slug' => 'politicas-y-lineamientos',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $categoria = TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => 'Población vulnerable',
            'slug' => 'poblacion-vulnerable',
        ]);

        $this->assertSame(
            route('topics.show', [$topic, 'categoria' => $categoria->id]),
            LegacyLink::rewrite('/tema/politicas-y-lineamientos/poblacion-vulnerable-31962')
        );
    }

    /**
     * Una categoría cuyo nombre acaba en número es legítima: «2023» no puede
     * quedarse en nada por parecerse a un desempate.
     */
    public function test_una_categoria_que_es_un_numero_no_se_recorta(): void
    {
        $topic = Topic::create([
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $categoria = TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => '2023',
            'slug' => '2023',
        ]);

        $this->assertSame(
            route('topics.show', [$topic, 'categoria' => $categoria->id]),
            LegacyLink::rewrite('/tema/programas/2023')
        );
    }

    /** Una categoría que aquí no existe enseña el tema entero, como el portal. */
    public function test_una_categoria_desconocida_no_vacia_el_listado(): void
    {
        Topic::create([
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $this->assertSame(
            route('topics.show', 'programas'),
            LegacyLink::rewrite('/tema/programas/poblacion-vulnerable-379850')
        );
    }

    /** Lo que todavía no se ha migrado sigue viviendo en el portal anterior. */
    public function test_un_tema_sin_migrar_sigue_apuntando_al_portal_anterior(): void
    {
        $this->assertSame(
            'https://portal-anterior.gov.co/tema/normatividad/poblacion-vulnerable',
            LegacyLink::rewrite('/tema/normatividad/poblacion-vulnerable')
        );
    }

    /** Lo que apunta a otra entidad se devuelve intacto. */
    public function test_un_destino_de_fuera_no_se_toca(): void
    {
        $url = 'https://community.secop.gov.co/Public/Tendering/OpportunityDetail/Index?noticeUID=CO1.NTC.1';

        $this->assertSame($url, LegacyLink::rewrite($url));
        $this->assertSame('', LegacyLink::rewrite(null));
    }

    /** Y el portal a veces guarda la dirección entera, no solo el camino. */
    public function test_una_direccion_absoluta_del_portal_tambien_se_traduce(): void
    {
        Topic::create([
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $this->assertSame(
            route('topics.show', 'programas'),
            LegacyLink::rewrite('https://portal-anterior.gov.co/tema/programas')
        );
    }
}
