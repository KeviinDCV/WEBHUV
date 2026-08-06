<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\ContentBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentBlockTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Noticia de ejemplo',
            'category' => Content::NEWS_CATEGORY,
            'published_at' => now()->subHour(),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Noticias',
            'sections_count' => 1,
            'sort' => 'recent',
            'show_title' => '1',
            'theme' => 'navy',
            'sections' => [
                ['category' => Content::NEWS_CATEGORY, 'title' => 'Noticias'],
            ],
        ], $overrides);
    }

    public function test_el_bloque_de_noticias_se_crea_solo_la_primera_vez(): void
    {
        // La portada no puede depender de que alguien haya pasado antes por la
        // pantalla de configuración.
        $this->get('/')->assertOk();

        $block = ContentBlock::with('sections')->where('key', ContentBlock::NEWS_KEY)->sole();

        $this->assertSame(Content::NEWS_CATEGORY, $block->sections->first()->category);

        // La agenda también se autoconfigura al primer render.
        $this->assertDatabaseHas('content_blocks', ['key' => ContentBlock::EVENTS_KEY]);
    }

    public function test_la_configuracion_exige_sesion_iniciada(): void
    {
        $block = ContentBlock::news();

        $this->get("/administracion/bloques/{$block->id}")->assertRedirect(route('login'));
        $this->put("/administracion/bloques/{$block->id}", $this->datos())->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_configuracion_muestra_sus_campos(): void
    {
        $block = ContentBlock::news();

        $this->actingAs($this->editor())
            ->get("/administracion/bloques/{$block->id}")
            ->assertOk()
            ->assertSee('Nombre del bloque', false)
            ->assertSee('Número de secciones a mostrar', false)
            ->assertSee('Orden de los contenidos', false)
            ->assertSee('Habilitar título', false)
            ->assertSee('Secciones de bloque', false)
            ->assertSee('Elige la sección uno', false)
            ->assertSee('Título que lleva esta sección', false)
            ->assertSee('Ocultar en muro de contenidos', false)
            ->assertSee('Selecciona un tema', false);
    }

    public function test_el_boton_editar_del_bloque_esta_en_la_seccion(): void
    {
        $block = ContentBlock::news();

        $this->get('/')->assertDontSee('data-huv-edit="noticias"', false);

        $this->actingAs($this->editor())->get('/')
            ->assertSee('data-huv-edit="noticias"', false)
            ->assertSee(route('admin.blocks.edit', $block), false);
    }

    public function test_se_puede_cambiar_el_rotulo_y_el_orden(): void
    {
        $block = ContentBlock::news();

        $this->noticia(['title' => 'La más antigua', 'published_at' => now()->subMonth()]);
        $this->noticia(['title' => 'La más reciente', 'published_at' => now()]);

        $this->actingAs($this->editor())
            ->put("/administracion/bloques/{$block->id}", $this->datos([
                'sort' => 'oldest',
                'sections' => [['category' => Content::NEWS_CATEGORY, 'title' => 'Actualidad institucional']],
            ]))
            ->assertRedirect(route('home'));

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Actualidad institucional', $html);
        // Con el orden invertido, la más antigua ocupa el lugar principal.
        $this->assertLessThan(
            strpos($html, 'La más reciente'),
            strpos($html, 'La más antigua')
        );
    }

    public function test_se_puede_ocultar_el_titulo_del_bloque(): void
    {
        $block = ContentBlock::news();
        $this->noticia();

        $datos = $this->datos(['sections' => [['category' => Content::NEWS_CATEGORY, 'title' => 'Rótulo visible']]]);
        unset($datos['show_title']);

        $this->actingAs($this->editor())->put("/administracion/bloques/{$block->id}", $datos);

        $this->get('/')->assertDontSee('Rótulo visible', false);
    }

    public function test_el_bloque_puede_combinar_varias_secciones(): void
    {
        $block = ContentBlock::news();

        $this->noticia(['title' => 'Una noticia']);
        $this->noticia(['title' => 'Un comunicado', 'category' => 'Comunicados']);

        $this->actingAs($this->editor())->put("/administracion/bloques/{$block->id}", $this->datos([
            'sections_count' => 2,
            'sections' => [
                ['category' => Content::NEWS_CATEGORY, 'title' => 'Noticias'],
                ['category' => 'Comunicados', 'title' => 'Comunicados oficiales'],
            ],
        ]));

        $this->get('/')
            ->assertSee('Comunicados oficiales', false)
            ->assertSee('Un comunicado', false);
    }

    public function test_ocultar_en_muro_saca_la_categoria_del_listado_general(): void
    {
        $block = ContentBlock::news();
        $this->noticia(['title' => 'Fuera del muro']);

        $this->actingAs($this->editor())->put("/administracion/bloques/{$block->id}", $this->datos([
            'sections' => [[
                'category' => Content::NEWS_CATEGORY,
                'title' => 'Noticias',
                'hide_in_feed' => '1',
            ]],
        ]));

        $html = $this->get('/')->getContent();

        // Sale del muro, pero sigue en su bloque.
        $feed = substr($html, strpos($html, 'id="huv-contenidos"'));
        $this->assertStringNotContainsString('Fuera del muro', $feed);
        $this->assertStringContainsString('Fuera del muro', $html);
    }

    public function test_el_tema_pinta_el_fondo_del_bloque(): void
    {
        $block = ContentBlock::news();

        $this->actingAs($this->editor())
            ->put("/administracion/bloques/{$block->id}", $this->datos(['theme' => 'crimson']));

        $this->get('/')->assertSee('background: '.\App\Support\Themes::color('crimson'), false);
    }

    public function test_no_se_admiten_secciones_ni_temas_inventados(): void
    {
        $block = ContentBlock::news();
        $editor = $this->editor();

        $this->actingAs($editor)
            ->put("/administracion/bloques/{$block->id}", $this->datos(['theme' => 'fucsia-neon']))
            ->assertSessionHasErrors('theme');

        $this->actingAs($editor)
            ->put("/administracion/bloques/{$block->id}", $this->datos([
                'sections' => [['category' => 'Inventada', 'title' => 'X']],
            ]))
            ->assertSessionHasErrors('sections.0.category');
    }
}
