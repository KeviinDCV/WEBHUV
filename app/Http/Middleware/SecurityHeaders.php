<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las cabeceras de seguridad de cada respuesta.
 *
 * Ninguna cambia lo que se ve: son instrucciones al navegador sobre lo que
 * puede hacer con la página. Se ponen aquí y no en la configuración del
 * servidor para que viajen con el código y no dependan de que quien despliegue
 * se acuerde.
 *
 * Lo que NO está aquí, y es deliberado:
 *
 * · Content-Security-Policy. El sitio usa la compilación normal de Alpine, que
 *   evalúa cada expresión de `x-data` y `x-show`, así que una política aplicada
 *   exigiría `unsafe-eval` —con lo que casi no protege— o migrar a la
 *   compilación CSP, que obliga a reescribir todos los componentes. Y el fallo
 *   sería mudo: sin permiso, Alpine no arranca, el `x-cloak` no se levanta y la
 *   portada queda en blanco. Merece su propia tanda, con una política en modo
 *   informe y semanas de observación antes de aplicarla.
 *
 * · La compresión y el HTTPS, que dependen del servidor que haya delante y no
 *   del código.
 */
class SecurityHeaders
{
    /**
     * Permisos del navegador que el sitio no usa.
     *
     * Comprobado en resources/js: no hay cámara, micrófono, ubicación ni pagos.
     * Declararlos vacíos evita que un script de terceros —o uno inyectado— los
     * pida en nombre del hospital.
     */
    private const DENIED = [
        'accelerometer', 'autoplay', 'camera', 'display-capture', 'encrypted-media',
        'geolocation', 'gyroscope', 'magnetometer', 'microphone', 'midi', 'payment',
        'usb', 'xr-spatial-tracking',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Un navegador que adivina el tipo de un fichero puede acabar
        // ejecutando como script algo que se subió como imagen.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Al salir del sitio se manda el dominio, no la dirección completa: la
        // ruta puede llevar el término que alguien buscó o el documento que
        // estaba leyendo, y eso no tiene por qué viajar a un tercero.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // El portal no se incrusta en ningún sitio. Sin esto, cualquiera puede
        // ponerlo dentro de un marco invisible y hacer que quien cree pulsar en
        // su página esté pulsando en la nuestra.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        $response->headers->set(
            'Permissions-Policy',
            collect(self::DENIED)->map(fn (string $permiso): string => $permiso.'=()')->implode(', ')
        );

        // Delata la versión exacta de PHP, que es media pista para quien busca
        // un fallo conocido. PHP la pone solo si `expose_php` está activo.
        $response->headers->remove('X-Powered-By');

        // HSTS solo tiene sentido sobre HTTPS: por HTTP el navegador la ignora.
        //
        // Y SIN `includeSubDomains`: «cross.huv.gov.co» —el formulario de
        // solicitudes que enlaza el propio portal— se sirve por HTTP plano, así
        // que incluir los subdominios haría que el navegador se negara a
        // abrirlo en cuanto alguien pasara por aquí. Cuando ese sistema tenga
        // certificado, se añade.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
