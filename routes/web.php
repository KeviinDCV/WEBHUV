<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/contenidos/{slug}', [ContentController::class, 'show'])->name('contents.show');

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

        // Contenidos: noticias, comunicados y notificaciones judiciales.
        Route::get('contenidos/nuevo', [AdminContentController::class, 'create'])->name('contents.create');
        Route::post('contenidos', [AdminContentController::class, 'store'])->name('contents.store');
        Route::get('contenidos/{content}/editar', [AdminContentController::class, 'edit'])->name('contents.edit');
        Route::put('contenidos/{content}', [AdminContentController::class, 'update'])->name('contents.update');
        Route::delete('contenidos/{content}', [AdminContentController::class, 'destroy'])->name('contents.destroy');

        // Biblioteca de imágenes reutilizables.
        Route::post('biblioteca/categorias', [MediaLibraryController::class, 'storeCategory'])->name('library.categories.store');
        Route::post('biblioteca/imagenes', [MediaLibraryController::class, 'storeImage'])->name('library.images.store');
        Route::delete('biblioteca/imagenes/{image}', [MediaLibraryController::class, 'destroyImage'])->name('library.images.destroy');

        // Acciones rápidas del menú de cada contenido.
        Route::post('contenidos/{content}/destacar', [AdminContentController::class, 'feature'])->name('contents.feature');
        Route::post('contenidos/{content}/activar', [AdminContentController::class, 'toggleActive'])->name('contents.active');
        Route::post('contenidos/{content}/ocultar', [AdminContentController::class, 'toggleHidden'])->name('contents.hidden');
    });
