<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta de cuentas del portal, desde el navegador.
 *
 * Hasta ahora la única vía era `php artisan huv:usuario`, que sigue existiendo
 * y sigue siendo la de la primera cuenta: sin cuentas no hay con qué entrar
 * aquí. Esta pantalla es para las siguientes, y solo la ve un administrador.
 *
 * Dos cosas que NO hace, a propósito:
 *
 * · No admite contraseñas flojas. El comando de consola tiene `--simple` para
 *   las cuentas de prueba del portátil de quien programa; una pantalla web
 *   abierta al hospital es otra cosa, y aquí manda siempre la regla larga.
 *
 * · No edita ni borra cuentas. Cambiar el rol de alguien o darlo de baja tiene
 *   consecuencias que conviene pensar despacio —empezando por que un
 *   administrador se quite a sí mismo el permiso y deje el portal sin nadie que
 *   pueda repartirlos—, así que eso todavía no está y se hará cuando se decida
 *   cómo evitarlo.
 */
class UserController extends Controller
{
    /** Quién tiene cuenta y con qué permiso. */
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', [
            'usuario' => new User(['role' => User::ROLE_OPERATOR]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            // La misma regla que el comando de consola sin `--simple`: doce
            // caracteres con letras, números y símbolos.
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],

            'role' => ['required', Rule::in(User::ROLES)],
        ], [
            'email.unique' => __('admin-usuarios.error.correo_repetido'),
        ], [
            'name' => __('admin-usuarios.campo.nombre'),
            'email' => __('admin-usuarios.campo.correo'),
            'password' => __('admin-usuarios.campo.contrasena'),
            'role' => __('admin-usuarios.campo.rol'),
        ]);

        User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'role' => $datos['role'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin-usuarios.mensaje.creada', ['nombre' => $datos['name']]));
    }
}
