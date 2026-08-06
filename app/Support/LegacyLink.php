<?php

namespace App\Support;

use App\Models\Topic;
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

    /** Dirección dentro de este aplicativo, si la sección ya se migró. */
    private static function internalRoute(string $path): ?string
    {
        if (! Str::startsWith($path, '/tema/')) {
            return null;
        }

        $slug = Str::after($path, '/tema/');

        return in_array($slug, self::migratedTopics(), true)
            ? route('topics.show', $slug)
            : null;
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
