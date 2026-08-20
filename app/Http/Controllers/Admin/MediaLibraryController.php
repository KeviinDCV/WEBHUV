<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryImage;
use App\Models\MediaCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Biblioteca de imágenes: se suben una vez y se reutilizan en varios
 * contenidos, agrupadas por categorías.
 */
class MediaLibraryController extends Controller
{
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('media_categories', 'name')],
        ], [
            'name.unique' => __('mensajes.validacion.categoria_repetida'),
        ], [
            'name' => __('mensajes.campo.nombre_categoria'),
        ]);

        MediaCategory::create($validated);

        return back()->with('status', __('mensajes.biblioteca.categoria_creada'));
    }

    public function storeImage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:2048'],
            // Obligatoria: la imagen se reutilizará en varios contenidos y
            // arrastrará esta descripción a todos (WCAG 1.1.1).
            'alt' => ['required', 'string', 'max:250'],
            'media_category_id' => ['nullable', 'exists:media_categories,id'],
        ], [
            'image.max' => __('mensajes.validacion.imagen_pesada'),
            'alt.required' => __('mensajes.validacion.imagen_alt'),
        ], [
            'image' => __('mensajes.campo.imagen'),
            'alt' => __('mensajes.campo.descripcion'),
            'media_category_id' => __('mensajes.campo.categoria'),
        ]);

        LibraryImage::create([
            'media_category_id' => $validated['media_category_id'] ?? null,
            'path' => $request->file('image')->store('biblioteca', 'public'),
            'alt' => $validated['alt'],
            'original_name' => $request->file('image')->getClientOriginalName(),
            'size' => $request->file('image')->getSize(),
        ]);

        return back()->with('status', __('mensajes.biblioteca.imagen_agregada'));
    }

    public function destroyImage(LibraryImage $image): RedirectResponse
    {
        // Los `content_media` que la referencian caen en cascada, así que los
        // contenidos que la usaban dejan de mostrarla en lugar de romperse.
        $image->delete();

        return back()->with('status', __('mensajes.biblioteca.imagen_eliminada'));
    }
}
