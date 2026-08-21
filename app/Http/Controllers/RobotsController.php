<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * robots.txt.
 *
 * Se sirve desde una ruta y no como fichero estático en public/ por una sola
 * razón: la directiva Sitemap tiene que llevar la dirección absoluta, y en un
 * fichero estático habría que escribir el dominio a mano. Escrito así, sale
 * siempre el del sitio que esté respondiendo, sin que nadie tenga que acordarse
 * de cambiarlo al desplegar.
 *
 * Lo que se cierra es lo que no aporta nada a un buscador y multiplica
 * direcciones casi iguales: la administración, el acceso y las combinaciones de
 * filtros del buscador.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lineas = [
            'User-agent: *',
            'Disallow: /administracion/',
            'Disallow: /ingresar',
            'Disallow: /buscar',
            '',
            'Sitemap: '.route('sitemap.index'),
            '',
        ];

        return response(implode("\n", $lineas), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
