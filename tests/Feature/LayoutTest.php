<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los listados en rejilla.
 *
 * Una rejilla estira sus celdas hasta la altura de la fila. Con el `<li>`
 * puesto a `flex` y la tarjeta a `w-full`, una noticia de dos renglones al lado
 * de otra con foto salía con el recuadro estirado y medio palmo de blanco
 * DENTRO: el borde bajaba hasta donde acababa la vecina. `items-start` deja que
 * cada tarjeta mida lo que mide su texto y el hueco quede entre tarjetas.
 *
 * Sobre eso va la mampostería —resources/js/lib/masonry.js—, que sube la
 * tarjeta de abajo al hueco que deja la corta en vez de esperar a que acabe la
 * fila, como hace el portal actual. Eso ocurre entero en el navegador; lo que
 * se comprueba aquí es su enganche: la referencia `rejilla` que el componente
 * busca para encontrar el listado.
 *
 * De la altura de una caja no puede decir nada una prueba de servidor: la
 * calcula el navegador. Estas comprobaciones sirven para que nadie retire las
 * clases ni la referencia sin enterarse, que es como llegó a perderse.
 */
class LayoutTest extends TestCase
{
    use RefreshDatabase;

    private function tema(string $slug, string $nombre): Topic
    {
        return Topic::create([
            'name' => $nombre,
            'slug' => $slug,
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);
    }

    /** El muro de contenidos de la portada. */
    public function test_el_muro_de_la_portada_no_estira_las_tarjetas(): void
    {
        Content::create([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="grid grid-cols-1 items-start gap-6"', false)
            // El enganche de la mampostería: sin la referencia, el componente
            // no encuentra la rejilla y las tarjetas dejan de subir al hueco.
            ->assertSee('x-ref="rejilla"', false);
    }

    /** El listado de un tema de artículos. */
    public function test_el_listado_de_un_tema_no_estira_las_tarjetas(): void
    {
        $topic = $this->tema('boletines', 'Boletines');

        $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Boletín de agosto',
            'slug' => 'boletin-agosto',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('class="grid grid-cols-1 items-start gap-5"', false)
            ->assertSee('x-ref="rejilla"', false);
    }

    /**
     * Los recuadros de «también en…» al pie de una ficha.
     *
     * Llevaban `h-full`, que con la rejilla estirando la fila dejaba el borde
     * del titular corto bajando hasta donde acababa el largo.
     */
    public function test_los_contenidos_relacionados_no_estiran_sus_recuadros(): void
    {
        foreach (['Jornada de donación de sangre', 'Un titular bastante más largo que el anterior para que la fila no cuadre', 'Corto'] as $i => $titulo) {
            Content::create([
                'title' => $titulo,
                'category' => Content::NEWS_CATEGORY,
                'is_active' => true,
                'show_in_feed' => true,
                'published_at' => now()->subDays($i + 1),
            ]);
        }

        $ficha = Content::create([
            'title' => 'La ficha que se está mirando',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ]);

        $html = $this->get($ficha->url())->assertOk()->getContent();

        $this->assertStringContainsString('class="grid grid-cols-1 items-start gap-4 sm:grid-cols-3"', $html);
        // `h-full` igualaba las alturas por su cuenta, así que quitar el
        // estirón de la rejilla sin quitarlo a él no habría servido de nada.
        $this->assertStringNotContainsString('block h-full rounded-[4px]', $html);
    }

    /** Y el del tema que publica contenidos, que es otra plantilla. */
    public function test_el_listado_de_contenidos_de_un_tema_no_estira_las_tarjetas(): void
    {
        $topic = $this->tema('noticias', 'Noticias');
        $topic->update(['content_category' => Content::NEWS_CATEGORY]);

        Content::create([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('class="grid grid-cols-1 items-start gap-5"', false)
            ->assertSee('x-ref="rejilla"', false);
    }
}
