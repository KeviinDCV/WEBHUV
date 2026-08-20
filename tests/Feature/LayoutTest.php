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
 * Aquí solo se puede comprobar que la clase sale en el HTML: la altura de una
 * caja la calcula el navegador, no el servidor. Sirve para que nadie la retire
 * sin enterarse, que es como llegó a perderse.
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
            ->assertSee('class="grid grid-cols-1 items-start gap-6"', false);
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
            ->assertSee('class="grid grid-cols-1 items-start gap-5"', false);
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
            ->assertSee('class="grid grid-cols-1 items-start gap-5"', false);
    }
}
