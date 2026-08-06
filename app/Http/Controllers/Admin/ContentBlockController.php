<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentBlock;
use App\Support\Themes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Configuración de un bloque de contenidos de la portada: de qué categorías se
 * nutre, con qué rótulo, en qué orden y con qué color.
 */
class ContentBlockController extends Controller
{
    public function edit(ContentBlock $block): View
    {
        return view('admin.blocks.form', ['block' => $block->load('sections')]);
    }

    public function update(Request $request, ContentBlock $block): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'sections_count' => ['required', 'integer', 'between:1,'.ContentBlock::MAX_SECTIONS],
            'sort' => ['required', Rule::in(array_keys(ContentBlock::SORTS))],
            'show_title' => ['boolean'],
            'theme' => ['required', Rule::in(Themes::keys())],

            'sections' => ['required', 'array', 'min:1', 'max:'.ContentBlock::MAX_SECTIONS],
            'sections.*.category' => ['required', Rule::in(Content::CATEGORIES)],
            'sections.*.title' => ['required', 'string', 'max:150'],
            'sections.*.hide_in_feed' => ['boolean'],
        ], [
            'sections.*.category.required' => 'Elija la sección que alimenta el bloque.',
            'sections.*.title.required' => 'Cada sección necesita el título con el que se muestra.',
        ], [
            'name' => 'nombre del bloque',
            'sections_count' => 'número de secciones',
            'sort' => 'orden de los contenidos',
            'theme' => 'tema',
        ]);

        $block->update([
            'name' => $validated['name'],
            'sort' => $validated['sort'],
            'show_title' => $request->boolean('show_title'),
            'theme' => $validated['theme'],
        ]);

        // Se reescriben las secciones: son pocas y así el orden queda siempre
        // como lo dejó el formulario, sin conciliar altas y bajas.
        $block->sections()->delete();

        foreach (array_slice($validated['sections'], 0, (int) $validated['sections_count']) as $index => $section) {
            $block->sections()->create([
                'category' => $section['category'],
                'title' => $section['title'],
                'hide_in_feed' => (bool) ($section['hide_in_feed'] ?? false),
                'position' => $index + 1,
            ]);
        }

        return redirect()->route('home')->with('status', 'Bloque de contenidos guardado.');
    }
}
