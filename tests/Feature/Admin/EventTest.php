<?php

namespace Tests\Feature\Admin;

use App\Models\ContentBlock;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * La agenda de la portada.
 *
 * No es una tabla propia: es un tema —«Calendario de actividades», con los
 * ciento cuarenta y un eventos que publica el portal—, y sus eventos se crean y
 * se editan con el mismo formulario que cualquier otro contenido. Antes había
 * dos agendas, con dos formularios distintos, que no se veían entre sí.
 *
 * Lo que sí es propio del bloque: cómo se titula, de qué tema salen los eventos
 * y qué categorías de ese tema se dejan pasar.
 */
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

    private function agenda(): Topic
    {
        return Topic::firstOrCreate(
            ['slug' => 'calendario-de-actividades'],
            [
                'name' => 'Calendario de actividades',
                'legacy_content_types' => ['Event'],
                'imported_at' => now(),
            ]
        );
    }

    private function evento(array $overrides = []): TopicItem
    {
        $titulo = $overrides['title'] ?? 'Jornada de vacunación';

        return $this->agenda()->items()->create(array_merge([
            'kind' => TopicItem::KIND_EVENT,
            'title' => $titulo,
            'slug' => Str::slug($titulo).'-'.Str::random(5),
            'opens_at' => now()->startOfWeek()->addDays(2)->setTime(9, 0),
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */

    public function test_el_calendario_publica_los_eventos_del_tema(): void
    {
        $this->evento(['title' => 'Jornada de donación de sangre']);

        $this->get('/')->assertOk()->assertSee('Jornada de donación de sangre', false);
    }

    /** Lo oculto o lo programado no sale, igual que en cualquier listado. */
    public function test_el_visitante_no_ve_lo_que_no_esta_publicado(): void
    {
        $this->evento(['title' => 'Evento oculto', 'is_hidden' => true]);
        $this->evento(['title' => 'Evento programado', 'published_at' => now()->addWeek()]);

        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertDontSee('Evento oculto', false);
        $respuesta->assertDontSee('Evento programado', false);
    }

    /**
     * Un evento de varios días aparece en todos.
     *
     * Los del portal no traen cierre y empiezan y acaban el mismo día, pero la
     * columna existe: un congreso de tres días tiene que verse los tres, no
     * solo el primero.
     */
    public function test_un_evento_de_varios_dias_aparece_en_todos(): void
    {
        $lunes = now()->startOfWeek();

        $this->evento([
            'title' => 'Semana de la seguridad del paciente',
            'opens_at' => $lunes->copy()->setTime(8, 0),
            'closes_at' => $lunes->copy()->addDays(3)->setTime(17, 0),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'Semana de la seguridad del paciente'));
    }

    /**
     * «Nuevo evento» lleva al editor del tema, no a un formulario aparte.
     *
     * Es el mismo editor que usa cualquier contenido, así que trae de una vez
     * la descripción con formato, la foto, las categorías y lo demás que el
     * portal ofrece al crear un evento.
     */
    public function test_el_boton_nuevo_evento_lleva_al_editor_del_tema(): void
    {
        $topic = $this->agenda();

        $this->get('/')->assertOk()->assertDontSee('Nuevo evento', false);

        $this->actingAs($this->editor())
            ->get('/')
            ->assertOk()
            ->assertSee('Nuevo evento', false)
            ->assertSee(route('topics.show', $topic).'#huv-editor-tema', false);
    }

    public function test_la_pantalla_del_bloque_exige_sesion(): void
    {
        $this->get('/administracion/eventos/bloque')->assertRedirect(route('login'));
    }

    public function test_la_pantalla_del_bloque_ofrece_las_categorias_del_tema(): void
    {
        $topic = $this->agenda();
        $topic->categories()->create(['name' => 'Educación', 'slug' => 'educacion']);

        $this->actingAs($this->editor())
            ->get('/administracion/eventos/bloque')
            ->assertOk()
            ->assertSee('Educación', false)
            ->assertSee('Calendario de actividades', false);
    }

    public function test_se_puede_renombrar_el_bloque(): void
    {
        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', [
                'name' => 'Agenda',
                'source' => ContentBlock::DEFAULT_EVENT_SOURCE,
            ])
            ->assertRedirect(route('home'));

        $this->assertSame('Agenda', ContentBlock::events()->name);
    }

    /** Las categorías elegidas filtran la agenda. */
    public function test_las_categorias_del_bloque_filtran_la_agenda(): void
    {
        $topic = $this->agenda();
        $educacion = $topic->categories()->create(['name' => 'Educación', 'slug' => 'educacion']);

        $this->evento(['title' => 'Congreso de enfermería'])->categories()->attach($educacion);
        $this->evento(['title' => 'Feria de servicios']);

        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', [
                'name' => 'Eventos',
                'source' => ContentBlock::DEFAULT_EVENT_SOURCE,
                'categories' => [$educacion->id],
            ])
            ->assertRedirect(route('home'));

        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertSee('Congreso de enfermería', false);
        $respuesta->assertDontSee('Feria de servicios', false);
    }

    /**
     * Al cambiar de sección, las categorías de la anterior no se quedan.
     *
     * Pertenecen a un tema: guardadas contra otro, filtrarían por
     * identificadores que allí no existen y el calendario saldría siempre vacío
     * sin que nada explicara por qué.
     */
    public function test_cambiar_de_seccion_descarta_las_categorias_de_la_anterior(): void
    {
        $topic = $this->agenda();
        $educacion = $topic->categories()->create(['name' => 'Educación', 'slug' => 'educacion']);

        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', [
                'name' => 'Eventos',
                'source' => 'consulta-ciudadana',
                'categories' => [$educacion->id],
            ])
            ->assertRedirect(route('home'));

        $this->assertSame([], ContentBlock::events()->option('categories'));
    }

    /**
     * La sección se guarda por tema, no por nombre.
     *
     * Hay DOS temas llamados «Rendición de cuentas»: el de slug «control», que
     * solo admite documentos, y el de slug «rendicion-de-cuentas», que es el que
     * tiene eventos. Buscando por nombre salía el primero que apareciera, y el
     * calendario se quedaba leyendo un tema sin eventos —vacío para siempre— sin
     * que nada lo explicara.
     */
    public function test_la_seccion_se_engancha_al_tema_y_no_a_su_nombre(): void
    {
        // El homónimo que no tiene eventos, creado primero a propósito.
        Topic::create([
            'name' => 'Rendición de cuentas',
            'slug' => 'control',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $bueno = Topic::create([
            'name' => 'Rendición de cuentas',
            'slug' => 'rendicion-de-cuentas',
            'legacy_content_types' => ['Event'],
            'imported_at' => now(),
        ]);

        $bueno->items()->create([
            'kind' => TopicItem::KIND_EVENT,
            'title' => 'Audiencia pública de rendición de cuentas',
            'slug' => 'audiencia-publica',
            'opens_at' => now()->startOfWeek()->addDays(2)->setTime(9, 0),
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->editor())
            ->put('/administracion/eventos/bloque', [
                'name' => 'Eventos',
                'source' => 'rendicion-de-cuentas',
            ])
            ->assertRedirect(route('home'));

        $this->get('/')->assertOk()->assertSee('Audiencia pública de rendición de cuentas', false);
    }

    /**
     * La ficha del evento dice cuándo, dónde y quién lo organiza.
     *
     * Los ciento cuarenta y un eventos del portal traen lugar y organizador, la
     * importación los guarda y el editor los pide, pero no llegaban a ninguna
     * pantalla: quien pulsaba un evento en el calendario aterrizaba en una
     * página que no decía ni cuándo ni dónde era.
     */
    public function test_la_ficha_del_evento_dice_cuando_donde_y_quien(): void
    {
        $topic = $this->agenda();

        $evento = $this->evento([
            'title' => 'Audiencia pública',
            'event_location' => 'Hospital sede Cali',
            'event_host' => 'Oficina de Control Interno',
            'opens_at' => now()->startOfWeek()->addDays(2)->setTime(14, 0),
        ]);

        $this->get(route('topics.items.show', [$topic, $evento]))
            ->assertOk()
            ->assertSee('Hospital sede Cali', false)
            ->assertSee('Oficina de Control Interno', false)
            ->assertSee($evento->startsAt()->toIso8601String(), false);
    }

    /**
     * Un evento sin fecha no se puede guardar.
     *
     * Sin ella desaparece del calendario y no queda ni un sitio donde se note:
     * el listado del tema lo sigue enseñando como si nada.
     */
    public function test_un_evento_exige_fecha_y_hora(): void
    {
        $topic = $this->agenda();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'kind' => TopicItem::KIND_EVENT,
                'title' => 'Evento sin fecha',
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['event_date', 'event_time']);

        $this->assertDatabaseCount('topic_items', 0);
    }
}
