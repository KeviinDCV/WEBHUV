<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fechas relativas en español («Hace 6 horas» en el pie institucional).
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        /*
        | Lo que solo puede el administrador.
        |
        | Un único permiso, y no uno por pantalla, porque la línea que separa
        | los dos roles es una sola: el operador administra el CONTENIDO del
        | portal y el administrador además la HERRAMIENTA —quién entra y qué
        | uso se le da—. Mientras la línea siga siendo esa, partirlo en varios
        | permisos solo daría sitios donde equivocarse.
        |
        | Se declara como Gate y no como middleware propio para poder usar el
        | mismo nombre en los tres sitios donde hace falta: `can:administrar`
        | en las rutas, `@can('administrar')` en las plantillas y
        | `$user->can('administrar')` en las pruebas.
        */
        Gate::define('administrar', fn (User $user): bool => $user->isAdmin());
    }
}
