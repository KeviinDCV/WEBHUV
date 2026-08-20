@extends('layouts.app')

@section('title', __('paginas.acceso.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.acceso.descripcion'))

@push('head')
    {{-- Una pantalla de acceso no aporta nada a los buscadores. --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <div class="bg-surface">
        <x-container class="flex justify-center py-12 lg:py-20">
            <div class="w-full max-w-[440px]">

                <div class="rounded-[4px] border border-line border-t-4 border-t-rule-accent bg-card
                            px-6 py-8 shadow-[0_10px_30px_rgba(23,32,64,0.08)] sm:px-9">

                    <h1 class="m-0 font-display text-24 font-bold tracking-[-0.01em] text-heading">
                        {{ __('paginas.acceso.titulo') }}
                    </h1>
                    <p class="m-0 mt-2 mb-7 text-14 leading-[1.6] text-muted">
                        {{ __('paginas.acceso.entradilla') }}
                    </p>

                    @if (session('status'))
                        <p class="m-0 mb-6 rounded-[3px] border border-line bg-tint px-4 py-3 text-13-5 text-heading"
                           role="status">
                            {{ session('status') }}
                        </p>
                    @endif

                    {{-- Resumen de errores: quien usa lector de pantalla se entera
                         del fallo sin tener que recorrer el formulario campo a campo. --}}
                    @if ($errors->any())
                        <div role="alert" tabindex="-1" x-init="$el.focus()"
                             class="mb-6 rounded-[3px] border border-l-4 border-line border-l-danger
                                    bg-danger-surface px-4 py-3">
                            <p class="m-0 text-13-5 font-semibold text-danger">
                                {{ __('paginas.acceso.error') }}
                            </p>
                            <ul class="m-0 mt-1 flex flex-col gap-1 text-13-5 text-danger">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
                        @csrf

                        <div class="flex flex-col gap-[6px]">
                            <label for="email" class="text-13-5 font-semibold text-heading">
                                {{ __('paginas.acceso.correo') }}
                            </label>
                            <input id="email" name="email" type="email" required autofocus
                                   autocomplete="username" inputmode="email"
                                   value="{{ old('email') }}"
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                   class="rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink
                                          placeholder:text-faint">
                            @error('email')
                                <p id="email-error" class="m-0 text-12-5 text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-[6px]">
                            <label for="password" class="text-13-5 font-semibold text-heading">
                                {{ __('paginas.acceso.contrasena') }}
                            </label>
                            <input id="password" name="password" type="password" required
                                   autocomplete="current-password"
                                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                   class="rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                            @error('password')
                                <p id="password-error" class="m-0 text-12-5 text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="remember" class="flex items-center gap-2 text-13-5 text-body">
                            <input id="remember" name="remember" type="checkbox" value="1"
                                   @checked(old('remember'))
                                   class="size-4 rounded-[2px] border-stroke accent-azure">
                            {{ __('paginas.acceso.recordar') }}
                        </label>

                        <button type="submit"
                                class="mt-1 rounded-[3px] border-0 bg-navy px-6 py-3 font-display text-14
                                       font-semibold text-on-brand transition-colors hover:bg-navy-dark">
                            {{ __('paginas.acceso.entrar') }}
                        </button>
                    </form>
                </div>

                <p class="m-0 mt-5 text-center text-12-5 leading-[1.6] text-muted">
                    {{ __('paginas.acceso.sin_cuenta') }}
                </p>
            </div>
        </x-container>
    </div>
@endsection
