@extends('layouts.app')

@section('title', $titulo.' — '.config('huv.institution.short_name'))
@section('description', $texto)

@push('head')
    {{-- Una página de error no aporta nada a un buscador. --}}
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    {{--
        La pantalla de un error, con la cara del portal.

        Antes se servía la de Laravel: seis mil bytes en inglés, sin logotipo,
        sin menú y sin salida. En un portal con más de mil doscientos contenidos
        traídos del sitio anterior, los enlaces rotos existen, y quien cae en uno
        merece algo mejor que «Not Found» y un callejón.

        Lleva el buscador porque casi siempre se llega aquí buscando algo
        concreto: es más útil que una lista de enlaces genéricos.
    --}}
    <div class="bg-page">
        <x-container class="py-14 lg:py-20">
            <div class="mx-auto max-w-[640px] text-center">

                <p class="m-0 font-display text-40 leading-none font-bold text-azure-pale lg:text-[64px]"
                   aria-hidden="true">{{ $codigo }}</p>

                <h1 class="m-0 mt-4 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    {{ $titulo }}
                </h1>

                <p class="m-0 mt-4 text-15 leading-[1.7] text-body">{{ $texto }}</p>

                {{-- El buscador, que es la salida más útil. --}}
                <form action="{{ route('search') }}" method="GET" role="search"
                      aria-label="{{ __('cabecera.buscar.etiqueta') }}"
                      class="mx-auto mt-8 flex w-full max-w-[440px] items-center rounded-[26px] border border-stroke
                             bg-card py-1 pr-1 pl-[18px]">
                    <label for="huv-buscar-error" class="sr-only">{{ __('cabecera.buscar.etiqueta') }}</label>
                    <input id="huv-buscar-error" type="search" name="q" autocomplete="off"
                           placeholder="{{ __('cabecera.buscar.etiqueta') }}"
                           class="min-w-0 flex-1 border-0 bg-transparent py-[6px] text-14 text-ink outline-0 placeholder:text-muted">
                    <button type="submit"
                            class="flex size-9 shrink-0 items-center justify-center rounded-full border-0 bg-azure
                                   text-on-accent transition-colors hover:bg-azure-dark">
                        <span class="sr-only">{{ __('cabecera.buscar.boton') }}</span>
                        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.6-3.6" />
                        </svg>
                    </button>
                </form>

                <ul class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-14">
                    <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                    <li><a href="{{ route('transparency') }}" class="text-link">{{ __('menu.nav.transparencia') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="text-link">{{ __('paginas.contacto.titulo') }}</a></li>
                </ul>
            </div>
        </x-container>
    </div>
@endsection
