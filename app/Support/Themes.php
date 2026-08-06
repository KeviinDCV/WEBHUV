<?php

namespace App\Support;

/**
 * Temas de color de los bloques de la portada.
 *
 * Cada tono se usa de dos formas: como color de icono sobre fondo blanco y
 * como fondo de bloque con texto blanco encima. Por eso todos están
 * oscurecidos lo necesario para superar el 4.5:1 de WCAG AA en ambos sentidos;
 * los tonos claros del portal original —amarillo, cian, verde lima— dejarían
 * ilegible el texto en cuanto alguien los eligiera.
 */
class Themes
{
    public const COLORS = [
        'navy' => '#2b3b80',
        'azure' => '#2676d2',
        'sky' => '#006c99',
        'teal' => '#006f66',
        'green' => '#17703d',
        'olive' => '#5f6b1a',
        'lime' => '#4f7a12',
        'amber' => '#a15c00',
        'orange' => '#b34e00',
        'red' => '#b3261e',
        'crimson' => '#a2003e',
        'magenta' => '#a3007a',
        'pink' => '#b0164f',
        'purple' => '#6a1b9a',
        'violet' => '#4527a0',
        'indigo' => '#283593',
        'slate' => '#3d4552',
        'graphite' => '#14275e',
        'ink' => '#33383f',
    ];

    public const DEFAULT = 'navy';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::COLORS);
    }

    public static function color(?string $key): string
    {
        return self::COLORS[$key] ?? self::COLORS[self::DEFAULT];
    }
}
