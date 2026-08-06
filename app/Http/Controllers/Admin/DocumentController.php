<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentRequest;
use App\Models\Document;
use App\Models\Topic;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Administración de los documentos de un tema.
 *
 * No tiene pantallas propias: el alta y la edición ocurren en el propio listado
 * del tema, sin salir de él, igual que en el muro de contenidos de la portada.
 */
class DocumentController extends Controller
{
    public function store(DocumentRequest $request, Topic $topic): RedirectResponse
    {
        $document = new Document(['topic_id' => $topic->id]);

        $this->fill($document, $request, $topic);
        $document->save();

        $this->storeFile($document, $request);

        return $this->backToTopic($topic, 'Documento publicado correctamente.');
    }

    public function update(DocumentRequest $request, Topic $topic, Document $document): RedirectResponse
    {
        abort_unless($document->topic_id === $topic->id, 404);

        $this->fill($document, $request, $topic);
        $document->save();

        $this->storeFile($document, $request);

        return $this->backToTopic($topic, 'Documento actualizado correctamente.');
    }

    public function destroy(Topic $topic, Document $document): RedirectResponse
    {
        abort_unless($document->topic_id === $topic->id, 404);

        $document->delete();

        return $this->backToTopic($topic, 'Documento eliminado.');
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
    /* Acciones rápidas del lápiz de cada documento                        */
    /* ------------------------------------------------------------------ */

    public function feature(Topic $topic, Document $document): RedirectResponse
    {
        abort_unless($document->topic_id === $topic->id, 404);

        $document->update(['is_featured' => true]);

        return back()->with('status', 'Documento destacado.');
    }

    public function toggleActive(Topic $topic, Document $document): RedirectResponse
    {
        abort_unless($document->topic_id === $topic->id, 404);

        $document->update(['is_active' => ! $document->is_active]);

        return back()->with(
            'status',
            $document->is_active ? 'Documento activado.' : 'Documento inactivado.'
        );
    }

    public function toggleHidden(Topic $topic, Document $document): RedirectResponse
    {
        abort_unless($document->topic_id === $topic->id, 404);

        $document->update(['is_hidden' => ! $document->is_hidden]);

        return back()->with(
            'status',
            $document->is_hidden ? 'Documento oculto en el listado.' : 'Documento visible en el listado.'
        );
    }

    /* ------------------------------------------------------------------ */

    private function backToTopic(Topic $topic, string $status): RedirectResponse
    {
        return redirect()
            ->to(route('topics.show', $topic).'#huv-documentos')
            ->with('status', $status);
    }

    private function fill(Document $document, DocumentRequest $request, Topic $topic): void
    {
        $document->fill($request->safe()->only(['title', 'issued_at', 'is_featured']));

        $document->description = RichText::clean($request->input('description'));
        $document->topic_category_id = $this->categoryId($request, $topic);

        // La casilla «Mostrar en muro de contenidos» es la cara amable de
        // `is_hidden`: lo que no se muestra queda bajo la pestaña «Ocultos».
        $document->is_hidden = ! $request->boolean('show_in_feed');

        // «Programar» adelanta la fecha de publicación; sin ella, se publica ya.
        $document->published_at = $request->date('published_at') ?? $document->published_at ?? now();

        // Un documento que solo vive fuera del portal se enlaza directamente:
        // la ficha sigue existiendo aquí, con su descripción y su categoría.
        // En uno ya descargado, `source_url` solo deja constancia de su origen
        // y no se toca si no se escribe un enlace nuevo.
        if (! $document->isDownloaded() || $request->filled('link')) {
            $document->source_url = $request->input('link');
        }
    }

    /** Categoría elegida, o la que se acaba de escribir en el formulario. */
    private function categoryId(DocumentRequest $request, Topic $topic): ?int
    {
        if ($request->filled('new_category')) {
            $name = trim($request->string('new_category')->toString());

            return $topic->categories()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        }

        return $request->integer('topic_category_id') ?: null;
    }

    private function storeFile(Document $document, DocumentRequest $request): void
    {
        if (! $file = $request->file('file')) {
            // Solo se toca la descripción del archivo que ya estuviera puesto.
            if ($request->filled('file_alt') && $document->isDownloaded()) {
                $document->update(['file_name' => $request->input('file_alt')]);
            }

            return;
        }

        // El archivo anterior se borra: dejarlo huérfano en disco solo ocupa.
        $document->deleteFile();

        $document->update([
            'file_path' => $file->store('documentos/'.$document->topic_id, 'public'),
            'file_name' => $request->input('file_alt') ?: $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_extension' => strtolower($file->getClientOriginalExtension()),
        ]);
    }
}
