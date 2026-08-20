<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma de la petición.
 *
 * El portal se escribe en español y se ofrece además en inglés. La elección
 * viaja en la sesión, no en la dirección: el sitio se está trasladando y
 * duplicar cada ruta —«/es/tema/…» y «/en/tema/…»— obligaría a rehacer todos
 * los enlaces del menú, del índice de Transparencia y de la importación.
 *
 * Se acepta también por consulta (?idioma=en) para poder enlazar una página en
 * un idioma concreto y para que el interruptor de la cabecera funcione sin
 * JavaScript: es un enlace normal a la misma página con el parámetro puesto.
 *
 * Un idioma que no esté en la lista se ignora. Es texto que llega de fuera y
 * acabaría en `app()->setLocale()`, que carga ficheros por nombre.
 */
class SetLocale
{
    /** Los que el sitio publica de verdad. */
    public const SUPPORTED = ['es', 'en'];

    public const SESSION_KEY = 'huv.locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->pick($request);

        app()->setLocale($locale);

        // Las fechas van con el texto: «Hace 3 años» y «hace 3 years» en la
        // misma página sería peor que no traducir nada.
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }

    private function pick(Request $request): string
    {
        $pedido = $request->query('idioma');

        if (is_string($pedido) && in_array($pedido, self::SUPPORTED, true)) {
            $request->session()->put(self::SESSION_KEY, $pedido);

            return $pedido;
        }

        $guardado = $request->session()->get(self::SESSION_KEY);

        return is_string($guardado) && in_array($guardado, self::SUPPORTED, true)
            ? $guardado
            : config('app.locale');
    }
}
