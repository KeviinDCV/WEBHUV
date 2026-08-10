<?php

namespace App\Support;

use App\Models\Topic;
use App\Models\TopicCategory;
use Illuminate\Support\Str;

/**
 * Resolución de los enlaces del menú durante la migración.
 *
 * El portal se traslada sección por sección, así que en cualquier momento
 * conviven enlaces ya migrados y enlaces que todavía viven en el portal
 * anterior. En lugar de ir editando `config/huv.php` cada vez que se importa un
 * tema, se decide aquí: si el tema ya está en la base de datos, el enlace apunta
 * a este aplicativo; si no, al portal actual.
 *
 * Los temas migrados se consultan una sola vez por petición: el menú principal
 * tiene más de sesenta enlaces y una consulta por cada uno sería absurda.
 */
class LegacyLink
{
    /** @var list<string>|null */
    private static ?array $migratedTopics = null;

    /**
     * @param  array{label: string, url?: string, path?: string}  $link
     * @return array{href: string, external: bool}
     */
    public static function resolve(array $link): array
    {
        // 'url' es siempre un destino ajeno al portal: otra entidad, una red
        // social, un trámite del Estado.
        if (isset($link['url'])) {
            return ['href' => $link['url'], 'external' => true];
        }

        $path = $link['path'];

        if ($route = self::internalRoute($path)) {
            return ['href' => $route, 'external' => false];
        }

        $base = rtrim((string) config('huv.legacy_base'), '/');

        // Sin portal anterior configurado el enlace se resuelve contra este
        // aplicativo: es lo que quedará cuando la migración termine.
        return ['href' => $base.$path, 'external' => $base !== ''];
    }

    /**
     * Destino de un enlace guardado durante la migración.
     *
     * Los enlaces del portal no siempre salen fuera: «Población vulnerable» son
     * cuatro atajos hacia otros temas del mismo sitio. Guardarlos tal cual
     * dejaría al visitante saltando al portal anterior desde secciones que ya
     * están aquí, así que se traducen igual que los del menú.
     *
     * Lo que apunta a otra entidad —el SECOP, un trámite del Estado— se
     * devuelve intacto.
     */
    public static function rewrite(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        $path = self::legacyPath($url);

        if ($path === null) {
            return $url;
        }

        return self::internalRoute($path) ?? rtrim((string) config('huv.legacy_base'), '/').$path;
    }

    /**
     * La parte de la dirección que puede vivir en este aplicativo.
     *
     * Devuelve null cuando el destino es de otro sitio: entonces no hay nada
     * que traducir.
     */
    private static function legacyPath(string $url): ?string
    {
        if (Str::startsWith($url, '/')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $legacyHost = parse_url((string) config('huv.legacy_base'), PHP_URL_HOST);

        if ($host === null || $legacyHost === null || $host !== $legacyHost) {
            return null;
        }

        return '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    /** Dirección dentro de este aplicativo, si la sección ya se migró. */
    private static function internalRoute(string $path): ?string
    {
        if (! Str::startsWith($path, '/tema/')) {
            return null;
        }

        // El portal admite «/tema/{tema}/{categoría}» para abrir un tema ya
        // filtrado. Más de dos tramos no significan nada allí.
        $parts = explode('/', trim(Str::after($path, '/tema/'), '/'));

        if (count($parts) > 2) {
            return null;
        }

        [$slug, $category] = [$parts[0], $parts[1] ?? null];

        if (! in_array($slug, self::migratedTopics(), true)) {
            return null;
        }

        if ($category === null) {
            return route('topics.show', $slug);
        }

        $id = self::categoryId($slug, $category);

        // Una categoría que aquí no existe no vacía el listado: el portal de
        // origen hace lo mismo, ignora el tramo y enseña el tema entero.
        return $id === null
            ? route('topics.show', $slug)
            : route('topics.show', [$slug, 'categoria' => $id]);
    }

    /**
     * La categoría del tema que corresponde a un tramo de la dirección.
     *
     * El portal le añade un número al final cuando el mismo nombre se repite en
     * varios temas —«poblacion-vulnerable-31962»—; aquí las categorías cuelgan
     * de su tema y no hacen falta desempates, así que se prueba primero el
     * tramo tal cual y después sin el sufijo.
     */
    private static function categoryId(string $topic, string $category): ?int
    {
        $candidates = array_values(array_unique(array_filter([
            $category,
            preg_replace('/-\d+$/', '', $category),
        ])));

        $matches = TopicCategory::query()
            ->whereHas('topic', fn ($query) => $query->where('slug', $topic))
            ->whereIn('slug', $candidates)
            ->pluck('id', 'slug');

        foreach ($candidates as $candidate) {
            if ($matches->has($candidate)) {
                return (int) $matches[$candidate];
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function migratedTopics(): array
    {
        return self::$migratedTopics ??= Topic::query()
            ->whereNotNull('imported_at')
            ->pluck('slug')
            ->all();
    }

    /** Solo para las pruebas: obliga a volver a consultar los temas migrados. */
    public static function forget(): void
    {
        self::$migratedTopics = null;
    }
}
