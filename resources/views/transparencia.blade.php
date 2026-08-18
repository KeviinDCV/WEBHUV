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
        apartado en una auditoría. Por eso va escrita en el rótulo del enlace y
        sale del orden de la lista, para que no pueda descuadrarse al añadir una
        entrada en medio.

        Los grupos se pliegan, y ahí NO se copia al portal: allí los doce salen
        abiertos de golpe: más de cien enlaces seguidos en los que no se
        distingue dónde empieza uno y acaba otro, y una barra de desplazamiento
        que no termina. Cerrados, la página cabe en una pantalla y se ve el
        índice entero, que es justo para lo que sirve.

        Con <details> y no con un desplegable a mano: funciona con JavaScript
        desactivado, el teclado lo abre con Enter sin que haya que programarlo,
        y el navegador anuncia solo si está abierto o cerrado. Lo que va dentro
        está en el HTML aunque el grupo esté plegado, así que lo encuentran los
        buscadores; que además el Ctrl+F del navegador lo despliegue solo, eso
        depende del navegador y no se da por hecho.
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

                <div class="mt-8 flex flex-col gap-3">
                    @foreach ($indice['groups'] as $indiceGrupo => $grupo)
                        @php $numero = $indiceGrupo + 1; @endphp

                        <details id="huv-transparencia-{{ $numero }}"
                                 class="group overflow-hidden rounded-[3px] border border-stroke bg-card">
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-[14px]
                                            text-heading hover:bg-tint lg:px-6
                                            [&::-webkit-details-marker]:hidden">
                                {{-- El número va oculto a la voz: el rótulo de cada entrada
                                     ya lo lleva delante, y repetirlo lo leería dos veces. --}}
                                <span aria-hidden="true"
                                      class="flex size-7 shrink-0 items-center justify-center rounded-full
                                             bg-navy font-display text-13 font-bold text-on-brand">
                                    {{ $numero }}
                                </span>

                                {{-- Encabezado de verdad, dentro del <summary>: el modelo de
                                     contenido de <summary> admite uno, y sin él la página se
                                     quedaba con un solo encabezado —el <h1>—. Quien navega con
                                     lector de pantalla salta de encabezado en encabezado para
                                     recorrer un índice como este, y al convertir los grupos en
                                     desplegables se perdieron los doce de golpe. --}}
                                <h2 class="m-0 flex-1 font-display text-16-5 font-bold">{{ $grupo['label'] }}</h2>

                                {{-- La flecha gira al abrir. Decorativa: quien no la ve ya
                                     tiene el estado en el propio <summary>. --}}
                                <svg class="size-4 shrink-0 text-muted transition-transform group-open:rotate-180"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </summary>

                            <ol class="m-0 list-none border-t border-stroke p-0">
                                @foreach ($grupo['items'] as $indiceEntrada => $entrada)
                                    @php $orden = $numero.'.'.($indiceEntrada + 1); @endphp

                                    <li class="border-b border-rule last:border-b-0">
                                        <x-transparency-link :link="$entrada" :order="$orden" />

                                        @if (! empty($entrada['children']))
                                            {{-- Sin fondo gris: el azul del enlace sobre «tint» se
                                                 queda en 4,17:1 y el mínimo para texto es 4,5:1.
                                                 El tercer nivel ya se distingue por el sangrado y
                                                 por la raya de arriba. --}}
                                            <ol class="m-0 list-none border-t border-rule p-0">
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
                        </details>
                    @endforeach
                </div>
            </div>
        </x-container>
    </div>
@endsection
