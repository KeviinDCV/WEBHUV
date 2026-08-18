<?php

namespace Tests\Feature\Admin;

use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventTest extends TestCase
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

    private function evento(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Jornada de vacunación',
            'starts_at' => now()->startOfWeek()->addDays(2)->setTime(9, 0),
            'ends_at' => now()->startOfWeek()->addDays(2)->setTime(12, 0),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Congreso de neurociencias',
            'starts_at' => now()->addWeek()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addWeek()->setTime(18, 0)->format('Y-m-d\TH:i'),
            'place' => 'Arena USC',
            'is_active' => '1',
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */

    public function test_la_agenda_exige_sesion_iniciada(): void
    {
        $event = $this->evento();

        $this->get('/administracion/eventos/nuevo')->assertRedirect(route('login'));
        $this->get("/administracion/eventos/{$event->id}/editar")->assertRedirect(route('login'));
        $this->post('/administracion/eventos', $this->datos())->assertRedirect(route('login'));
        $this->delete("/administracion/eventos/{$event->id}")->assertRedirect(route('login'));
        $this->get('/administracion/eventos/bloque')->assertRedirect(route('login'));

        $this->assertDatabaseCount('events', 1);
    }

    public function test_se_puede_crear_un_evento(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/eventos', $this->datos())
            ->assertRedirect(route('home'));

        $this->assertSame('Congreso de neurociencias', Event::sole()->title);
    }

    /**
     * El formulario pide lo mismo que el del portal, y con sus topes.
     *
     * Quien publica la agenda viene de allí: si aquí falta «Organizador» o el
     * lugar admite el triple de caracteres, el mismo evento se escribe distinto
     * según por dónde se cree.
     */
    public function test_el_formulario_pide_los_mismos_campos_que_el_portal(): void
    {
        $html = $this->actingAs($this->editor())
            ->get('/administracion/eventos/nuevo')
            ->assertOk()
            ->getContent();

        foreach (['Título', 'Organizador', 'Lugar', 'Fecha inicio', 'Hora inicio'] as $rotulo) {
            $this->assertStringContainsString($rotulo, $html, "Falta el campo «{$rotulo}».");
        }

        // Los topes del portal: 150 el título, 70 el organizador y 70 el lugar.
        $this->assertMatchesRegularExpression('~name="title"[^>]*maxlength="150"~', $html);
        $this->assertMatchesRegularExpression('~name="host"[^>]*maxlength="70"~', $html);
        $this->assertMatchesRegularExpression('~name="place"[^>]*maxlength="70"~', $html);

        // Fecha y hora en dos controles, no en un «datetime-local».
        $this->assertMatchesRegularExpression('~name="starts_date"[^>]*type="date"~', $html);
        $this->assertMatchesRegularExpression('~name="starts_time"[^>]*type="time"~', $html);
    }

    /** La fecha y la hora llegan por separado y se guardan en una sola columna. */
    public function test_la_fecha_y_la_hora_separadas_se_juntan_al_guardar(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/eventos', [
                'title' => 'Jornada de donación de sangre',
                'host' => 'Banco de Sangre',
                'place' => 'Auditorio principal',
                'starts_date' => '2026-09-15',
                'starts_time' => '14:30',
                'is_active' => '1',
            ])
            ->assertRedirect(route('home'));

        $event = Event::sole();

        $this->assertSame('Banco de Sangre', $event->host);
        $this->assertSame('2026-09-15 14:30', $event->starts_at->format('Y-m-d H:i'));
    }

    /** Un organizador más largo de lo que cabe en la tarjeta se rechaza. */
    public function test_el_organizador_no_pasa_de_setenta_caracteres(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/eventos', $this->datos(['host' => str_repeat('a', 71)]))
            ->assertSessionHasErrors('host');

        $this->assertDatabaseCount('events', 0);
    }

    public function test_la_fecha_de_fin_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        // Un evento así no se pintaría en ningún día del calendario.
        $this->actingAs($this->editor())
            ->post('/administracion/eventos', $this->datos([
                'starts_at' => now()->addWeek()->setTime(18, 0)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addWeek()->setTime(8, 0)->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_el_calendario_publica_los_eventos_activos(): void
    {
        $this->evento(['title' => 'Evento visible']);
        $this->evento(['title' => 'Evento inactivo', 'is_active' => false]);

        $this->get('/')
            ->assertSee('Evento visible', false)
            ->assertDontSee('Evento inactivo', false);

        // Quien administra sí lo ve, marcado, para poder reactivarlo.
        $this->actingAs($this->editor())->get('/')->assertSee('Evento inactivo', false);
    }

    public function test_un_evento_de_varios_dias_aparece_en_todos(): void
    {
        $monday = now()->startOfWeek();

        $this->evento([
            'title' => 'Semana de la salud',
            'starts_at' => $monday->copy()->setTime(8, 0),
            'ends_at' => $monday->copy()->addDays(4)->setTime(17, 0),
        ]);

        $html = $this->get('/')->getContent();

        // Cinco días de la semana muestran el mismo evento.
        $this->assertSame(5, substr_count($html, 'Semana de la salud'));
    }

    public function test_el_boton_nuevo_evento_solo_existe_con_sesion(): void
    {
        $this->get('/')->assertDontSee('Nuevo evento', false);

        $this->actingAs($this->editor())->get('/')
            ->assertSee('Nuevo evento', false)
            ->assertSee(route('admin.events.create'), false);
    }

    public function test_el_boton_editar_del_bloque_esta_en_la_seccion(): void
    {
        $this->actingAs($this->editor())->get('/')
            ->assertSee('data-huv-edit="eventos"', false)
            ->assertSee(route('admin.events.block.edit'), false);
    }

    /* ------------------------------------------------------------------ */
    /* Configuración del bloque                                            */
    /* ------------------------------------------------------------------ */

    public function test_la_pantalla_del_bloque_muestra_sus_campos(): void
    {
        $this->actingAs($this->editor())
            ->get('/administracion/eventos/bloque')
            ->assertOk()
            ->assertSee('Nombre del bloque', false)
            ->assertSee('Selecciona una sección', false)
            ->assertSee('Calendario de actividades', false)
            ->assertSee('Selecciona una o varias categorías', false)
            ->assertSee('Agregar una categoría', false);
    }

    public function test_se_puede_renombrar_el_bloque(): void
    {
        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', [
                'name' => 'Agenda institucional',
                'source' => 'Calendario de actividades',
            ])
            ->assertRedirect(route('home'));

        $this->get('/')->assertSee('Agenda institucional', false);
    }

    public function test_las_categorias_del_bloque_filtran_la_agenda(): void
    {
        $educacion = EventCategory::create(['name' => 'Educación']);

        $conCategoria = $this->evento(['title' => 'Curso de reanimación']);
        $conCategoria->categories()->sync([$educacion->id]);

        $this->evento(['title' => 'Evento sin categoría']);

        $this->actingAs($this->editor())->put('/administracion/eventos/bloque', [
            'name' => 'Eventos',
            'source' => 'Calendario de actividades',
            'categories' => [$educacion->id],
        ]);

        $this->get('/')
            ->assertSee('Curso de reanimación', false)
            ->assertDontSee('Evento sin categoría', false);
    }

    public function test_sin_categorias_elegidas_se_muestra_toda_la_agenda(): void
    {
        $educacion = EventCategory::create(['name' => 'Educación']);

        $conCategoria = $this->evento(['title' => 'Curso de reanimación']);
        $conCategoria->categories()->sync([$educacion->id]);

        $this->evento(['title' => 'Evento sin categoría']);

        $this->get('/')
            ->assertSee('Curso de reanimación', false)
            ->assertSee('Evento sin categoría', false);
    }

    public function test_no_se_admiten_secciones_inventadas(): void
    {
        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', ['name' => 'Eventos', 'source' => 'Inventada'])
            ->assertSessionHasErrors('source');
    }

    public function test_se_pueden_crear_categorias_de_evento(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/eventos/categorias', ['name' => 'Participación Social en Salud'])
            ->assertRedirect();

        $this->assertSame('participacion-social-en-salud', EventCategory::sole()->slug);
    }

    public function test_el_bloque_de_eventos_se_autoconfigura(): void
    {
        $this->get('/')->assertOk();

        $block = ContentBlock::where('key', ContentBlock::EVENTS_KEY)->sole();

        $this->assertSame('Calendario de actividades', $block->option('source'));
        $this->assertSame([], $block->option('categories'));
    }
}
