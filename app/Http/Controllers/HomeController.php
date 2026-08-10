<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BannerController;
use App\Models\Banner;
use App\Models\Content;
use App\Models\ContentBlock;
use App\Models\ContentBlockSection;
use App\Models\Event;
use App\Models\Setting;
use App\Models\ShortcutBlock;
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
        return view('home', [
            'banners' => Banner::active()->ordered()->get(),
            'rotation' => (int) Setting::get(BannerController::ROTATION_KEY, 10),

            'newsBlock' => $this->news(),
            'feed' => $this->feed(),
            'shortcutBlocks' => $this->shortcutBlocks(),

            // ?editar=N abre el editor incrustado con ese contenido cargado,
            // sin salir de la portada.
            'editing' => Auth::check() && $request->filled('editar')
                ? Content::with('media')->find($request->query('editar'))
                : null,

            'eventsBlock' => ContentBlock::events(),
            'calendar' => EventCalendar::make(
                $this->events(),
                $request->query('vista'),
                $request->query('periodo', 0),
            ),
        ]);
    }

    /**
     * Bloque de Noticias, tal como esté configurado: sus secciones, el rótulo
     * de cada una y el orden elegido.
     *
     * @return array{block: ContentBlock, groups: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    private function news(): array
    {
        $block = ContentBlock::news();

        $groups = $block->sections->map(function ($section) use ($block): array {
            $group = $this->groupFor($section->category, $block->isDescending());

            return $group + ['title' => $section->title];
        });

        return ['block' => $block, 'groups' => $groups];
    }

    /**
     * Nota destacada y titulares de una categoría.
     *
     * @return array{featured: ?Content, items: \Illuminate\Support\Collection<int, Content>}
     */
    private function groupFor(string $category, bool $descending): array
    {
        $query = Content::query()
            ->with('media')
            ->where('category', $category)
            ->tap($this->visibility(...))
            ->recent($descending);

        $featured = (clone $query)->where('is_featured', true)->first();

        // Sin nota destacada explícita la encabeza la más reciente, así que hay
        // que pedir una de más: si no, la columna se quedaría con un titular
        // menos que cuando sí hay destacada.
        $items = $query
            ->when($featured, fn (Builder $q) => $q->whereKeyNot($featured->getKey()))
            ->limit(Content::NEWS_SIDEBAR_LIMIT + ($featured ? 0 : 1))
            ->get();

        if (! $featured && $items->isNotEmpty()) {
            $featured = $items->shift();
        }

        return ['featured' => $featured, 'items' => $items];
    }

    /**
     * Agenda, filtrada por las categorías que tenga elegidas el bloque.
     *
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function events(): \Illuminate\Support\Collection
    {
        $block = ContentBlock::events();

        return Event::query()
            ->when(! Auth::check(), fn (Builder $q) => $q->active())
            ->inCategories($block->option('categories', []))
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Barras de accesos directos.
     *
     * Una barra con menos de tres accesos no se publica: la rejilla queda
     * descompensada y no comunica que es una barra de atajos. Quien administra
     * sí las ve, marcadas, para poder completarlas.
     *
     * @return \Illuminate\Support\Collection<int, ShortcutBlock>
     */
    private function shortcutBlocks(): \Illuminate\Support\Collection
    {
        return ShortcutBlock::with('shortcuts')
            ->ordered()
            ->get()
            ->filter(fn (ShortcutBlock $block): bool => Auth::check() || $block->isPublishable())
            ->values();
    }

    /** Muro de contenidos: todo lo publicado, sea de la categoría que sea. */
    private function feed(): \Illuminate\Support\Collection
    {
        return Content::query()
            ->with('media')
            ->where('show_in_feed', true)
            // Una sección puede estar marcada como «oculta en muro de
            // contenidos» desde la configuración de su bloque.
            ->whereNotIn('category', $this->categoriesHiddenFromFeed())
            ->tap($this->visibility(...))
            ->recent()
            ->get();
    }

    /**
     * Categorías que la configuración de bloques deja fuera del muro.
     *
     * @return list<string>
     */
    private function categoriesHiddenFromFeed(): array
    {
        return ContentBlockSection::where('hide_in_feed', true)->pluck('category')->unique()->all();
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
