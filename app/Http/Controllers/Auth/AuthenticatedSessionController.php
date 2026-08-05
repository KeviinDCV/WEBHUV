<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Inicio y cierre de sesión del personal del hospital.
 *
 * No hay registro público: las cuentas se crean desde la consola con
 * `php artisan huv:usuario`.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Se renueva el identificador de sesión para cerrar la puerta a la
        // fijación de sesión (session fixation).
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('status', 'Sesión iniciada. Ya puede editar el contenido del portal.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Sesión cerrada correctamente.');
    }
}
