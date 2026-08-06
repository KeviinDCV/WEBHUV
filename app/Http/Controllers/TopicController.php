<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TopicController extends Controller
{
    /**
     * Listado de un tema documental: Presupuesto, Planes, Contratación…
     *
     * Los documentos se imprimen todos y el filtrado ocurre en el navegador
     * —igual que en el muro de contenidos—, así que hay que traerlos completos.
     * Un tema tiene decenas de documentos, no miles: si alguno crece, se pasa a
     * paginación en el servidor.
     */
    public function show(Topic $topic): View
    {
        $documents = $topic->documents()
            ->with('category')
            // Sin sesión iniciada, lo inactivo y lo oculto no existe.
            ->unless(Auth::check(), fn ($query) => $query->visible())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            // El tema ya está cargado: sin esto, cada ficha lo volvería a
            // consultar al construir su dirección.
            ->each->setRelation('topic', $topic);

        return view('topics.show', [
            'topic' => $topic,
            'documents' => $documents,
            // Los recuentos se cuentan sobre lo que se muestra, no sobre la
            // tabla entera: si no, un visitante vería «(13)» en una categoría
            // con documentos inactivos y encontraría menos al pulsarla.
            'categories' => $topic->categories()
                ->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $documents->where('topic_category_id', $category->id)->count(),
                ])
                ->filter(fn (array $category) => $category['count'] > 0)
                ->sortByDesc('count')
                ->values(),
            'editing' => $this->editing($topic),
        ]);
    }

    /** Ficha de un documento. */
    public function showDocument(Topic $topic, Document $document): View
    {
        abort_unless($document->topic_id === $topic->id, 404);

        if (! Auth::check() && ! $document->isPublic()) {
            throw new NotFoundHttpException;
        }

        return view('documents.show', [
            'topic' => $topic,
            'document' => $document,
            'related' => $topic->documents()
                ->visible()
                ->whereKeyNot($document->getKey())
                ->when($document->topic_category_id, fn ($query) => $query
                    ->where('topic_category_id', $document->topic_category_id))
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each->setRelation('topic', $topic),
        ]);
    }

    /**
     * Documento que se está editando, si se llegó desde el lápiz de una ficha.
     */
    private function editing(Topic $topic): ?Document
    {
        if (! Auth::check() || ! request()->filled('editar')) {
            return null;
        }

        return $topic->documents()->find(request()->integer('editar'));
    }
}
