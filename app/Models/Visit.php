<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Una página vista.
 *
 * Lo que se pregunta desde la pantalla de estadísticas es siempre lo mismo
 * —cuántos visitantes, cuántas páginas, cuál es el promedio— así que las
 * consultas viven aquí y no repartidas por el controlador.
 *
 * Sobre lo que estas cifras SÍ y NO dicen, hay una advertencia importante que
 * se enseña también en la pantalla: una «visita» es un navegador en un día. A
 * quien no acepte la cookie de sesión —y los rastreadores no la aceptan— se le
 * cuenta una visita nueva en cada página, así que el número real de personas es
 * algo menor que el que sale. Por eso se descartan los rastreadores conocidos
 * al registrar, y por eso conviene mirar la tendencia más que el número exacto.
 */
class Visit extends Model
{
    /** Solo se escribe al crear; una visita no se modifica nunca. */
    public const UPDATED_AT = null;

    protected $guarded = [];

    /*
     | `visited_on` se guarda y se lee como cadena «Y-m-d», SIN convertirla a
     | fecha.
     |
     | Con el convertidor puesto, Eloquent escribía «2026-08-25 00:00:00» —su
     | formato de fecha y hora— dentro de una columna DATE. MySQL lo recorta al
     | guardar y no se nota; SQLite, que es donde corren las pruebas, lo guarda
     | entero, y entonces `whereBetween('visited_on', ['…-19', '…-25'])` deja
     | fuera el último día, porque «2026-08-25 00:00:00» es mayor que
     | «2026-08-25» comparando letra a letra.
     |
     | O sea: la misma consulta daba cifras distintas según el motor. Guardando
     | siempre «Y-m-d» a mano, las dos dan lo mismo.
     */

    /**
     * El identificador de visitante de un día.
     *
     * La sesión mezclada con la fecha y con la clave de la aplicación. Cambia
     * cada medianoche a propósito: sirve para contar a alguien dentro de un día
     * y no sirve para seguirlo al siguiente.
     */
    public static function visitorHash(string $sessionId, Carbon $dia): string
    {
        return substr(
            hash('sha256', $sessionId.'|'.$dia->toDateString().'|'.config('app.key')),
            0,
            32
        );
    }

    /* ------------------------------------------------------------------ */
    /* Lo que enseña la pantalla                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Un día por fila, con sus visitantes y sus páginas vistas.
     *
     * Los días sin ninguna visita también salen, con ceros: si se omitieran,
     * la gráfica juntaría el viernes con el lunes y el fin de semana —que en un
     * portal de trámites se nota mucho— desaparecería del dibujo.
     *
     * @return Collection<int, array{fecha: Carbon, visitantes: int, paginas: int}>
     */
    public static function perDay(Carbon $desde, Carbon $hasta): Collection
    {
        // Con el constructor de consultas a secas y no con el del modelo: aquí
        // no salen filas de la tabla sino un resumen, y el modelo intentaría
        // convertir `visited_on` en fecha y `visitantes` en columna suya.
        $filas = self::query()
            ->toBase()
            ->whereBetween('visited_on', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('visited_on')
            ->selectRaw('visited_on, COUNT(DISTINCT visitor) as visitantes, COUNT(*) as paginas')
            ->get()
            ->keyBy(fn (object $fila): string => Carbon::parse($fila->visited_on)->toDateString());

        $dias = collect();

        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $fila = $filas->get($dia->toDateString());

            $dias->push([
                'fecha' => $dia->copy(),
                'visitantes' => (int) ($fila->visitantes ?? 0),
                'paginas' => (int) ($fila->paginas ?? 0),
            ]);
        }

        return $dias;
    }

    /**
     * Las páginas más vistas del periodo.
     *
     * @return Collection<int, array{path: string, paginas: int, visitantes: int}>
     */
    public static function topPaths(Carbon $desde, Carbon $hasta, int $cuantas = 10): Collection
    {
        return self::query()
            ->toBase()
            ->whereBetween('visited_on', [$desde->toDateString(), $hasta->toDateString()])
            ->groupBy('path')
            ->selectRaw('path, COUNT(*) as paginas, COUNT(DISTINCT visitor) as visitantes')
            ->orderByDesc('paginas')
            ->limit($cuantas)
            ->get()
            ->map(fn (object $fila): array => [
                'path' => (string) $fila->path,
                'paginas' => (int) $fila->paginas,
                'visitantes' => (int) $fila->visitantes,
            ]);
    }

    /** El día con más visitantes del periodo, para saber cuál es el techo. */
    public static function busiestDay(Carbon $desde, Carbon $hasta): ?array
    {
        return self::perDay($desde, $hasta)
            ->sortByDesc('visitantes')
            ->first();
    }
}
