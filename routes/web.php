<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ContentBlockController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\ShortcutController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/contenidos/{slug}', [ContentController::class, 'show'])->name('contents.show');

// Temas documentales: Presupuesto, Planes, Contratación… Los que todavía no se
// han importado siguen resolviéndose contra el portal actual; ver
// App\Support\LegacyLink.
Route::get('/tema/{topic}', [TopicController::class, 'show'])->name('topics.show');
Route::get('/tema/{topic}/{document}', [TopicController::class, 'showDocument'])->name('documents.show');

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

        // Configuración de los bloques de la portada.
        Route::get('bloques/{block}', [ContentBlockController::class, 'edit'])->name('blocks.edit');
        Route::put('bloques/{block}', [ContentBlockController::class, 'update'])->name('blocks.update');

        // Agenda de eventos.
        Route::get('eventos/bloque', [EventController::class, 'editBlock'])->name('events.block.edit');
        Route::put('eventos/bloque', [EventController::class, 'updateBlock'])->name('events.block.update');
        Route::post('eventos/categorias', [EventController::class, 'storeCategory'])->name('events.categories.store');

        Route::get('eventos/nuevo', [EventController::class, 'create'])->name('events.create');
        Route::post('eventos', [EventController::class, 'store'])->name('events.store');
        Route::get('eventos/{event}/editar', [EventController::class, 'edit'])->name('events.edit');
        Route::put('eventos/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('eventos/{event}', [EventController::class, 'destroy'])->name('events.destroy');

        // Barras de accesos directos.
        Route::get('accesos/{block}', [ShortcutController::class, 'edit'])->name('shortcuts.edit');
        Route::put('accesos/{block}', [ShortcutController::class, 'update'])->name('shortcuts.update');
        Route::get('accesos/{block}/nuevo', [ShortcutController::class, 'create'])->name('shortcuts.create');
        Route::post('accesos/{block}', [ShortcutController::class, 'store'])->name('shortcuts.store');
        Route::get('accesos/{block}/{shortcut}/editar', [ShortcutController::class, 'editShortcut'])->name('shortcuts.item.edit');
        Route::put('accesos/{block}/{shortcut}', [ShortcutController::class, 'updateShortcut'])->name('shortcuts.item.update');
        Route::delete('accesos/{block}/{shortcut}', [ShortcutController::class, 'destroyShortcut'])->name('shortcuts.item.destroy');

        // Biblioteca de imágenes reutilizables.
        Route::post('biblioteca/categorias', [MediaLibraryController::class, 'storeCategory'])->name('library.categories.store');
        Route::post('biblioteca/imagenes', [MediaLibraryController::class, 'storeImage'])->name('library.images.store');
        Route::delete('biblioteca/imagenes/{image}', [MediaLibraryController::class, 'destroyImage'])->name('library.images.destroy');

        // Documentos de los temas: Presupuesto, Planes, Contratación…
        Route::post('temas/{topic}/categorias', [DocumentController::class, 'storeCategory'])
            ->name('documents.categories.store');
        Route::post('temas/{topic}/documentos', [DocumentController::class, 'store'])->name('documents.store');
        Route::put('temas/{topic}/documentos/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('temas/{topic}/documentos/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('temas/{topic}/documentos/{document}/destacar', [DocumentController::class, 'feature'])->name('documents.feature');
        Route::post('temas/{topic}/documentos/{document}/activar', [DocumentController::class, 'toggleActive'])->name('documents.active');
        Route::post('temas/{topic}/documentos/{document}/ocultar', [DocumentController::class, 'toggleHidden'])->name('documents.hidden');

        // Acciones rápidas del menú de cada contenido.
        Route::post('contenidos/{content}/destacar', [AdminContentController::class, 'feature'])->name('contents.feature');
        Route::post('contenidos/{content}/activar', [AdminContentController::class, 'toggleActive'])->name('contents.active');
        Route::post('contenidos/{content}/ocultar', [AdminContentController::class, 'toggleHidden'])->name('contents.hidden');
    });
