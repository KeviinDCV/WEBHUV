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
        ]);
    }

    public function updateBlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'source' => ['required', Rule::in(ContentBlock::EVENT_SOURCES)],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:topic_categories,id'],
        ], [
            'source.required' => 'Elija la sección que alimenta el calendario.',
        ], [
            'name' => 'nombre del bloque',
            'source' => 'sección',
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

        return redirect()->route('home')->with('status', 'Bloque de eventos guardado.');
    }

    /** El tema que alimenta el calendario, si ya está migrado. */
    private function topicFor(?string $source): ?Topic
    {
        return $source === null ? null : Topic::with('categories')->firstWhere('name', $source);
    }
}
