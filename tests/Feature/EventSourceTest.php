<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * De qué tema salen los eventos de la agenda.
 *
 * Esta prueba nace de un fallo que costó encontrar. La fila del bloque guardaba
 * «Calendario de actividades» —el NOMBRE del tema— y la agenda busca por slug,
 * así que no encontraba nada y el calendario salía vacío: 141 eventos
 * importados y ni uno en pantalla, sin un error, sin un aviso, sin nada.
 *
 * El formulario y su validación son correctos y solo guardan slugs, de modo que
 * ese valor entró por fuera de la aplicación —lo más probable, al restaurar la
 * base—. Lo que se arregla aquí no es el formulario: es que un valor
 * inesperado no vuelva a traducirse en una sección vacía y muda.
 */
class EventSourceTest extends TestCase
{
    use RefreshDatabase;

    private function agenda(): Topic
    {
        return Topic::create([
            'name' => 'Calendario de actividades',
            'slug' => ContentBlock::DEFAULT_EVENT_SOURCE,
            'legacy_content_types' => ['Event'],
            'imported_at' => now(),
        ]);
    }

    private function evento(Topic $topic, string $titulo = 'Foro de salud mental'): TopicItem
    {
        return $topic->items()->create([
            'kind' => TopicItem::KIND_EVENT,
            'title' => $titulo,
            'slug' => str($titulo)->slug()->toString(),
            'published_at' => now()->subDay(),
            'opens_at' => now()->addDays(3)->setTime(14, 0),
            'event_location' => 'Virtual',
        ]);
    }

    private function conOrigen(mixed $source): ContentBlock
    {
        $bloque = ContentBlock::events();
        $bloque->update(['options' => ['source' => $source, 'categories' => []]]);

        return $bloque->fresh();
    }

    /* ------------------------------------------------------------------ */

    /** Lo normal: el slug guardado es el que manda. */
    public function test_el_slug_guardado_es_el_que_manda(): void
    {
        $this->assertSame(
            'rendicion-de-cuentas',
            $this->conOrigen('rendicion-de-cuentas')->eventSource()
        );
    }

    /**
     * Y si lo guardado es el NOMBRE del tema, se traduce a su slug.
     *
     * Es el caso que rompió la agenda de verdad.
     */
    public function test_el_nombre_del_tema_se_traduce_a_su_slug(): void
    {
        $this->assertSame(
            ContentBlock::DEFAULT_EVENT_SOURCE,
            $this->conOrigen('Calendario de actividades')->eventSource()
        );

        $this->assertSame(
            'rendicion-de-cuentas',
            $this->conOrigen('Rendición de cuentas')->eventSource()
        );
    }

    /** Cualquier otra cosa cae al tema por omisión, no a la nada. */
    public function test_un_valor_irreconocible_cae_al_tema_por_omision(): void
    {
        foreach (['tema-que-no-existe', '', null, 42, ['a']] as $basura) {
            $this->assertSame(
                ContentBlock::DEFAULT_EVENT_SOURCE,
                $this->conOrigen($basura)->eventSource(),
                'Con '.var_export($basura, true).' debería caer al tema por omisión.'
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Y lo que de verdad importa: que se vean                             */
    /* ------------------------------------------------------------------ */

    /**
     * Con el nombre en vez del slug, la agenda SIGUE pintando sus eventos.
     *
     * Sin la traducción, esta misma portada salía con el calendario vacío.
     */
    public function test_la_agenda_se_pinta_aunque_el_origen_este_guardado_por_su_nombre(): void
    {
        $tema = $this->agenda();
        $this->evento($tema, 'Foro de salud mental');

        $this->conOrigen('Calendario de actividades');

        $this->get('/')->assertOk()->assertSee('Foro de salud mental', false);
    }

    /** Y con el slug, igual: la traducción no rompe el camino normal. */
    public function test_la_agenda_se_pinta_con_el_slug_de_siempre(): void
    {
        $tema = $this->agenda();
        $this->evento($tema, 'Jornada de donacion de sangre');

        $this->conOrigen(ContentBlock::DEFAULT_EVENT_SOURCE);

        $this->get('/')->assertOk()->assertSee('Jornada de donacion de sangre', false);
    }
}
