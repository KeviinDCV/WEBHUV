<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BannerController;
use App\Models\Banner;
use App\Models\Content;
use App\Models\Setting;
use App\Support\EventCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Página de inicio institucional.
     *
     * Los banners y los contenidos vienen de la base de datos; el resto de
     * secciones sigue en config/huv.php hasta que se administren.
     */
    public function index(Request $request): View
    {
        $news = $this->news();

        return view('home', [
            'banners' => Banner::active()->ordered()->get(),
            'rotation' => (int) Setting::get(BannerController::ROTATION_KEY, 10),

            'featured' => $news['featured'],
            'items' => $news['items'],
            'feed' => $this->feed(),

            'calendar' => EventCalendar::make(
                config('huv.events.items'),
                $request->query('vista'),
                $request->query('periodo', 0),
            ),
        ]);
    }

    /**
     * Bloque de Noticias: la nota destacada y los titulares que la acompañan.
     *
     * @return array{featured: ?Content, items: \Illuminate\Support\Collection<int, Content>}
     */
    private function news(): array
    {
        $query = Content::query()->with('media')->news()->tap($this->visibility(...))->recent();

        $featured = (clone $query)->where('is_featured', true)->first();

        $items = $query
            ->when($featured, fn (Builder $q) => $q->whereKeyNot($featured->getKey()))
            ->limit(Content::NEWS_SIDEBAR_LIMIT)
            ->get();

        // Sin nota destacada explícita, la más reciente ocupa ese lugar.
        if (! $featured && $items->isNotEmpty()) {
            $featured = $items->shift();
        }

        return ['featured' => $featured, 'items' => $items];
    }

    /** Muro de contenidos: todo lo publicado, sea de la categoría que sea. */
    private function feed(): \Illuminate\Support\Collection
    {
        return Content::query()
            ->with('media')
            ->where('show_in_feed', true)
            ->tap($this->visibility(...))
            ->recent()
            ->get();
    }

    /**
     * Quien administra ve también lo inactivo y lo oculto —marcado como tal—
     * para poder revertirlo desde la propia portada. El visitante solo ve lo
     * que está publicado.
     */
    private function visibility(Builder $query): void
    {
        if (! Auth::check()) {
            $query->onHome();
        }
    }
}
