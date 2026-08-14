<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TopicItemRequest;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Support\CommentWall;
use App\Support\MediaSync;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Administración de los elementos de un tema: documentos y artículos.
 *
 * No tiene pantallas propias: el alta y la edición ocurren en el propio listado
 * del tema, sin salir de él, igual que en el muro de contenidos de la portada.
 */
class TopicItemController extends Controller
{
    public function store(TopicItemRequest $request, Topic $topic): RedirectResponse
    {
        $item = new TopicItem(['topic_id' => $topic->id]);

        $this->fill($item, $request, $topic);
        $item->save();

        $this->syncCategories($item, $request, $topic);
        $this->syncMedia($item, $request, $topic);

        return $this->backToTopic($topic, 'Contenido publicado correctamente.');
    }

    public function update(TopicItemRequest $request, Topic $topic, TopicItem $item): RedirectResponse
    {
        $this->fill($item, $request, $topic);
        $item->save();

        $this->syncCategories($item, $request, $topic);
        $this->syncMedia($item, $request, $topic);

        return $this->backToTopic($topic, 'Contenido actualizado correctamente.');
    }

    public function destroy(Topic $topic, TopicItem $item): RedirectResponse
    {
        $item->delete();

        return $this->backToTopic($topic, 'Contenido eliminado.');
    }

