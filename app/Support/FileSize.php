<?php

namespace App\Support;

/**
 * Tamaño de un archivo tal como lo escribe el portal institucional.
 *
 * Vive aparte porque lo necesitan por igual los documentos de un tema y los
 * adjuntos de un contenido, y tenerlo dos veces ya produjo una divergencia:
 * una ficha decía «777 Kb» y otra «777 KB» para lo mismo.
 *
 * Se sigue al portal en las dos cosas que lo distinguen: la caja de las
 * unidades y el redondeo a número entero —allí no hay «2,5 Mb» en ninguna
 * ficha—. Es menos preciso que llevar un decimal, pero el tamaño solo sirve
 * para hacerse una idea de lo que se va a descargar.
 */
class FileSize
{
    /** Unidades escritas como en el portal: «777 Kb», «7 Mb». */
    private const UNITS = ['B', 'Kb', 'Mb', 'Gb'];

    public static function human(?int $bytes): ?string
    {
        if (! $bytes) {
            return null;
        }

        $power = min((int) floor(log(max($bytes, 1), 1024)), count(self::UNITS) - 1);

        return round($bytes / (1024 ** $power)).' '.self::UNITS[$power];
    }
}
