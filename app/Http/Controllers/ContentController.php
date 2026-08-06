<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends Controller
{
    /**
     * Página de un contenido publicado.
     *
     * Lo inactivo, lo programado y lo oculto solo es accesible con sesión
     * iniciada: así se puede revisar antes de que salga a la luz. Para el
     * visitante no existe.
     */
    public function show(string $slug): View
    {
        $content = Content::with('media')->where('slug', $slug)->firstOrFail();

        if (! Auth::check() && ! $this->isPublic($content)) {
            throw new NotFoundHttpException;
        }

        return view('contents.show', [
            'content' => $content,
            'related' => Content::query()
                ->where('category', $content->category)
                ->whereKeyNot($content->getKey())
                ->onHome()
                ->recent()
                ->limit(3)
                ->get(),
        ]);
    }

    private function isPublic(Content $content): bool
    {
        return $content->is_active
            && ! $content->is_hidden
            && ! $content->isScheduled();
    }
}
