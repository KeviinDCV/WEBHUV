<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shortcut;
use App\Models\ShortcutBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Barras de accesos directos de la portada y sus accesos.
 */
class ShortcutController extends Controller
{
    /** Pantalla de una barra: nombre, listado ordenable y tema. */
    public function edit(ShortcutBlock $block): View
    {
        return view('admin.shortcuts.block', ['block' => $block->load('shortcuts')]);
    }

    /** Guarda el nombre, el orden de los accesos y el tema. */
    public function update(Request $request, ShortcutBlock $block): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'theme' => ['required', Rule::in(array_keys(ShortcutBlock::THEMES))],
            'order' => ['array'],
            'order.*' => ['integer', Rule::exists('shortcuts', 'id')->where('shortcut_block_id', $block->id)],
        ], [], [
            'name' => 'nombre del bloque',
            'theme' => 'tema',
        ]);

        $block->update(['name' => $validated['name'], 'theme' => $validated['theme']]);

        foreach ($validated['order'] ?? [] as $position => $id) {
            Shortcut::whereKey($id)->update(['position' => $position + 1]);
        }

        return redirect()->route('home')->with('status', 'Barra de accesos guardada.');
    }

    /* ------------------------------------------------------------------ */
    /* Accesos                                                             */
    /* ------------------------------------------------------------------ */

    public function create(ShortcutBlock $block): View
    {
        $this->ensureThereIsRoom($block);

        return view('admin.shortcuts.form', [
            'block' => $block,
            'shortcut' => new Shortcut(['icon' => 'info']),
        ]);
    }

    public function store(Request $request, ShortcutBlock $block): RedirectResponse
    {
        $this->ensureThereIsRoom($block);

        $block->shortcuts()->create($this->validated($request) + [
            'position' => (int) $block->shortcuts()->max('position') + 1,
        ]);

        return redirect()
            ->route('admin.shortcuts.edit', $block)
            ->with('status', 'Acceso directo agregado.');
    }

    public function editShortcut(ShortcutBlock $block, Shortcut $shortcut): View
    {
        return view('admin.shortcuts.form', ['block' => $block, 'shortcut' => $shortcut]);
    }

    public function updateShortcut(Request $request, ShortcutBlock $block, Shortcut $shortcut): RedirectResponse
    {
        $shortcut->update($this->validated($request));

        return redirect()
            ->route('admin.shortcuts.edit', $block)
            ->with('status', 'Acceso directo actualizado.');
    }

    public function destroyShortcut(ShortcutBlock $block, Shortcut $shortcut): RedirectResponse
    {
        $shortcut->delete();

        return redirect()
            ->route('admin.shortcuts.edit', $block)
            ->with('status', 'Acceso directo eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'icon' => ['required', Rule::in(array_keys(Shortcut::ICONS))],
            // Se admite una dirección completa o una ruta que empiece por «/»:
            // esta última se sirve del portal actual hasta que la sección
            // exista aquí.
            'url' => ['required', 'string', 'max:2048', 'regex:~^(https?://|/)~'],
        ], [
            'url.regex' => 'Escriba una dirección completa (con http:// o https://) o una ruta '
                .'del portal que empiece por «/».',
        ], [
            'label' => 'nombre',
            'icon' => 'icono',
            'url' => 'enlace',
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function ensureThereIsRoom(ShortcutBlock $block): void
    {
        if (! $block->hasRoom()) {
            throw ValidationException::withMessages([
                'shortcut' => 'Esta barra ya tiene '.ShortcutBlock::MAX_SHORTCUTS
                    .' accesos, el máximo permitido.',
            ]);
        }
    }
}
