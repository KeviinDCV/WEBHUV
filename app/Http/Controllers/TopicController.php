<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TopicController extends Controller
{
    /**
     * Filas por página en los temas de enlaces.
     *
     * Diez, como en el portal: sus 701 contrataciones dan exactamente las 71
     * páginas que muestra su paginador.
     */
    private const LINKS_PER_PAGE = 10;


    /**
     * Listado de un tema: Presupuesto, Programas, Planes…
     *
     * Los elementos se imprimen todos y el filtrado ocurre en el navegador
     * —igual que en el muro de contenidos—, así que hay que traerlos completos.
     * Un tema tiene decenas de elementos, no miles; el día que uno crezca
     * («Notificaciones judiciales» pasa de cuatrocientos) habrá que paginar en
     * el servidor.
     */
    public function show(Topic $topic): View
    {
        // «Noticias» no tiene contenidos propios: es la misma tabla que
        // alimenta la portada, vista desde otra página.
        if ($topic->isContentBacked()) {
            return $this->showContents($topic);
        }

        // Un tema de enlaces se pagina en el servidor: «Contrataciones» tiene
        // setecientos y publicarlos todos en el HTML, como se hace con los
        // demás, daría una página imposible de cargar.
        if ($topic->isLinkList()) {
            return $this->showLinks($topic);
        }

        $items = $topic->items()
            ->with(['categories', 'media'])
            // Sin sesión iniciada, lo inactivo y lo oculto no existe.
            ->unless(Auth::check(), fn ($query) => $query->visible())
            // En un tema de orden manual manda el que puso quien edita; en el
            // resto, lo más reciente primero.
            ->when(
                $topic->isSortable(),
                fn ($query) => $query->orderByDesc('legacy_display_order')->orderByDesc('id'),
                // Por la misma fecha que encabeza cada ficha: la de la última
                // modificación. Ordenar por una fecha distinta de la que se lee
                // deja un listado que parece desordenado.
                fn ($query) => $query
                    ->orderByRaw('COALESCE(modified_at, published_at, created_at) DESC')
                    ->orderByDesc('id')
            )
            ->get()
            // El tema ya está cargado: sin esto, cada ficha lo volvería a
            // consultar al construir su dirección.
            ->each->setRelation('topic', $topic);

        // Los recuentos se cuentan sobre lo que se muestra, no sobre la tabla
        // entera: si no, un visitante vería «(13)» en una categoría con
        // elementos inactivos y encontraría menos al pulsarla. Un elemento con
        // dos categorías cuenta en las dos.
        $counts = $items->flatMap->categories->countBy('id');

        return view('topics.show', [
            'topic' => $topic,
            'items' => $items,
            'categories' => $topic->categories()
                ->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $counts->get($category->id, 0),
                ])
                ->filter(fn (array $category) => $category['count'] > 0)
                ->sortByDesc('count')
                ->values(),
            'tabs' => $this->tabsFor($topic),
            'editing' => $this->editing($topic),
        ]);
    }

    /**
     * Listado paginado de un tema de enlaces.
     *
     * Aquí el filtrado y la búsqueda ocurren en el servidor, no en el navegador
     * como en los demás temas: con setecientas filas no se pueden imprimir
     * todas y decidir después cuáles se ven. Todo viaja en la dirección, así
     * que la página se puede compartir y funciona sin JavaScript.
     */
    private function showLinks(Topic $topic): View
    {
        $categoria = request()->integer('categoria') ?: null;
        $buscar = trim((string) request()->string('buscar'));
        $orden = request()->string('orden')->toString() === 'az' ? 'az' : 'recientes';

        $items = $topic->items()
            ->with('categories')
            ->unless(Auth::check(), fn ($query) => $query->visible())
            ->when($categoria, fn ($query) => $query->whereHas(
                'categories',
                fn ($c) => $c->where('topic_categories.id', $categoria)
            ))
            ->when($buscar !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('title', 'like', '%'.$buscar.'%')
                    ->orWhere('body', 'like', '%'.$buscar.'%')
            ))
            ->when(
                $orden === 'az',
                fn ($query) => $query->orderBy('title'),
                fn ($query) => $query->orderByRaw('COALESCE(modified_at, published_at, created_at) DESC')
                    ->orderByDesc('id')
            )
            ->paginate(self::LINKS_PER_PAGE)
            ->withQueryString();

        // El tema ya está cargado: sin esto, cada fila lo volvería a consultar
        // al construir su dirección. `each->` no vale sobre un paginador.
        $items->getCollection()->each->setRelation('topic', $topic);

        return view('topics.links', [
            'topic' => $topic,
            'items' => $items,
            'categories' => $this->categoryCounts($topic),
            'categoriaActiva' => $categoria,
            'buscar' => $buscar,
            'orden' => $orden,
            'editing' => $this->editing($topic),
        ]);
    }

    /**
     * Recuento por categoría, contado con una sola consulta.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, count: int}>
     */
    private function categoryCounts(Topic $topic): \Illuminate\Support\Collection
    {
        return $topic->categories()
            ->withCount(['items' => fn ($query) => $query->unless(
                Auth::check(),
                fn ($q) => $q->visible()
            )])
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $category->items_count,
            ])
            ->filter(fn (array $category) => $category['count'] > 0)
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Listado de un tema servido por la tabla de contenidos.
     *
     * Cada noticia existe una sola vez: la que se publica desde la portada
     * aparece aquí, y la que se publica aquí aparece en la portada. Por eso su
     * ficha vive en /contenidos/{slug} y no bajo el tema: una sola dirección
     * para una sola cosa.
     */
    private function showContents(Topic $topic): View
    {
        $items = $topic->contents()
            ->with(['media', 'topicCategories'])
            ->unless(Auth::check(), fn ($query) => $query->onHome())
            ->recent()
            ->get();

        $counts = $items->flatMap->topicCategories->countBy('id');

        return view('topics.contents', [
            'topic' => $topic,
            'items' => $items,
            'categories' => $topic->categories()
                ->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'count' => $counts->get($category->id, 0),
                ])
                ->filter(fn (array $category) => $category['count'] > 0)
                ->sortByDesc('count')
                ->values(),
            'editing' => Auth::check() && request()->filled('editar')
                ? $topic->contents()->with('media')->find(request()->integer('editar'))
                : null,
        ]);
    }

    /** Ficha de un elemento del tema. */
    public function showItem(Topic $topic, TopicItem $item): View
    {
        if (! Auth::check() && ! $item->isPublic()) {
            throw new NotFoundHttpException;
        }

        return view('topics.item', [
            'topic' => $topic,
            'item' => $item,
            'related' => $topic->items()
                ->visible()
                ->whereKeyNot($item->getKey())
                ->when($item->categories->isNotEmpty(), fn ($query) => $query->whereHas(
                    'categories',
                    fn ($categories) => $categories->whereIn('topic_categories.id', $item->categories->pluck('id'))
                ))
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
                ->each->setRelation('topic', $topic),
        ]);
    }

    /**
     * Pestañas de orden.
     *
     * Un tema de orden manual no ofrece ninguna: reordenarlo por fecha
     * desharía delante del visitante el orden que alguien colocó a mano.
     *
     * «Fecha de expedición» solo donde hay documentos, como en el portal de
     * origen: es un dato que los artículos no tienen.
     *
     * @return list<array{key: string, label: string}>
     */
    private function tabsFor(Topic $topic): array
    {
        if ($topic->isSortable()) {
            return [];
        }

        return array_values(array_filter([
            ['key' => 'recientes', 'label' => 'Recientes'],
            ['key' => 'az', 'label' => 'A-Z'],
            $topic->sortsByIssueDate() ? ['key' => 'expedicion', 'label' => 'Fecha de expedición'] : null,
        ]));
    }

    /**
     * Elemento que se está editando, si se llegó desde el lápiz de una ficha.
     */
    private function editing(Topic $topic): ?TopicItem
    {
        if (! Auth::check() || ! request()->filled('editar')) {
            return null;
        }

        return $topic->items()->with('media', 'categories')->find(request()->integer('editar'));
    }
}
