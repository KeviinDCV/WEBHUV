<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Configuración del bloque de la agenda.
 *
 * Los eventos ya no viven aquí: la agenda es un tema —«Calendario de
 * actividades»—, igual que en el portal, y sus eventos se crean y se editan con
 * el mismo formulario que cualquier otro contenido. Este controlador solo
 * decide qué enseña el bloque de la portada: cómo se titula, de qué tema salen
 * los eventos y qué categorías de ese tema se dejan pasar.
 */
class EventController extends Controller
{
    public function editBlock(): View
    {
        $block = ContentBlock::events();

        return view('admin.events.block', [
            'block' => $block,
            // Las del tema elegido, no una lista propia: son las mismas que se
            // ven en el listado del tema y las que la importación mantiene al
            // día. Sin tema todavía importado no hay ninguna, y el bloque se
            // configura igual.
            'categories' => $this->topicFor($block->option('source'))?->categories ?? collect(),
            'sources' => $this->sources(),
        ]);
    }

    /**
     * Los temas que pueden alimentar la agenda, con el nombre que tienen.
     *
     * El nombre sale de la base y no de la constante: son los mismos siete
     * temas que el visitante ve rotulados así en su propia página, y con la
     * constante por delante un tema renombrado se quedaba con el rótulo viejo
     * en el único sitio donde se elige. La constante queda de reserva para el
     * tema que todavía no se haya importado, que si no desaparecería de la
     * lista y no habría forma de escogerlo.
     *
     * @return array<string, string>
     */
    private function sources(): array
    {
        $nombres = Topic::whereIn('slug', array_keys(ContentBlock::EVENT_SOURCES))
            ->pluck('name', 'slug');

        return collect(ContentBlock::EVENT_SOURCES)
            ->map(fn (string $reserva, string $slug): string => $nombres[$slug] ?? $reserva)
            ->all();
    }

    public function updateBlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'source' => ['required', Rule::in(array_keys(ContentBlock::EVENT_SOURCES))],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:topic_categories,id'],
        ], [
            'source.required' => __('mensajes.validacion.seccion_calendario'),
        ], [
            'name' => __('mensajes.campo.nombre_bloque'),
            'source' => __('mensajes.campo.seccion'),
        ]);

        $block = ContentBlock::events();
        $topic = $this->topicFor($validated['source']);

        // Las categorías pertenecen a un tema. Al cambiar de tema, las que
        // hubiera elegidas son de otro sitio: filtrarían por identificadores
        // que en el tema nuevo no existen y el calendario saldría siempre
        // vacío, sin que nada explicara por qué.
        $elegidas = array_map('intval', $validated['categories'] ?? []);

        $suyas = $topic
            ? $topic->categories->pluck('id')->all()
            : [];

        $block->update([
            'name' => $validated['name'],
            'options' => [
                'source' => $validated['source'],
                'categories' => array_values(array_intersect($elegidas, $suyas)),
            ],
        ]);

        return redirect()->route('home')->with('status', __('mensajes.bloque.eventos_guardado'));
    }

    /**
     * El tema que alimenta el calendario, si ya está migrado.
     *
     * Por slug y no por nombre: hay dos temas llamados «Rendición de cuentas»
     * y el nombre lo reescribe la importación en cada pasada.
     */
    private function topicFor(?string $source): ?Topic
    {
        return $source === null ? null : Topic::with('categories')->firstWhere('slug', $source);
    }
}
