<?php

namespace App\Support;

use App\Models\Content;
use App\Models\TopicItem;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * El buscador del portal.
 *
 * Busca lo mismo que el buscador del portal actual, que se comprobó
 * interrogando su API: `/api/v1/contents?keyword=…` devuelve artículos,
 * documentos, enlaces y avisos, y encuentra tanto por el título como por el
 * texto del cuerpo —«Guardian Group», que solo aparece dentro de una noticia,
 * la saca—. Lo que NO busca son los rótulos del menú ni los nombres de las
 * categorías: la petición que lanza al menú va siempre con la palabra vacía.
 *
 * Aquí el contenido vive en dos tablas —los contenidos de la portada y los
 * artículos de un tema—, así que se consulta cada una y se juntan los
 * resultados en un solo listado ordenado por fecha.
 *
 * Se traen primero solo el identificador y la fecha de cada coincidencia, se
 * ordenan, se recorta la página y solo entonces se cargan las filas enteras.
 * Buscar «de» casa con casi todo, y traer dos mil cuerpos completos para
 * enseñar diez sería pagar el listado entero en cada búsqueda.
 */
class SiteSearch
{
    /** Cuántos resultados por página. */
    public const PER_PAGE = 10;

    /**
     * Por debajo de esto no se busca.
     *
     * Con una sola letra casa medio portal y el listado no dice nada; el portal
     * actual tampoco responde a un carácter suelto.
     */
    public const MIN_LENGTH = 2;

    /**
     * Qué carácter anula los comodines de LIKE.
     *
     * No se usa la barra invertida, que es lo habitual: MySQL la trata además
     * como escape DENTRO del literal de texto, así que `ESCAPE '\'` le llega
     * partido y la consulta deja de devolver nada, mientras SQLite la acepta
     * tal cual. Con las pruebas en SQLite y el sitio en MySQL, eso es un fallo
     * que solo se ve en producción. La admiración no la interpreta ninguno de
     * los dos, así que significa lo mismo en ambos.
     */
    private const ESCAPE = '!';

    public function __construct(
        private readonly string $terms,
        private readonly ?string $type = null,
        private readonly ?Carbon $from = null,
        private readonly ?Carbon $to = null,
    ) {}

    /** ¿Hay algo que buscar? */
    public function isSearchable(): bool
    {
        return mb_strlen($this->terms) >= self::MIN_LENGTH;
    }

