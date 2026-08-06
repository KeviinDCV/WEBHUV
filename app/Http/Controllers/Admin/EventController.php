<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Agenda institucional: los eventos y la configuración de su bloque.
 */
class EventController extends Controller
{
    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event(['starts_at' => now()->addDay()->setTime(8, 0)]),
            'categories' => EventCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->validated($request));
        $event->categories()->sync($request->input('categories', []));

        return redirect()->route('home')->with('status', 'Evento creado.');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event->load('categories'),
            'categories' => EventCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->validated($request));
        $event->categories()->sync($request->input('categories', []));

        return redirect()->route('home')->with('status', 'Evento actualizado.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('home')->with('status', 'Evento eliminado.');
    }

    /* ------------------------------------------------------------------ */
    /* Configuración del bloque                                            */
    /* ------------------------------------------------------------------ */

    public function editBlock(): View
    {
        return view('admin.events.block', [
            'block' => ContentBlock::events(),
            'categories' => EventCategory::orderBy('name')->get(),
        ]);
    }

    public function updateBlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'source' => ['required', Rule::in(ContentBlock::EVENT_SOURCES)],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:event_categories,id'],
        ], [
            'source.required' => 'Elija la sección que alimenta el calendario.',
        ], [
            'name' => 'nombre del bloque',
            'source' => 'sección',
        ]);

        ContentBlock::events()->update([
            'name' => $validated['name'],
            'options' => [
                'source' => $validated['source'],
                'categories' => array_map('intval', $validated['categories'] ?? []),
            ],
        ]);

        return redirect()->route('home')->with('status', 'Bloque de eventos guardado.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('event_categories', 'name')],
        ], [
            'name.unique' => 'Ya existe una categoría con ese nombre.',
        ], ['name' => 'nombre de la categoría']);

        EventCategory::create($validated);

        return back()->with('status', 'Categoría creada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'starts_at' => ['required', 'date'],
            // Un evento que termina antes de empezar no se pintaría en ningún
            // día del calendario.
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'place' => ['nullable', 'string', 'max:200'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ], [
            'ends_at.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'url.url' => 'El enlace debe empezar por http:// o https://',
        ], [
            'title' => 'título',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'place' => 'lugar',
            'url' => 'enlace',
            'description' => 'descripción',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
