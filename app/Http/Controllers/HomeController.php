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
use App\Support\FeedType;
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
        $topic = Topic::firstWhere('slug', $block->option('source', ContentBlock::DEFAULT_EVENT_SOURCE));

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
            // Dos eventos a la misma hora se ordenan por el orden en que los
            // publicó el portal, que es el de su identificador: el 19 de agosto
            // de 2026 coinciden dos a las 14:00 y allí sale primero «Derechos y
            // Deberes de los Pacientes», el más antiguo de los dos.
            //
            // Sin este desempate el orden lo decide la base y no está definido.
            // Va sin prueba a propósito: con dos filas MySQL las devuelve en
            // orden de identificador de todas formas, así que una comprobación
            // pasaría igual sin esta línea y solo daría confianza falsa.
            ->orderBy('id')
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
        $tope = (int) config('huv.content_feed.max_items', 120);

        $contenidos = Content::query()
            ->with('media')
            ->where('show_in_feed', true)
            // Una sección puede estar marcada como «oculta en muro de
            // contenidos» desde la configuración de su bloque.
            ->whereNotIn('category', $this->categoriesHiddenFromFeed())
            ->tap($this->visibility(...))
            ->recent()
            ->limit($tope)
            ->get();

        /*
         | Los elementos de tema NO entran en el recorte por fecha.
         |
         | Si se recortara la mezcla a los ciento veinte más recientes, los
         | contenidos se lo comerían todo —son cuatrocientos noventa y cinco
         | frente a ciento treinta— y el filtro por tipo quedaría inservible:
         | medido, salían ciento dieciséis noticias, dos documentos, dos
         | eventos y ni una convocatoria ni un enlace, o sea cuatro de los seis
         | tipos vacíos.
         |
         | Son ciento treinta fichas en total, así que caben enteras. El orden
         | que se ve sigue siendo por fecha; lo único que cambia es que la cola
         | de la lista tiene variedad en vez de más de lo mismo.
        */
        return $contenidos
            ->concat($this->feedItems())
            ->sortByDesc(fn (Content|TopicItem $item): int => $item->displayDate()?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * Los elementos de tema que van al muro.
     *
     * El portal de origen mezcla en su muro todos los tipos de contenido, y
     * marca uno a uno cuáles salen en la portada. Esa marca se importó en
     * `legacy_show_on_home`, así que aquí no hay que decidir nada: se respeta la
     * que trajo cada elemento. Hoy son ciento treinta.
     *
     * Se descartan las clases que el origen no considera contenido de muro
     * —preguntas frecuentes y trámites—; ver App\Support\FeedType.
     */
    private function feedItems(): \Illuminate\Support\Collection
    {
        return TopicItem::query()
            ->with(['media', 'topic'])
            ->where('legacy_show_on_home', true)
            ->whereIn('kind', FeedType::feedKinds())
            ->visible()
            ->orderByDesc('published_at')
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
