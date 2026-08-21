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
