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

/**
 * Temas de enlaces: «Contrataciones» y compañía.
 *
 * Se distinguen de los demás en que se paginan en el servidor. Con setecientos
 * registros no se pueden imprimir todos y decidir después cuáles se ven.
 */
class TopicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LegacyLink::forget();
    }

    private function editor(): User
    {
        return User::firstOrCreate(
            ['email' => 'editora@huv.gov.co'],
            ['name' => 'Editora del portal', 'password' => Hash::make('Contrasena-Segura-2026#')]
        );
    }

    private function tema(): Topic
    {
        return Topic::create([
            'name' => 'Contrataciones',
            'slug' => 'contrataciones',
            'legacy_content_types' => ['Link'],
            'imported_at' => now(),
        ]);
    }

    private function enlace(Topic $topic, array $overrides = []): TopicItem
    {
        return $topic->items()->create(array_merge([
            'kind' => TopicItem::KIND_LINK,
            'title' => 'C26-106',
            'body' => '<p>QUINBERLAB SAS</p>',
            'source_url' => 'https://community.secop.gov.co/Public/Tendering/OpportunityDetail/Index?noticeUID=CO1.NTC.1',
            'published_at' => now()->subDay(),
            'modified_at' => now()->subDay(),
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */

    public function test_el_listado_se_pagina_en_el_servidor(): void
    {
        $topic = $this->tema();

        foreach (range(1, 25) as $i) {
            $this->enlace($topic, [
                'title' => 'C26-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'modified_at' => now()->subDays($i),
            ]);
        }

        $response = $this->get(route('topics.show', $topic))->assertOk();

        // Diez por página, como en el portal: ni una fila más en el HTML.
        $response->assertViewHas('items', fn ($items) => $items->count() === 10 && $items->total() === 25);

        $response->assertSee('C26-001')->assertDontSee('C26-011');

        // Y la segunda página trae los siguientes.
        $this->get(route('topics.show', $topic).'?page=2')
            ->assertOk()
            ->assertSee('C26-011')
            ->assertDontSee('C26-001');
    }

    /**
     * El listado se pagina en el servidor, así que buscar y filtrar tienen que
     * hacerlo también: si no, solo se buscaría dentro de la página que se ve.
     */
    public function test_la_busqueda_recorre_todo_el_tema_y_no_solo_la_pagina(): void
    {
        $topic = $this->tema();

        foreach (range(1, 25) as $i) {
            $this->enlace($topic, [
                'title' => 'C26-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'modified_at' => now()->subDays($i),
            ]);
        }

        // «C26-024» está en la última página; la búsqueda debe encontrarlo.
        $this->get(route('topics.show', $topic).'?buscar=C26-024')
            ->assertOk()
            ->assertSee('C26-024')
            ->assertViewHas('items', fn ($items) => $items->total() === 1);
    }

    public function test_se_puede_filtrar_por_categoria_y_ordenar_alfabeticamente(): void
    {
        $topic = $this->tema();

        $categoria = TopicCategory::create(['topic_id' => $topic->id, 'name' => '2026', 'slug' => '2026']);

        $this->enlace($topic, ['title' => 'C26-500'])->categories()->attach($categoria);
        $this->enlace($topic, ['title' => 'C22-100']);

        $this->get(route('topics.show', [$topic, 'categoria' => $categoria->id]))
            ->assertOk()
            ->assertSee('C26-500')
            ->assertDontSee('C22-100');

        $this->get(route('topics.show', [$topic, 'orden' => 'az']))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->first()->title === 'C22-100');
    }

    public function test_la_ficha_lleva_al_expediente_de_fuera(): void
    {
        $topic = $this->tema();
        $enlace = $this->enlace($topic);

        $this->get(route('topics.items.show', [$topic, $enlace]))
            ->assertOk()
            ->assertSee('QUINBERLAB SAS')
            ->assertSee('Consultar el expediente completo')
            ->assertSee($enlace->source_url, false)
            // Ni archivo ni fecha de expedición: un enlace no tiene ninguno.
            ->assertDontSee('Archivos para descargar')
            ->assertDontSee('Fecha de expedición');
    }

    public function test_un_enlace_necesita_su_direccion(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'C26-999',
                'body' => '<p>Sin destino.</p>',
                'show_in_feed' => '1',
            ])
            ->assertSessionHasErrors('link');

        $this->assertSame(0, TopicItem::count());
    }

    public function test_se_publica_un_enlace_desde_el_editor(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'C26-999',
                'body' => '<p>DATAMEDIC COLOMBIA SAS</p>',
                'link' => 'https://community.secop.gov.co/expediente',
                'show_in_feed' => '1',
            ])
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();

        $this->assertSame(TopicItem::KIND_LINK, $item->kind);
        $this->assertSame('https://community.secop.gov.co/expediente', $item->source_url);
    }

    /**
     * Regresión: `@aria-current(...)` no existe en Blade. Se imprimía literal
     * en el HTML y Alpine lo leía como el atajo de un evento, reventando con
     * un error de sintaxis en la consola del navegador.
     */
    public function test_ninguna_directiva_de_blade_llega_sin_procesar_al_html(): void
    {
        $topic = $this->tema();
        $this->enlace($topic);

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        $this->assertSame(
            0,
            preg_match_all('/@(?:aria|class|checked|selected|disabled|readonly|required|style|data)[\w-]*\(/', $html),
            'Hay directivas de Blade sin procesar en el HTML.'
        );

        $this->assertStringContainsString('aria-current="true"', $html);
    }

    public function test_el_visitante_no_ve_lo_inactivo_ni_lo_oculto(): void
    {
        $topic = $this->tema();

        $this->enlace($topic, ['title' => 'C26-VISIBLE']);
        $this->enlace($topic, ['title' => 'C26-INACTIVO', 'is_active' => false]);
        $this->enlace($topic, ['title' => 'C26-OCULTO', 'is_hidden' => true]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('C26-VISIBLE')
            ->assertDontSee('C26-INACTIVO')
            ->assertDontSee('C26-OCULTO');
    }
}
