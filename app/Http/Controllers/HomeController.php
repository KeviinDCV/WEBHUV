<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\BannerController;
use App\Models\Banner;
use App\Models\Content;
use App\Models\ContentBlock;
use App\Models\ContentBlockSection;
use App\Models\Setting;
use App\Models\ShortcutBlock;
use App\Models\Topic;
use App\Models\TopicItem;
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
     * Agenda: los eventos del tema que el bloque tenga elegido.
     *
     * La agenda no es una tabla aparte, es un tema —«Calendario de actividades»,
     * con sus ciento cuarenta y un eventos—, igual que en el portal. Así se
     * editan con el mismo formulario que cualquier otro contenido y no hay dos
     * agendas que no se vean entre sí.
     *
     * Sin ese tema no hay agenda que pintar, y el calendario sale vacío en vez
     * de reventar: un sitio recién instalado todavía no ha importado nada.
     *
     * @return \Illuminate\Support\Collection<int, TopicItem>
     */
    private function events(): \Illuminate\Support\Collection
    {
        $block = ContentBlock::events();
        $topic = Topic::firstWhere('name', $block->option('source', ContentBlock::EVENT_SOURCES[0]));

        if (! $topic) {
            return collect();
        }

        $categories = array_filter((array) $block->option('categories', []));

        return $topic->items()
            ->where('kind', TopicItem::KIND_EVENT)
            ->when(! Auth::check(), fn (Builder $q) => $q->visible())
            ->when($categories !== [], fn (Builder $q) => $q->whereHas(
                'categories',
                fn (Builder $c) => $c->whereIn('topic_categories.id', $categories)
            ))
            ->orderBy('opens_at')
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

    /**
     * Muro de contenidos: lo publicado, sea de la categoría que sea.
     *
     * Acotado a los más recientes porque el muro se imprime entero en el HTML y
     * filtra en el navegador. Al importar «Notificaciones Judiciales» pasó de
     * setenta contenidos a casi quinientos y la portada saltó de ciento
     * cincuenta kilobytes a 1,17 megabytes: una página que en una conexión
     * lenta tarda más en llegar que en leerse.
     *
     * El tope es holgado —veinte pulsaciones de «Cargar más»— pero es un tope:
     * la búsqueda del muro no encuentra más atrás. Lo de verdad correcto es
     * paginar en el servidor, como ya se hace en «Contrataciones»; mientras
     * tanto, esto evita servir un megabyte de portada.
     */
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
            ->limit(config('huv.content_feed.max_items', 120))
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
