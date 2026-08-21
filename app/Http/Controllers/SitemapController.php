<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * El mapa del sitio para los buscadores.
 *
 * El portal publica más de dos mil direcciones, y muchas —las setenta páginas
 * de «Contrataciones», las notificaciones judiciales antiguas— quedan a varios
 * saltos de la portada: sin mapa, un rastreador tarda meses en encontrarlas o
 * no las encuentra. Para una entidad del Estado eso significa que contrataciones
 * y actos administrativos publicados no salen cuando la ciudadanía los busca.
 *
 * Va partido en un índice y tres hijos porque un solo fichero con dos mil
 * entradas es incómodo de revisar y el protocolo recomienda trocearlo mucho
 * antes de las cincuenta mil que admite.
 *
 * Se cachea: recorrer mil ochocientos artículos preguntándole su dirección a
 * cada uno cuesta lo mismo que servir una página, y esto lo pide un robot.
 */
class SitemapController extends Controller
{
    /** Las tres secciones, con el nombre que llevan en la dirección. */
    public const SECTIONS = ['temas', 'contenidos', 'articulos'];

    private const CACHE_MINUTES = 360;

    /** El índice: dice qué hijos hay y cuándo cambió cada uno. */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.indice', self::CACHE_MINUTES * 60, function (): string {
            $mapas = collect(self::SECTIONS)->map(fn (string $seccion): string => $this->tag('sitemap', [
                'loc' => route('sitemap.section', $seccion),
                'lastmod' => $this->latest($seccion)?->toAtomString(),
            ]));

            return $this->document('sitemapindex', $mapas->implode("\n"));
        });

        return $this->xml($xml);
    }

    /** Una sección: sus direcciones con su última modificación. */
    public function section(string $seccion): Response
    {
        abort_unless(in_array($seccion, self::SECTIONS, true), 404);

        $xml = Cache::remember('sitemap.'.$seccion, self::CACHE_MINUTES * 60, function () use ($seccion): string {
            $urls = $this->entries($seccion)->map(fn (array $entry): string => $this->tag('url', $entry));

            return $this->document('urlset', $urls->implode("\n"));
        });

        return $this->xml($xml);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Las direcciones de una sección.
     *
     * @return \Illuminate\Support\Collection<int, array<string, string|null>>
     */
    private function entries(string $seccion): \Illuminate\Support\Collection
    {
        return match ($seccion) {
            'temas' => $this->fixedPages()->concat($this->topics()),
            'contenidos' => $this->contents(),
            'articulos' => $this->items(),
        };
    }

    /** La portada y las páginas propias, que no salen de la base. */
    private function fixedPages(): \Illuminate\Support\Collection
    {
        return collect(['home', 'transparency', 'branches', 'policies', 'pqrds', 'contact'])
            ->map(fn (string $nombre): array => [
                'loc' => route($nombre),
                'changefreq' => $nombre === 'home' ? 'daily' : 'monthly',
                'priority' => $nombre === 'home' ? '1.0' : '0.5',
            ]);
    }

    private function topics(): \Illuminate\Support\Collection
    {
        return Topic::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Topic $topic): array => [
                'loc' => route('topics.show', $topic),
                'lastmod' => $topic->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
            ]);
    }

    /**
     * Las fichas de contenido que tienen página propia.
     *
     * Las que traen `link` no la tienen: su dirección es la de otro sitio, y
     * anunciar en nuestro mapa una página ajena es decirle al buscador algo que
     * no es nuestro.
     */
    private function contents(): \Illuminate\Support\Collection
    {
        return Content::query()
            ->onHome()
            ->whereNull('link')
            ->orderBy('id')
            ->get()
            ->map(fn (Content $content): array => [
                'loc' => route('contents.show', $content->slug),
                'lastmod' => ($content->modified_at ?? $content->published_at ?? $content->created_at)?->toAtomString(),
            ]);
    }

    /**
     * Los artículos de tema con página propia.
     *
     * Un enlace y un trámite no la tienen: su `url()` lleva fuera —a gov.co, al
     * SECOP, al portal anterior—, así que se quedan fuera del mapa.
     *
     * No basta con mirar si la dirección empieza por «/tema/»: los enlaces que
     * apuntaban al portal anterior los reescribe App\Support\LegacyLink contra
     * este, y unos cuantos apuntaban a PÁGINAS DE TEMA. Colados así, el mapa
     * anunciaba trece veces una dirección que ya estaba —«/tema/control-
     * ciudadano» salía tres— y encima como si fuera la ficha de un artículo.
     * Se compara con la ruta propia del elemento, que es la única que dice de
     * verdad si la página existe aquí y es suya.
     */
    private function items(): \Illuminate\Support\Collection
    {
        return TopicItem::query()
            ->visible()
            ->with('topic')
            ->orderBy('id')
            ->get()
            ->filter(fn (TopicItem $item): bool => $item->url() === route('topics.items.show', [$item->topic, $item]))
            ->map(fn (TopicItem $item): array => [
                'loc' => $item->url(),
                'lastmod' => ($item->modified_at ?? $item->published_at ?? $item->created_at)?->toAtomString(),
            ])
            ->values();
    }

    /** La fecha más reciente de una sección, para el índice. */
    private function latest(string $seccion): ?Carbon
    {
        $fechas = $this->entries($seccion)
            ->pluck('lastmod')
            ->filter()
            ->map(fn (string $fecha): Carbon => Carbon::parse($fecha));

        return $fechas->isEmpty() ? null : $fechas->max();
    }

    /* ------------------------------------------------------------------ */

    /**
     * Una entrada, con sus campos en el orden que exige el esquema.
     *
     * @param  array<string, string|null>  $campos
     */
    private function tag(string $nombre, array $campos): string
    {
        $dentro = collect(['loc', 'lastmod', 'changefreq', 'priority'])
            ->filter(fn (string $campo): bool => filled($campos[$campo] ?? null))
            ->map(fn (string $campo): string => '    <'.$campo.'>'.e($campos[$campo]).'</'.$campo.'>')
            ->implode("\n");

        return "  <{$nombre}>\n{$dentro}\n  </{$nombre}>";
    }

    private function document(string $raiz, string $cuerpo): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<'.$raiz.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$cuerpo."\n"
            .'</'.$raiz.'>'."\n";
    }

    private function xml(string $cuerpo): Response
    {
        return response($cuerpo, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