    /**
     * Los resultados de la página pedida.
     *
     * @return LengthAwarePaginator<int, Content|TopicItem>
     */
    public function paginate(int $page = 1): LengthAwarePaginator
    {
        if (! $this->isSearchable()) {
            return $this->emptyPage($page);
        }

        // Ordenar solo por fecha no basta: dos contenidos del mismo día quedan
        // como los devuelva la base, que no tiene por qué contestar igual en la
        // página siguiente —un resultado saldría dos veces y otro ninguna—. El
        // identificador desempata, rellenado con ceros para que se compare como
        // número y no como texto, donde «9» iría después de «10».
        $hits = $this->contentHits()
            ->concat($this->itemHits())
            ->sortByDesc(fn (array $hit): string => $hit['fecha']
                .'|'.str_pad((string) $hit['id'], 12, '0', STR_PAD_LEFT)
                .'|'.$hit['tipo'])
            ->values();

        $slice = $hits->forPage($page, self::PER_PAGE);

        return new LengthAwarePaginator(
            $this->hydrate($slice),
            $hits->count(),
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /* ------------------------------------------------------------------ */
    /* Las dos consultas ligeras                                           */
    /* ------------------------------------------------------------------ */

    /** @return Collection<int, array{tipo: string, id: int, fecha: string}> */
    private function contentHits(): Collection
    {
        if ($this->type !== null && $this->type !== Content::class) {
            return collect();
        }

        $query = Content::query()
            ->unless(Auth::check(), fn (Builder $q) => $q->onHome())
            ->where($this->matches(['title', 'excerpt', 'body']));

        $this->applyDates($query, 'COALESCE(published_at, created_at)');

        return $query
            ->selectRaw("id, COALESCE(published_at, created_at) as fecha")
            ->get()
            ->map(fn (Content $row): array => [
                'tipo' => Content::class,
                'id' => $row->id,
                'fecha' => (string) $row->getRawOriginal('fecha'),
            ]);
    }

    /** @return Collection<int, array{tipo: string, id: int, fecha: string}> */
    private function itemHits(): Collection
    {
        if ($this->type !== null && $this->type !== TopicItem::class) {
            return collect();
        }

        $query = TopicItem::query()
            ->unless(Auth::check(), fn (Builder $q) => $q->visible())
            ->where($this->matches(['title', 'body']));

        $this->applyDates($query, 'COALESCE(modified_at, published_at, created_at)');

        return $query
            ->selectRaw("id, COALESCE(modified_at, published_at, created_at) as fecha")
            ->get()
            ->map(fn (TopicItem $row): array => [
                'tipo' => TopicItem::class,
                'id' => $row->id,
                'fecha' => (string) $row->getRawOriginal('fecha'),
            ]);
    }

    /**
     * El filtro de texto: todas las palabras, en cualquiera de los campos.
     *
     * Se exigen TODAS las palabras porque es lo que espera quien escribe dos:
     * «acuerdo presupuesto» debe estrechar la búsqueda, no ensancharla. Cada
     * palabra puede aparecer en un campo distinto —una en el título y otra en
     * el cuerpo—, que es como está escrito el contenido de verdad.
     *
     * @param  list<string>  $fields
     */
    private function matches(array $fields): callable
    {
        $words = $this->words();

        return function (Builder $query) use ($words, $fields): void {
            foreach ($words as $word) {
                $query->where(function (Builder $q) use ($word, $fields): void {
                    foreach ($fields as $field) {
                        $q->orWhereRaw("{$field} LIKE ? ESCAPE '".self::ESCAPE."'", ['%'.$word.'%']);
                    }
                });
            }
        };
    }

    /**
     * Las palabras del término, sin los comodines de LIKE.
     *
     * `%` y `_` son comodines: buscar «100%» sin escaparlos casaría con
     * cualquier cosa que empiece por «100».
     *
     * @return list<string>
     */
    private function words(): array
    {
        $words = preg_split('~\s+~u', trim($this->terms), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_map(
            fn (string $word): string => str_replace(
                [self::ESCAPE, '%', '_'],
                [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
                $word
            ),
            array_slice($words, 0, 8)
        ));
    }

    /** @param  Builder<Content|TopicItem>  $query */
    private function applyDates(Builder $query, string $dateExpression): void
    {
        if ($this->from) {
            $query->whereRaw("{$dateExpression} >= ?", [$this->from->startOfDay()]);
        }

        if ($this->to) {
            $query->whereRaw("{$dateExpression} <= ?", [$this->to->endOfDay()]);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Carga las filas de la página, cada una con lo que su tarjeta necesita.
     *
     * @param  Collection<int, array{tipo: string, id: int, fecha: string}>  $slice
     * @return Collection<int, Content|TopicItem>
     */
    private function hydrate(Collection $slice): Collection
    {
        $contentIds = $slice->where('tipo', Content::class)->pluck('id')->all();
        $itemIds = $slice->where('tipo', TopicItem::class)->pluck('id')->all();

        $contents = $contentIds === []
            ? collect()
            : Content::with('media')->whereKey($contentIds)->get()->keyBy('id');

        $items = $itemIds === []
            ? collect()
            : TopicItem::with('topic', 'categories', 'media')->whereKey($itemIds)->get()->keyBy('id');

        // Se recorre el recorte y no las colecciones cargadas: el orden por
        // fecha se decidió antes y `whereKey` lo devuelve como quiera la base.
        return $slice
            ->map(fn (array $hit) => $hit['tipo'] === Content::class
                ? ($contents[$hit['id']] ?? null)
                : ($items[$hit['id']] ?? null))
            ->filter()
            ->values();
    }

    /** @return LengthAwarePaginator<int, Content|TopicItem> */
    private function emptyPage(int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator(collect(), 0, self::PER_PAGE, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
