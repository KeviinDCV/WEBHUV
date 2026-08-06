<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\LibraryImage;
use App\Support\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Administración de los contenidos publicados: noticias, comunicados y
 * notificaciones judiciales.
 */
class ContentController extends Controller
{
    public function create(): View
    {
        return view('admin.contents.form', [
            'content' => new Content([
                'category' => Content::NEWS_CATEGORY,
                'show_in_feed' => true,
                'is_active' => true,
                'published_at' => now(),
            ]),
        ]);
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        $content = new Content;

        $this->fill($content, $request);
        $content->save();

        $this->syncMedia($content, $request);

        $this->settleFeatured($content);

        return redirect()->route('home')->with('status', 'Contenido publicado correctamente.');
    }

    public function edit(Content $content): View
    {
        return view('admin.contents.form', ['content' => $content]);
    }

    public function update(ContentRequest $request, Content $content): RedirectResponse
    {
        $this->fill($content, $request);
        $content->save();

        $this->syncMedia($content, $request);

        $this->settleFeatured($content);

        return redirect()->route('home')->with('status', 'Contenido actualizado correctamente.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        $content->delete();

        return redirect()->route('home')->with('status', 'Contenido eliminado.');
    }

    /* ------------------------------------------------------------------ */
    /* Acciones rápidas del menú de cada contenido                         */
    /* ------------------------------------------------------------------ */

    /** Destacar: pasa a ocupar el espacio grande del bloque de Noticias. */
    public function feature(Content $content): RedirectResponse
    {
        $content->update(['is_featured' => true]);

        $this->settleFeatured($content);

        return back()->with('status', 'Contenido destacado.');
    }

    /** Activar o inactivar: fuera de todo el sitio, no solo de la portada. */
    public function toggleActive(Content $content): RedirectResponse
    {
        $content->update(['is_active' => ! $content->is_active]);

        return back()->with(
            'status',
            $content->is_active ? 'Contenido activado.' : 'Contenido inactivado.'
        );
    }

    /** Ocultar o mostrar: sigue activo, pero fuera de la portada. */
    public function toggleHidden(Content $content): RedirectResponse
    {
        $content->update(['is_hidden' => ! $content->is_hidden]);

        return back()->with(
            'status',
            $content->is_hidden ? 'Contenido oculto en la portada.' : 'Contenido visible en la portada.'
        );
    }

    /* ------------------------------------------------------------------ */

    private function fill(Content $content, ContentRequest $request): void
    {
        $content->fill($request->safe()->only([
            'title', 'category', 'excerpt', 'link', 'is_featured', 'show_in_feed', 'participation',
        ]));

        // El cuerpo llega del editor como HTML: se depura antes de guardarlo.
        $content->body = RichText::clean($request->input('body'));

        // «Sin fecha de visualización»: se publica sin fecha visible, pero se
        // sigue ordenando por su fecha de creación. Una fecha futura lo deja
        // programado: no se muestra al público hasta que llega.
        $content->published_at = $request->boolean('no_date') ? null : $request->date('published_at');
    }

    /**
     * Sincroniza fotos, vídeo y archivos con lo que llega del formulario.
     */
    private function syncMedia(Content $content, ContentRequest $request): void
    {
        $existing = $content->media()->get()->keyBy('id');

        // 1. Bajas.
        foreach ($request->input('media_delete', []) as $id) {
            $existing->get((int) $id)?->delete();
            $existing->forget((int) $id);
        }

        // 2. Descripciones de lo que se conserva.
        foreach ($request->input('media_alt', []) as $id => $alt) {
            $existing->get((int) $id)?->update(['alt' => $alt]);
        }

        // 3. Fotos nuevas.
        $alts = $request->input('photo_alts', []);

        foreach ($request->file('photos', []) as $index => $photo) {
            $content->media()->create([
                'type' => ContentMedia::TYPE_IMAGE,
                'path' => $photo->store('contenidos', 'public'),
                'alt' => $alts[$index] ?? null,
                'original_name' => $photo->getClientOriginalName(),
                'size' => $photo->getSize(),
                'position' => $content->media()->max('position') + 1,
            ]);
        }

        // 4. Archivos nuevos.
        $titles = $request->input('file_titles', []);

        foreach ($request->file('files', []) as $index => $file) {
            $content->media()->create([
                'type' => ContentMedia::TYPE_FILE,
                'path' => $file->store('documentos', 'public'),
                'alt' => $titles[$index] ?: $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'position' => $content->media()->max('position') + 1,
            ]);
        }

        // 5. Imágenes de la biblioteca: se sincroniza el conjunto elegido.
        $this->syncLibraryImages($content, $request->input('library_ids', []));

        // 6. Vídeo: uno por contenido.
        $videoUrl = $request->input('video_url');
        $video = $content->media()->where('type', ContentMedia::TYPE_VIDEO)->first();

        if (blank($videoUrl)) {
            $video?->delete();
        } elseif ($video) {
            $video->update(['url' => $videoUrl]);
        } else {
            $content->media()->create(['type' => ContentMedia::TYPE_VIDEO, 'url' => $videoUrl]);
        }

        $this->settleMainImage($content, $request->input('media_main'));
    }

    /**
     * Vincula y desvincula imágenes de la biblioteca.
     *
     * Al desvincular solo se borra la fila de enlace: el archivo pertenece a la
     * biblioteca y puede estar en uso en otros contenidos.
     *
     * @param  list<int|string>  $chosen
     */
    private function syncLibraryImages(Content $content, array $chosen): void
    {
        $chosen = array_map('intval', $chosen);

        $linked = $content->media()->whereNotNull('library_image_id')->get();

        $linked->whereNotIn('library_image_id', $chosen)->each->delete();

        $already = $linked->pluck('library_image_id')->all();

        LibraryImage::whereIn('id', array_diff($chosen, $already))
            ->get()
            ->each(function (LibraryImage $image) use ($content): void {
                $content->media()->create([
                    'library_image_id' => $image->id,
                    'type' => ContentMedia::TYPE_IMAGE,
                    'path' => $image->path,
                    'alt' => $image->alt,
                    'original_name' => $image->original_name,
                    'size' => $image->size,
                    'position' => $content->media()->max('position') + 1,
                ]);
            });
    }

    /**
     * Marca la foto principal.
     *
     * Solo puede haber una: es la que representa al contenido en los listados.
     * Si no se eligió ninguna, se toma la primera para que las tarjetas no
     * queden sin imagen.
     */
    private function settleMainImage(Content $content, mixed $chosen): void
    {
        $images = $content->media()->where('type', ContentMedia::TYPE_IMAGE)->orderBy('position')->get();

        if ($images->isEmpty()) {
            return;
        }

        $main = $images->firstWhere('id', (int) $chosen) ?? $images->first();

        $content->media()
            ->where('type', ContentMedia::TYPE_IMAGE)
            ->update(['is_main' => false]);

        $main->update(['is_main' => true]);
    }

    /**
     * Solo puede haber una nota destacada: al marcar una, se desmarca el resto
     * de su categoría. De lo contrario el bloque tendría dos «principales» y
     * la portada elegiría una al azar.
     */
    private function settleFeatured(Content $content): void
    {
        if (! $content->is_featured) {
            return;
        }

        Content::where('category', $content->category)
            ->whereKeyNot($content->getKey())
            ->where('is_featured', true)
            ->update(['is_featured' => false]);
    }
}
