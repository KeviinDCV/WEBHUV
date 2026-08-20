@extends('layouts.app')

@section('title', __('paginas.pqrds.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.pqrds.descripcion', [
    'entidad' => config('huv.institution.name_plain'),
]))

@php
    /*
     | Los diez trámites del portal, con su definición literal.
     |
     | El texto español es el suyo palabra por palabra, erratas incluidas
     | —«competenten», «la presentación indebida de un servicio»—: son
     | definiciones legales que la entidad publica así, y corregirlas por
     | nuestra cuenta cambiaría un texto oficial.
     |
     | El formulario que las recibe NO es de este aplicativo: vive en la
     | plataforma (pqrs-api.micolombiadigital.gov.co) y su dirección se pide con
     | una credencial que este portal no tiene. Hasta que el hospital decida con
     | qué lo reemplaza, cada botón lleva a la página donde el trámite funciona
     | hoy, en vez de a un formulario nuestro que no radicaría nada.
     |
     | La clave del icono es también la del rótulo: el archivo «peticion.svg» y
     | «paginas.pqrds.tramites.peticion» no pueden desalinearse.
     */
    $tramites = ['peticion', 'queja', 'reclamo', 'sugerencia', 'felicitacion', 'denuncia',
        'solicitud-informacion', 'solicitud-datos', 'cita'];

    // Donde el trámite se radica de verdad, hoy.
    $radicacion = rtrim((string) config('huv.legacy_base'), '/').'/peticiones-quejas-reclamos';
@endphp

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.pqrds.titulo') }}</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-balance text-heading lg:text-33">
                    {{ __('paginas.pqrds.encabezado') }}
                </h1>

                <p class="m-0 mt-3 max-w-[70ch] text-14-5 text-muted">
                    {{ __('paginas.pqrds.entradilla') }}
                </p>

                <h2 id="huv-tipos-de-solicitud"
                    class="m-0 mt-8 font-display text-19 font-bold text-heading lg:text-21">
                    {{ __('paginas.pqrds.seleccion') }}
                </h2>

                {{-- Una lista y no una sucesión de <div>: son diez opciones
                     equivalentes, y quien navega con lector de pantalla merece
                     saber cuántas hay antes de recorrerlas. --}}
                <ul aria-labelledby="huv-tipos-de-solicitud" class="m-0 mt-4 flex list-none flex-col p-0">
                    @foreach ($tramites as $tramite)
                        @php($rotulo = 'paginas.pqrds.tramites.'.str_replace('-', '_', $tramite))

                        <li class="flex gap-5 border-t border-line py-6">
                            {{-- Decorativo: lo que dice el icono ya está en el
                                 título que tiene al lado. --}}
                            <img src="{{ asset('img/pqrds/'.$tramite.'.svg') }}" alt=""
                                 aria-hidden="true" width="44" height="52" loading="lazy"
                                 class="huv-pqrds-icono mt-1 hidden h-[52px] w-[44px] shrink-0 object-contain sm:block">

                            <div class="min-w-0 flex-1">
                                <h3 class="m-0 font-display text-17 font-bold text-heading">
                                    {{ __($rotulo.'.titulo') }}
                                </h3>

                                <p class="m-0 mt-1 text-14 leading-[1.6] text-pretty text-body">
                                    {{ __($rotulo.'.definicion') }}
                                </p>

                                <p class="m-0 mt-3">
                                    <a href="{{ $radicacion }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[9px]
                                              font-display text-12-5 font-bold tracking-[0.04em] text-on-accent
                                              uppercase no-underline transition-colors hover:bg-azure-dark
                                              hover:no-underline">
                                        {{ __($rotulo.'.boton') }}
                                        <span class="sr-only">{{ __('paginas.enlace.pestana_nueva') }}</span>
                                    </a>
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- ---------------- Seguimiento ---------------- --}}
                <div class="mt-8 rounded-[4px] bg-tint px-5 py-6 lg:px-8">
                    <p class="m-0 max-w-[70ch] text-14-5 leading-[1.6] text-heading">
                        {{ __('paginas.pqrds.seguimiento.texto') }}
                    </p>

                    <p class="m-0 mt-4">
                        <a href="{{ $radicacion }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-6 py-[10px]
                                  font-display text-12-5 font-bold tracking-[0.04em] text-on-accent uppercase
                                  no-underline transition-colors hover:bg-azure-dark hover:no-underline">
                            {{ __('paginas.pqrds.seguimiento.boton') }}
                            <span class="sr-only">{{ __('paginas.enlace.pestana_nueva') }}</span>
                        </a>
                    </p>
                </div>
            </div>
        </x-container>
    </div>
@endsection
