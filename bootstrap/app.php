<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Después de la sesión: el idioma elegido se guarda en ella.
        $middleware->web(append: [App\Http\Middleware\SetLocale::class]);

        /*
         | El recuento de visitas, también en «web» y no en la pila global.
         |
         | Necesita la sesión para saber si dos páginas las vio el mismo
         | navegador, y la sesión solo existe dentro de este grupo. Anota al
         | terminar —en `terminate()`— así que la escritura ocurre con la
         | respuesta ya enviada y no la retrasa.
        */
        $middleware->web(append: [App\Http\Middleware\RecordVisit::class]);

        /*
         | Y antes de resolver los modelos de la ruta.
         |
         | Añadido al final del grupo, el idioma se fijaba DESPUÉS de buscar en
         | la base el tema o el contenido de la dirección. Un enlace roto lanza
         | su 404 en ese paso, así que la pantalla de error se pintaba siempre en
         | español aunque se pidiera en inglés —y en un portal con mil doscientos
         | contenidos traídos de otro sitio, los enlaces rotos existen—.
         |
         | Se declara el orden explícito de Laravel con SetLocale intercalado:
         | después de la sesión, de la que lee la preferencia, y antes de
         | SubstituteBindings, que es quien lanza el 404.
        */
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            App\Http\Middleware\SetLocale::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        /*
         | Las cabeceras de seguridad, en la pila global y no en la de «web».
         |
         | Una dirección que no existe no llega a entrar en el grupo «web», así
         | que la página de error se servía sin ninguna de ellas. Y son
         | justamente las respuestas raras —un 404, un fichero, un error— las
         | que conviene que las lleven.
        */
        $middleware->append(App\Http\Middleware\SecurityHeaders::class);

        /*
         | Detrás de un proxy que termine el cifrado.
         |
         | Sin esto, Laravel ve la petición interna —que llega por HTTP— y arma
         | las direcciones absolutas con ese esquema: la canónica, `og:url` y la
         | del sitemap saldrían en http dentro de un sitio https, que para un
         | buscador son dos sitios distintos.
         |
         | Los proxies se declaran con TRUSTED_PROXIES en el .env; mientras no
         | haya ninguno, esto no cambia nada.
        */
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES') ? explode(',', (string) env('TRUSTED_PROXIES')) : [],
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
