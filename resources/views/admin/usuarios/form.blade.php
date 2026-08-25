@php
    use App\Models\User;

    $backUrl = route('admin.users.index');
@endphp

@extends('layouts.admin')

@section('title', __('admin-usuarios.form.titulo'))
@section('heading', __('admin-usuarios.form.titulo'))

@section('content')
    {{--
        Alta de una cuenta.

        La contraseña la escribe quien crea la cuenta y se entrega a mano: este
        portal no manda correos de invitación ni de recuperación, así que montar
        un flujo a medias sería peor que decirlo claro, y por eso el aviso está
        en la pantalla y no en la cabeza de nadie.
    --}}
    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-[620px]">
        @csrf

        <div class="mb-7">
            <label for="name" class="text-13-5 font-semibold text-heading">
                {{ __('admin-usuarios.campo.nombre') }}
            </label>
            <input id="name" name="name" type="text" maxlength="120" required autocomplete="off"
                   value="{{ old('name') }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <div class="mb-7">
            <label for="email" class="text-13-5 font-semibold text-heading">
                {{ __('admin-usuarios.campo.correo') }}
            </label>
            <input id="email" name="email" type="email" maxlength="255" required autocomplete="off"
                   value="{{ old('email') }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <div class="mb-7">
            <label for="password" class="text-13-5 font-semibold text-heading">
                {{ __('admin-usuarios.campo.contrasena') }}
            </label>
            {{-- «new-password» y no «off»: es lo que hace que el gestor de
                 contraseñas ofrezca generar una nueva en vez de rellenar la de
                 quien está creando la cuenta. --}}
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            <p class="m-0 mt-1 text-12-5 text-faint">{{ __('admin-usuarios.campo.contrasena_ayuda') }}</p>
        </div>

        <div class="mb-7">
            <label for="password_confirmation" class="text-13-5 font-semibold text-heading">
                {{ __('admin-usuarios.campo.contrasena_repetir') }}
            </label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <p class="mb-7 max-w-[70ch] rounded-[3px] border border-line border-l-4 border-l-rule-accent
                  bg-card px-4 py-3 text-13-5 leading-[1.6] text-body">
            {{ __('admin-usuarios.aviso.contrasena') }}
        </p>

        <fieldset class="mb-8 border-0 p-0">
            <legend class="mb-2 p-0 text-13-5 font-semibold text-heading">
                {{ __('admin-usuarios.campo.rol') }}
            </legend>

            <div class="flex flex-col gap-4">
                @foreach (User::ROLES as $rol)
                    <label class="flex items-start gap-2 text-14 text-ink">
                        <input type="radio" name="role" value="{{ $rol }}"
                               @checked(old('role', $usuario->role) === $rol)
                               class="mt-[3px] shrink-0">
                        <span>
                            {{ __('admin-usuarios.rol.'.$rol) }}
                            <span class="mt-[2px] block text-12-5 leading-[1.5] text-faint">
                                {{ __('admin-usuarios.rol_explicado.'.$rol) }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-5">
            <button type="submit"
                    class="rounded-full border-0 bg-azure px-6 py-[10px] font-display text-14 font-bold
                           text-on-accent transition-colors hover:bg-azure-dark">
                {{ __('admin-usuarios.form.guardar') }}
            </button>

            <a href="{{ route('admin.users.index') }}"
               class="text-13-5 font-semibold text-link underline underline-offset-4">
                {{ __('admin-usuarios.form.cancelar') }}
            </a>
        </div>
    </form>
@endsection
