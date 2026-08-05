<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Acceso del personal
|--------------------------------------------------------------------------
| No existe registro público: las cuentas se crean con `php artisan huv:usuario`.
| La ruta de inicio de sesión debe llamarse «login» porque es la que usa el
| middleware `auth` para redirigir a quien no ha entrado.
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/ingresar', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/salir', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Administración del contenido
|--------------------------------------------------------------------------
*/
Route::middleware('auth')
    ->prefix('administracion')
    ->name('admin.')
    ->group(function (): void {
        Route::post('banners/orden', [BannerController::class, 'arrange'])->name('banners.arrange');

        Route::get('banners', [BannerController::class, 'index'])->name('banners.index');
        Route::get('banners/nuevo', [BannerController::class, 'create'])->name('banners.create');
        Route::post('banners', [BannerController::class, 'store'])->name('banners.store');
        Route::get('banners/{banner}/editar', [BannerController::class, 'edit'])->name('banners.edit');
        Route::put('banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    });
