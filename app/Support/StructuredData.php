<?php

namespace App\Support;

use App\Models\Content;
use App\Models\TopicItem;
use Illuminate\Support\Str;

/**
 * Los bloques schema.org que emite cada página.
 *
 * La organización va en todas y la declara partials/structured-data.blade.php.
 * Aquí viven los que dependen de lo que se esté viendo: la ficha de una noticia
 * y el rastro de navegación.
 *
 * Todo lo que se emite tiene que estar además VISIBLE en la página. Un dato
 * estructurado que dice algo que el visitante no puede leer es exactamente lo
 * que Google penaliza, así que el rastro de migas se arma con las mismas migas
 * que ya se pintan, no con una lista aparte.
 */
class StructuredData
{
    /** El identificador estable de la organización, para poder referirla. */
    public static function organizationId(): string
    {
        return url('/').'#organizacion';
    }

    /**
     * La ficha de un contenido: noticia, comunicado o notificación judicial.
     *
     * `NewsArticle` solo para las noticias. Un comunicado o una notificación
     * judicial no son periodismo, y declararlos como tal para arañar un
     * resultado enriquecido es precisamente lo que las directrices llaman
     * marcado engañoso.
     *
     * @return array<string, mixed>
     */
    public static function article(Content $content): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $content->category === Content::NEWS_CATEGORY ? 'NewsArticle' : 'Article',
            'headline' => Str::limit($content->title, 110, ''),
            'description' => Str::squish($content->summary(160)) ?: null,
            'inLanguage' => config('huv.content_locale'),
            'datePublished' => $content->displayDate()?->toAtomString(),
            'dateModified' => ($content->modified_at ?? $content->published_at ?? $content->updated_at)?->toAtomString(),
            'image' => $content->imageUrl() ?: null,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $content->url()],
            'publisher' => ['@id' => self::organizationId()],
            'isAccessibleForFree' => true,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    /**
     * La ficha de un artículo de tema.
     *
     * Solo los que son artículos de verdad. Un documento es un PDF con una
     * portada, no una pieza escrita, y un evento tiene su propio tipo; ni uno ni
     * otro se declaran aquí para no prometer lo que no hay.
     *
     * @return array<string, mixed>|null
     */
    public static function topicItem(TopicItem $item): ?array
    {
        if (! $item->isArticle()) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => Str::limit($item->title, 110, ''),
            'description' => Str::squish($item->summary(160)) ?: null,
            'inLanguage' => config('huv.content_locale'),
            'datePublished' => $item->date()?->toAtomString(),
            'dateModified' => ($item->modified_at ?? $item->published_at ?? $item->updated_at)?->toAtomString(),
            'image' => $item->imageUrl() ?: null,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $item->url()],
            'publisher' => ['@id' => self::organizationId()],
            'isAccessibleForFree' => true,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    /**
     * El rastro de navegación.
     *
     * @param  list<array{nombre: string, url?: string|null}>  $migas
     * @return array<string, mixed>|null
     */
    public static function breadcrumbs(array $migas): ?array
    {
        $migas = array_values(array_filter($migas, fn (array $miga): bool => filled($miga['nombre'] ?? null)));

        // Con una sola miga no hay rastro que declarar: es la propia página.
        if (count($migas) < 2) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $miga, int $i): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $miga['nombre'],
                    'item' => $miga['url'] ?? null,
                ], fn ($valor) => $valor !== null),
                $migas,
                array_keys($migas)
            ),
        ];
    }
}
