@php
    use App\Models\Content;
    use App\Support\SiteSearch;
@endphp

@extends('layouts.app')

@section('title', ($terms !== '' ? $terms.' — ' : '').__('paginas.buscador.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.buscador.descripcion', ['entidad' => config('huv.institution.name_plain')]))

@push('head')
    {{-- Una página de resultados no aporta nada a un buscador y multiplica las
         direcciones casi iguales. --}}
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">

            {{-- Rastro de navegación --}}
            <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                    <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                    <li aria-hidden="true">›</li>
                    <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.buscador.titulo') }}</li>
                </ol>
            </nav>

            <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                {{ __('paginas.buscador.titulo') }}
            </h1>

            {{--
                El formulario repite el término y los filtros: es la única forma
                de corregir una búsqueda sin volver a la cabecera, y de que el
                botón «Atrás» del navegador devuelva lo que se escribió.
            --}}
            {{-- Con nombre propio: en esta página hay dos regiones de búsqueda
                 —esta y la de la cabecera— y sin distinguirlas, la lista de
                 regiones de un lector de pantalla ofrece dos entradas iguales. --}}
            <form action="{{ route('search') }}" method="GET" role="search" class="mt-6"
                  aria-label="{{ __('paginas.buscador.que_buscas') }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="q" class="text-13-5 font-semibold text-heading">
                            {{ __('paginas.buscador.que_buscas') }}
                        </label>
                        <input id="q" name="q" type="search" value="{{ $terms }}" autocomplete="off"
                               placeholder="{{ __('cabecera.buscar.etiqueta') }}"
                               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                    </div>

                    <div>
                        <label for="tipo" class="text-13-5 font-semibold text-heading">
                            {{ __('paginas.buscador.tipo.etiqueta') }}
                        </label>
                        <select id="tipo" name="tipo"
                                class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink sm:w-[210px]">
                            <option value="" @selected($tipo === '')>{{ __('paginas.buscador.tipo.todos') }}</option>
                            <option value="contenidos" @selected($tipo === 'contenidos')>{{ __('paginas.buscador.tipo.contenidos') }}</option>
                            <option value="temas" @selected($tipo === 'temas')>{{ __('paginas.buscador.tipo.temas') }}</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="shrink-0 rounded-[3px] border-0 bg-azure px-6 py-[11px] font-display text-13-5
                                   font-bold text-on-accent transition-colors hover:bg-azure-dark">
                        {{ __('cabecera.buscar.boton') }}
                    </button>
                </div>

                {{-- Las fechas, como en el portal actual. --}}
                <fieldset class="mt-3 flex flex-wrap items-end gap-3 border-0 p-0">
                    <legend class="p-0 text-13-5 font-semibold text-heading">
                        {{ __('paginas.buscador.fechas.etiqueta') }}
                    </legend>
                    <div>
                        <label for="desde" class="text-12-5 text-muted">{{ __('paginas.buscador.fechas.desde') }}</label>
                        <input id="desde" name="desde" type="date" value="{{ $desde }}"
                               class="mt-1 block rounded-[3px] border border-stroke bg-card px-3 py-[8px] text-14 text-ink">
                    </div>
                    <div>
                        <label for="hasta" class="text-12-5 text-muted">{{ __('paginas.buscador.fechas.hasta') }}</label>
                        <input id="hasta" name="hasta" type="date" value="{{ $hasta }}"
                               class="mt-1 block rounded-[3px] border border-stroke bg-card px-3 py-[8px] text-14 text-ink">
                    </div>
                </fieldset>
            </form>

            <hr class="my-8 border-0 border-t border-line">

            @if (! $buscable)
                {{-- Sin término no se enseña un listado vacío como si no hubiera
                     nada: se dice qué hay que hacer. --}}
                <p class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    {{ $terms === ''
                        ? __('paginas.buscador.vacio')
                        : __('paginas.buscador.corto', ['minimo' => SiteSearch::MIN_LENGTH]) }}
                </p>
            @elseif ($results->isEmpty())
                <p class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    {!! __('paginas.buscador.sin_resultados', ['termino' => e($terms)]) !!}
                </p>
            @else
                {{-- Sin aria-live: el recuento ya viene en el HTML de la
                     respuesta, así que la región nunca cambia y no anuncia nada.
                     Declararla solo promete un comportamiento que no ocurre. --}}
                <p class="m-0 mb-5 text-13-5 text-muted">
                    {{-- El recuento manda al plural, pero el que se pinta es el
                         número ya formateado con el separador del idioma. --}}
                    {!! trans_choice('paginas.buscador.recuento', $results->total(), [
                        'total' => number_format($results->total(), 0, '', __('componentes.numero.millares')),
                        'termino' => e($terms),
                    ]) !!}
                </p>

                {{-- Cada tarjeta mide lo que mide su texto, como en el resto de
                     listados. Aquí no hay mampostería: los resultados van en una
                     sola columna, así que no hay hueco que rellenar. --}}
                <ul class="grid grid-cols-1 items-start gap-5">
                    @foreach ($results as $result)
                        <li>
                            @if ($result instanceof Content)
                                <x-content-card :content="$result" layout="topic" />
                            @else
                                <x-topic-item-card :item="$result" />
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    {{ $results->links() }}
                </div>
            @endif
        </x-container>
    </div>
@endsection
