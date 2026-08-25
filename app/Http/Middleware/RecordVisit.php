<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Anota que alguien vio una página.
 *
 * Se registra DESPUÉS de responder —el middleware devuelve la respuesta y anota
 * al terminar— para que quien visita no espere a que se escriba nada.
 *
 * Lo que NO se anota, y cada exclusión tiene su motivo:
 *
 * · Lo que no sea una página HTML vista con GET y respondida con 200. Un 404,
 *   una descarga o un envío de formulario no son «una visita».
 * · La administración, y en general cualquiera con la sesión iniciada: la
 *   pregunta es cuánta gente entra al portal, y quien edita no es público. Si
 *   se contara, los días de mucho trabajo parecerían días de mucho público.
 * · Los rastreadores conocidos. No son gente, y además no devuelven la cookie
 *   de sesión, así que cada página suya contaría como una visita nueva.
 *
 * Y si al anotar algo falla, la página ya se ha servido y no pasa nada más: una
 * estadística incompleta es un mal menor frente a una página caída.
 */
class RecordVisit
{
    /**
     * Anotaciones que se admiten por minuto y por origen.
     *
     * Sin tope, cualquiera desde fuera podía escribir filas en bucle: no hay
     * sesión que valga —quien no manda la cookie estrena una en cada petición,
     * así que cada una contaba como visitante nuevo—, y con eso se infla el
     * promedio y se llena la tabla.
     *
     * Diez por segundo es un orden de magnitud por encima de lo que hace
     * cualquiera navegando y un orden por debajo de lo que hace un guion. Es
     * generoso a propósito: si algún día esto se despliega detrás de un proxy
     * sin TRUSTED_PROXIES fijado, todas las visitas compartirían origen, y más
     * vale un tope que no se alcanza nunca que dejar de contar al hospital
     * entero.
     */
    public const MAX_POR_MINUTO = 600;

    /** Trozos de identificador que delatan a un rastreador. */
    private const BOTS = [
        'bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'python-requests',
        'headlesschrome', 'phantomjs', 'lighthouse', 'pingdom', 'uptime',
        'facebookexternalhit', 'preview', 'monitoring', 'scraper',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /** Se ejecuta con la respuesta ya enviada al navegador. */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->countable($request, $response)) {
            return;
        }

        // El origen se usa para el tope y NO se guarda: la clave es un resumen
        // que solo vive en la caché el minuto que dura la ventana.
        $cubo = 'visitas:'.sha1((string) $request->ip());

        if (RateLimiter::tooManyAttempts($cubo, self::MAX_POR_MINUTO)) {
            return;
        }

        RateLimiter::hit($cubo, 60);

        try {
            $hoy = Carbon::today();

            Visit::create([
                'visitor' => Visit::visitorHash($request->session()->getId(), $hoy),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
                'visited_on' => $hoy->toDateString(),
            ]);
        } catch (Throwable) {
            // A propósito en silencio: la página ya está servida y perder una
            // anotación no justifica ensuciar el registro de errores en cada
            // visita si la tabla no existiera o la base estuviera ocupada.
        }
    }

    private function countable(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        // Solo páginas: ni ficheros, ni XML, ni JSON.
        if (! Str::contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if (Auth::check() || $request->is('administracion/*', 'ingresar')) {
            return false;
        }

        // Basta con que haya sesión y tenga identificador. NO se comprueba
        // `isStarted()`, aunque parezca lo natural: para cuando corre este
        // `terminate()`, StartSession ya ha guardado y cerrado la sesión, así
        // que `isStarted()` devuelve false SIEMPRE y no se anotaría ni una
        // visita. El identificador, en cambio, sigue ahí, que es lo único que
        // hace falta.
        if (! $request->hasSession() || blank($request->session()->getId())) {
            return false;
        }

        return ! Str::contains(Str::lower((string) $request->userAgent()), self::BOTS);
    }
}
