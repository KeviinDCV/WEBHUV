<?php

namespace App\Support;

/**
 * Las pestañas de un listado, con su rótulo ya traducido.
 *
 * Quien las pinta es Alpine —el muro de la portada y el listado de un tema—, y
 * dentro del JavaScript no hay forma de pedir una traducción: no existe `__()`
 * ni sabe en qué idioma se está sirviendo la página. Así que los rótulos se
 * resuelven aquí, en el servidor, y viajan al componente en su configuración.
 *
 * Está en un solo sitio porque son tres las vistas que las montan y las claves
 * se les escapaban: escritas en cada plantilla, bastaba con corregir una para
 * que las otras dos se quedaran atrás.
 */
class ListTabs
{
    /** Dónde vive el rótulo de cada pestaña. */
    private const KEYS = [
        'recientes' => 'mensajes.orden.recientes',
        'az' => 'mensajes.orden.az',
        'expedicion' => 'mensajes.orden.expedicion',
        'destacados' => 'mensajes.orden.destacados',
        'inactivos' => 'mensajes.moderacion.inactivos',
        'ocultos' => 'mensajes.moderacion.ocultos',
    ];

    /**
     * Las pestañas pedidas, en el orden en que se piden.
     *
     * @return list<array{key: string, label: string}>
     */
    public static function of(string ...$keys): array
    {
        return array_values(array_map(
            fn (string $key): array => [
                'key' => $key,
                'label' => __(self::KEYS[$key] ?? $key),
            ],
            array_filter($keys, fn (string $key): bool => isset(self::KEYS[$key]))
        ));
    }
}
