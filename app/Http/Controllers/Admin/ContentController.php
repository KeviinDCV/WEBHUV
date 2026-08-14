<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Content;
use App\Support\CommentWall;
use App\Support\MediaSync;
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

        // Se vuelve al muro, que es donde se comprueba el resultado.
        return $this->backToFeed('Contenido publicado correctamente.');
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

        return $this->backToFeed('Contenido actualizado correctamente.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        $content->delete();

        return $this->backToFeed('Contenido eliminado.');
    }

    /** Vuelve al muro de contenidos de la portada, no al principio. */
    private function backToFeed(string $status): RedirectResponse
    {
        return redirect()->to(route('home').'#huv-contenidos')->with('status', $status);
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
            'title', 'category', 'excerpt', 'link', 'is_featured', 'show_in_feed',
        ]));

        // Sin `filled()` esto tendría una trampa: si el campo llega vacío, el
        // middleware lo convierte en null, `input()` devuelve null en lugar del
        // valor por defecto —la clave existe— y `(int) null` es cero, que
        // justamente significa «participación pública».
        $content->comment_wall = filled($request->input('comment_wall'))
            ? (int) $request->input('comment_wall')
            : CommentWall::NINGUNA;

        // El cuerpo llega del editor como HTML: se depura antes de guardarlo.
        $content->body = RichText::clean($request->input('body'));

        // La ficha muestra «Modificación: …», y no puede ser `updated_at`
        // porque una reimportación lo sobreescribiría. Igual que en el editor
        // de un tema: sin esta línea, corregir a mano una noticia importada
        // dejaba en pantalla la fecha en que la tocó el portal, meses antes que
        // el texto que se está leyendo.
        $content->modified_at = now();

        // «Sin fecha de visualización»: se publica sin fecha visible, pero se
        // sigue ordenando por su fecha de creación. Una fecha futura lo deja
        // programado: no se muestra al público hasta que llega.
        $content->published_at = $request->boolean('no_date') ? null : $request->date('published_at');

        // Fecha final de visualización: pasada esa fecha deja de mostrarse.
        $content->expires_at = $request->boolean('no_end_date') ? null : $request->date('expires_at');
    }

    /**
     * Sincroniza fotos, vídeo y archivos con lo que llega del formulario.
     *
     * El trabajo vive en App\Support\MediaSync porque el mismo bloque del
     * editor se usa en los artículos de un tema.
     */
    private function syncMedia(Content $content, ContentRequest $request): void
    {
        (new MediaSync($content, 'contenidos'))->apply($request);
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
