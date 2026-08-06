<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InlineEditorTest extends TestCase
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

    public function test_el_editor_vive_en_la_propia_portada(): void
    {
        $this->actingAs($this->editor())->get('/')
            ->assertSee('id="huv-editor-contenido"', false)
            // El botón alterna el editor en vez de llevarse a otra página.
            ->assertSee("editor = ! editor", false)
            ->assertSee('Nuevo contenido', false)
            // Y carga el editor de texto enriquecido.
            ->assertSee('data-huv-editor', false);
    }

    public function test_el_visitante_no_recibe_el_editor(): void
    {
        $this->noticia();

        $this->get('/')
            ->assertDontSee('id="huv-editor-contenido"', false)
            ->assertDontSee('data-huv-editor', false)
            ->assertDontSee('Nuevo contenido', false);
    }

    public function test_editar_una_noticia_abre_el_editor_sin_salir_de_la_portada(): void
    {
        $content = $this->noticia(['title' => 'Rendición de cuentas 2025']);
        $editor = $this->editor();

        $response = $this->actingAs($editor)->get('/');

        // El lápiz enlaza con la propia portada, no con otra pantalla.
        $response->assertSee('editar='.$content->id, false);
        $response->assertDontSee(route('admin.contents.edit', $content), false);

        $this->actingAs($editor)->get('/?editar='.$content->id)
            ->assertOk()
            ->assertSee('Rendición de cuentas 2025', false)
            // Con contenido cargado, el editor viene desplegado.
            ->assertSee('"openEditor":true', false)
            ->assertSee('Eliminar contenido', false);
    }

    public function test_el_editor_se_despliega_solo_si_el_guardado_falla(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->get('/')->assertSee('"openEditor":false', false);

        // Un título vacío no pasa la validación: al volver, el editor debe
        // seguir abierto para poder corregirlo.
        $this->actingAs($editor)
            ->from(route('home'))
            ->post('/administracion/contenidos', ['category' => Content::NEWS_CATEGORY])
            ->assertSessionHasErrors('title');

        $this->actingAs($editor)->get('/')->assertSee('"openEditor":true', false);
    }

    public function test_el_titulo_llega_en_el_html_y_no_solo_en_alpine(): void
    {
        $content = $this->noticia(['title' => 'Título que debe verse']);

        // Sin `value` en el input, quien navegue sin JavaScript encontraría el
        // campo vacío y perdería el contenido al guardar.
        $html = $this->actingAs($this->editor())->get('/?editar='.$content->id)->getContent();

        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="title"[^>]*value="Título que debe verse"/u',
            $html
        );
    }

    /* ------------------------------------------------------------------ */
    /* Fecha final de visualización                                        */
    /* ------------------------------------------------------------------ */

    public function test_un_contenido_caducado_deja_de_mostrarse(): void
    {
        $this->noticia(['title' => 'Convocatoria vencida', 'expires_at' => now()->subDay()]);

        $this->get('/')->assertDontSee('Convocatoria vencida', false);

        // Quien administra lo sigue viendo, marcado, para poder renovarlo.
        $this->actingAs($this->editor())->get('/')
            ->assertSee('Convocatoria vencida', false)
            ->assertSee('Caducado', false);
    }

    public function test_la_fecha_final_debe_ser_posterior_a_la_de_publicacion(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', [
                'title' => 'Comunicado',
                'category' => Content::NEWS_CATEGORY,
                'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'expires_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('expires_at');
    }

    public function test_sin_fecha_final_el_contenido_no_caduca(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', [
            'title' => 'Comunicado permanente',
            'category' => Content::NEWS_CATEGORY,
            'published_at' => now()->format('Y-m-d\TH:i'),
            'no_end_date' => '1',
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);

        $this->assertNull(Content::sole()->expires_at);
    }
}
