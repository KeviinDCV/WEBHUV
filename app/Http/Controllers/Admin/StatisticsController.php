<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Cuánta gente entra al portal.
 *
 * La pregunta que hay que responder es el promedio diario de visitantes, así
 * que es lo que va más grande y lo primero que se lee. Lo demás —el total del
 * periodo, las páginas vistas, el día de más— está para dar contexto a ese
 * número, no para competir con él.
 *
 * No confundir con la página pública «/estadisticas», que sigue en blanco: esta
 * es la de dentro, y solo la ve un administrador.
 */
class StatisticsController extends Controller
{
    /** Periodos que se pueden mirar, en días. */
    public const PERIODS = [7, 30, 90, 365];

    private const DEFAULT_PERIOD = 30;

    public function index(Request $request): View
    {
        $dias = (int) $request->integer('dias', self::DEFAULT_PERIOD);

        if (! in_array($dias, self::PERIODS, true)) {
            $dias = self::DEFAULT_PERIOD;
        }

        $hasta = Carbon::today();

        // Menos uno porque el periodo incluye hoy: «últimos 7 días» son hoy y
        // los seis anteriores, no hoy y los siete anteriores.
        $desde = $hasta->copy()->subDays($dias - 1);

        $porDia = Visit::perDay($desde, $hasta);

        $visitantes = $porDia->sum('visitantes');
        $paginas = $porDia->sum('paginas');

        return view('admin.estadisticas.index', [
            'dias' => $dias,
            'desde' => $desde,
            'hasta' => $hasta,
            'porDia' => $porDia,

            // El promedio se calcula sobre los días del periodo y no sobre los
            // días CON visitas: dividir solo entre los días que tuvieron gente
            // daría un promedio más alto cuanto más vacío esté el portal.
            'promedio' => $porDia->count() > 0 ? $visitantes / $porDia->count() : 0.0,

            'visitantes' => $visitantes,
            'paginas' => $paginas,
            'diaCumbre' => $porDia->sortByDesc('visitantes')->first(),
            'topPaths' => Visit::topPaths($desde, $hasta),

            // Sin datos todavía no hay nada que interpretar, y conviene decirlo
            // en vez de enseñar un cero que parece una caída.
            'hayDatos' => Visit::query()->exists(),
            'desdeCuando' => Visit::query()->min('visited_on'),
        ]);
    }
}