    /** Categoría nueva creada desde «Agregar categoría». */
    public function storeCategory(Topic $topic): RedirectResponse
    {
        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
        ], [], ['name' => 'nombre de la categoría']);

        $topic->categories()->firstOrCreate(
            ['slug' => Str::slug($data['name'])],
            ['name' => $data['name']]
        );

        return back()->with('status', 'Categoría creada.');
    }

    /* ------------------------------------------------------------------ */
    /* Acciones rápidas del lápiz de cada ficha                            */
    /* ------------------------------------------------------------------ */

    /**
     * Destacar.
     *
     * Solo puede haber uno destacado por TEMA, y se acota por `topic_id`: así
     * destacar algo aquí no puede tocar la noticia principal de la portada.
     */
    public function feature(Topic $topic, TopicItem $item): RedirectResponse
    {
        $item->update(['is_featured' => true]);

        $topic->items()->whereKeyNot($item->getKey())->update(['is_featured' => false]);

        return back()->with('status', 'Contenido destacado.');
    }

    public function toggleActive(Topic $topic, TopicItem $item): RedirectResponse
    {
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with(
            'status',
            $item->is_active ? 'Contenido activado.' : 'Contenido inactivado.'
        );
    }

    public function toggleHidden(Topic $topic, TopicItem $item): RedirectResponse
    {
        $item->update(['is_hidden' => ! $item->is_hidden]);

        return back()->with(
            'status',
            $item->is_hidden ? 'Contenido oculto en el listado.' : 'Contenido visible en el listado.'
        );
    }

    /* ------------------------------------------------------------------ */

    private function backToTopic(Topic $topic, string $status): RedirectResponse
    {
        return redirect()
            ->to(route('topics.show', $topic).'#huv-listado')
            ->with('status', $status);
    }

    private function fill(TopicItem $item, TopicItemRequest $request, Topic $topic): void
    {
        $item->fill($request->safe()->only(['title', 'is_featured']));

        // El tipo se fija al crear y no se cambia después: mover un documento a
        // artículo dejaría su archivo huérfano.
        $item->kind = $item->exists ? $item->kind : $request->kind();

        $item->body = RichText::clean($request->input('body'));

        // La casilla «Mostrar en muro de contenidos» es la cara amable de
        // `is_hidden`: lo que no se muestra queda bajo la pestaña «Ocultos».
        $item->is_hidden = ! $request->boolean('show_in_feed');

        // «Programar» adelanta la fecha de publicación; sin ella, se publica ya.
        $item->published_at = $request->date('published_at') ?? $item->published_at ?? now();

        // La ficha muestra «Modificación: …», y no puede ser `updated_at`
        // porque una reimportación lo sobreescribiría.
        $item->modified_at = now();

        if ($item->isDocument()) {
            $item->issued_at = $request->date('issued_at');

            // Un documento que solo vive fuera del portal se enlaza
            // directamente. En uno ya descargado, `source_url` solo deja
            // constancia de su origen y no se toca sin un enlace nuevo.
            if (! $item->isDownloaded() || $request->filled('link')) {
                $item->source_url = $request->input('link');
            }

            return;
        }

        // Un enlace guarda su destino en el mismo campo que el documento: es el
        // mismo dato, «dónde está esto de verdad».
        if ($item->isLink()) {
            $item->source_url = $request->input('link');
        }

        // La convocatoria abre y cierra, y cerrada se sigue leyendo: sus fechas
        // van a columnas propias y nunca a `expires_at`, que retiraría del
        // listado un proceso que el portal sigue publicando.
        if ($item->isConvocation()) {
            $item->opens_at = $request->date('opens_at');
            $item->closes_at = $request->date('closes_at');
            $item->expires_at = null;
            $item->comment_wall = $this->commentWall($request);

            return;
        }

        $item->expires_at = $request->boolean('no_end_date') ? null : $request->date('expires_at');
        $item->comment_wall = $this->commentWall($request);
    }

    /**
     * Sin `filled()` esto tendría una trampa: si el campo llega vacío, el
     * middleware lo convierte en null, `input()` devuelve null en lugar del
     * valor por defecto —la clave existe— y `(int) null` es cero, que
     * justamente significa «participación pública».
     */
    private function commentWall(TopicItemRequest $request): int
    {
        return filled($request->input('comment_wall'))
            ? (int) $request->input('comment_wall')
            : CommentWall::NINGUNA;
    }

    private function syncCategories(TopicItem $item, TopicItemRequest $request, Topic $topic): void
    {
        $ids = array_map('intval', $request->input('topic_category_ids', []));

        if ($request->filled('new_category')) {
            $name = trim($request->string('new_category')->toString());

            $ids[] = $topic->categories()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        }

        $item->categories()->sync(array_unique($ids));
    }

    /**
     * Fotos, vídeo y archivos.
     *
     * Los lleva el artículo, la convocatoria y también el documento: el portal
     * publica hasta veinticinco archivos en un mismo documento, y hasta ahora
     * el editor solo dejaba subir uno.
     *
     * El documento conserva además su archivo principal en columnas propias
     * —es el que da el icono y el peso a la tarjeta del listado—, así que sigue
     * teniendo su control de reemplazo. Los demás son medios como los del
     * artículo, y la ficha los publica todos en una sola lista.
     */
    private function syncMedia(TopicItem $item, TopicItemRequest $request, Topic $topic): void
    {
        if ($item->isArticle() || $item->isConvocation() || $item->isDocument()) {
            // Sin galería salvo en el artículo: la ficha de un documento y la
            // de una convocatoria son texto y archivos, sin imagen ni vídeo.
            (new MediaSync($item, 'temas/'.$topic->id, gallery: $item->isArticle() || $item->isEvent()))->apply($request);
        }

        if (! $item->isDocument()) {
            return;
        }

        if (! $file = $request->file('file')) {
            // Solo se toca la descripción del archivo que ya estuviera puesto.
            if ($request->filled('file_alt') && $item->isDownloaded()) {
                $item->update(['file_name' => $request->input('file_alt')]);
            }

            return;
        }

        // El archivo anterior se borra: dejarlo huérfano en disco solo ocupa.
        $item->deleteFile();

        $item->update([
            // Se suelta la dirección de origen, salvo que se haya escrito un
            // enlace nuevo a mano. Sin esto, una reimportación devolvía nombre,
            // peso y extensión a los del portal y dejaba en disco el archivo
            // que había subido quien edita: la ficha anunciaba una cosa y
            // entregaba otra. Ahora el origen ve que el archivo cambió y vuelve
            // a traer el suyo, que es lo que significa reimportar.
            'source_url' => $request->filled('link') ? $item->source_url : null,
            'file_path' => $file->store('documentos/'.$topic->id, 'public'),
            'file_name' => $request->input('file_alt') ?: $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_extension' => strtolower($file->getClientOriginalExtension()),
        ]);
    }
}
