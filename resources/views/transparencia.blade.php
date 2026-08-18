@extends('layouts.app')

@section('title', 'Transparencia — '.config('huv.institution.short_name'))
@section('description', 'Índice de transparencia y acceso a la información pública del '
    .config('huv.institution.name_plain').', según la Resolución 1519 de 2020.')

@section('content')
    {{--
        Índice de Transparencia.

        Una página, no un tema: es el mapa que exige la Resolución 1519 de 2020,
        doce grupos numerados que llevan a donde de verdad está cada cosa. No
        tiene contenido propio —ni fichas, ni archivos, ni fechas—, así que su
        árbol vive en configuración, igual que el menú principal, y los destinos
        los resuelve LegacyLink: lo migrado enlaza aquí dentro y lo que no,
        todavía al portal anterior.

        La numeración es la del texto legal, no un adorno: es como se cita cada
        apartado en las auditorías. Por eso va escrita en el rótulo del enlace y
        sale del orden de la lista, para que no pueda descuadrarse al añadir una
        entrada en medio.
    --}}
    @php $indice = config('huv.transparency_index'); @endphp

    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[900px]">

                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ $indice['title'] }}</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    {{ $indice['title'] }}
                </h1>

                <div class="mt-8 flex flex-col gap-6">
                    @foreach ($indice['groups'] as $indiceGrupo => $grupo)
                        @php $numero = $indiceGrupo + 1; @endphp

                        <section aria-labelledby="huv-transparencia-{{ $numero }}"
                                 class="overflow-hidden rounded-[3px] border border-stroke bg-card">
                            <h2 id="huv-transparencia-{{ $numero }}"
                                class="m-0 flex items-center gap-3 border-b border-stroke px-4 py-[14px]
                                       font-display text-16-5 font-bold text-heading lg:px-6">
                                {{-- El número va aparte del título y oculto a la voz: el
                                     rótulo de cada entrada ya lo lleva delante, y
                                     repetirlo haría que se leyera dos veces. --}}
                                <span aria-hidden="true"
                                      class="flex size-7 shrink-0 items-center justify-center rounded-full
                                             bg-navy font-display text-13 font-bold text-on-brand">
                                    {{ $numero }}
                                </span>
                                {{ $grupo['label'] }}
                            </h2>

                            <ol class="m-0 list-none p-0">
                                @foreach ($grupo['items'] as $indiceEntrada => $entrada)
                                    @php $orden = $numero.'.'.($indiceEntrada + 1); @endphp

                                    <li class="border-b border-rule last:border-b-0">
                                        <x-transparency-link :link="$entrada" :order="$orden" />

                                        @if (! empty($entrada['children']))
                                            <ol class="m-0 list-none border-t border-rule bg-tint p-0">
                                                @foreach ($entrada['children'] as $indiceHija => $hija)
                                                    <li class="border-b border-rule last:border-b-0">
                                                        <x-transparency-link :link="$hija"
                                                                             :order="$orden.'.'.($indiceHija + 1)"
                                                                             nested />
                                                    </li>
                                                @endforeach
                                            </ol>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endforeach
                </div>
            </div>
        </x-container>
    </div>
@endsection
