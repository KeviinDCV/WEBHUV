@php
    use App\Http\Controllers\Admin\StatisticsController;

    $backUrl = route('admin.menu.index');

    $formato = fn (float|int $n, int $decimales = 0): string => number_format($n, $decimales, ',', '.');

    // Para dibujar las barras: la más alta ocupa el alto entero.
    $techo = max(1, (int) $porDia->max('visitantes'));
@endphp

@extends('layouts.admin')

@section('title', __('admin-estadisticas.titulo'))
@section('heading', __('admin-estadisticas.titulo'))
@section('subheading', __('admin-estadisticas.subtitulo'))

@section('content')
    {{--
        Estadísticas de uso.

        La gráfica va en HTML y CSS, sin biblioteca: son treinta barras y una
        escala: traerse doscientos kilobytes de JavaScript para eso sería
        desproporcionado, y además el portal no carga nada de fuera.

        Debajo va la misma información en una tabla. No es redundancia: una
        gráfica de barras no se puede leer con un lector de pantalla, y estas
        cifras son justo las que alguien va a querer copiar a un informe.
    --}}
    @unless ($hayDatos)
        <div class="mb-8 rounded-[4px] border border-line border-l-4 border-l-rule-accent bg-card px-5 py-4">
            <h2 class="m-0 font-display text-15 font-bold text-heading">
                {{ __('admin-estadisticas.vacio.titulo') }}
            </h2>
            <p class="m-0 mt-2 max-w-[70ch] text-13-5 leading-[1.6] text-body">
                {{ __('admin-estadisticas.vacio.texto') }}
            </p>
        </div>
    @endunless

    {{-- ---------------- Periodo ---------------- --}}
    <nav aria-label="{{ __('admin-estadisticas.periodo.etiqueta') }}" class="mb-7">
        <ul class="m-0 flex flex-wrap gap-2 p-0">
            @foreach (StatisticsController::PERIODS as $opcion)
                <li class="list-none">
                    <a href="{{ route('admin.statistics.index', ['dias' => $opcion]) }}"
                       @if ($opcion === $dias) aria-current="page" @endif
                       @class([
                           'inline-block rounded-full px-4 py-[7px] text-13-5 font-semibold no-underline',
                           'bg-navy text-on-brand' => $opcion === $dias,
                           'border border-stroke bg-card text-link hover:bg-tint' => $opcion !== $dias,
                       ])>
                        {{ __('admin-estadisticas.periodo.'.$opcion) }}
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="m-0 mt-2 text-12-5 text-faint">
            {{ __('admin-estadisticas.periodo.rango', [
                'desde' => $desde->translatedFormat('j \d\e F \d\e Y'),
                'hasta' => $hasta->translatedFormat('j \d\e F \d\e Y'),
            ]) }}
            @if ($desdeCuando)
                · {{ __('admin-estadisticas.desde_cuando', [
                    'fecha' => \Illuminate\Support\Carbon::parse($desdeCuando)->translatedFormat('j \d\e F \d\e Y'),
                ]) }}
            @endif
        </p>
    </nav>

    {{-- ---------------- Las cuatro cifras ---------------- --}}
    <div class="mb-9 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- La que se pidió, y por eso va primera y más grande. --}}
        <div class="rounded-[4px] border border-rule-accent bg-card px-5 py-4">
            <p class="m-0 font-display text-13 font-bold tracking-[0.04em] text-heading uppercase">
                {{ __('admin-estadisticas.promedio.titulo') }}
            </p>
            <p class="m-0 mt-1 font-display text-33 leading-none font-bold text-navy tabular-nums">
                {{ $formato($promedio, $promedio < 10 ? 1 : 0) }}
            </p>
            <p class="m-0 mt-2 text-12-5 leading-[1.5] text-faint">
                {{ __('admin-estadisticas.promedio.pie', ['dias' => $dias]) }}
            </p>
        </div>

        <div class="rounded-[4px] border border-stroke bg-card px-5 py-4">
            <p class="m-0 font-display text-13 font-bold tracking-[0.04em] text-heading uppercase">
                {{ __('admin-estadisticas.total.titulo') }}
            </p>
            <p class="m-0 mt-1 font-display text-25 leading-none font-bold text-heading tabular-nums">
                {{ $formato($visitantes) }}
            </p>
            <p class="m-0 mt-2 text-12-5 leading-[1.5] text-faint">
                {{ __('admin-estadisticas.total.pie') }}
            </p>
        </div>

        <div class="rounded-[4px] border border-stroke bg-card px-5 py-4">
            <p class="m-0 font-display text-13 font-bold tracking-[0.04em] text-heading uppercase">
                {{ __('admin-estadisticas.paginas.titulo') }}
            </p>
            <p class="m-0 mt-1 font-display text-25 leading-none font-bold text-heading tabular-nums">
                {{ $formato($paginas) }}
            </p>
            <p class="m-0 mt-2 text-12-5 leading-[1.5] text-faint">
                {{ __('admin-estadisticas.paginas.pie', [
                    'media' => $visitantes > 0 ? $formato($paginas / $visitantes, 1) : '0',
                ]) }}
            </p>
        </div>

        <div class="rounded-[4px] border border-stroke bg-card px-5 py-4">
            <p class="m-0 font-display text-13 font-bold tracking-[0.04em] text-heading uppercase">
                {{ __('admin-estadisticas.cumbre.titulo') }}
            </p>
            <p class="m-0 mt-1 font-display text-25 leading-none font-bold text-heading tabular-nums">
                {{ $diaCumbre ? $formato($diaCumbre['visitantes']) : '0' }}
            </p>
            <p class="m-0 mt-2 text-12-5 leading-[1.5] text-faint">
                @if ($diaCumbre && $diaCumbre['visitantes'] > 0)
                    {{ __('admin-estadisticas.cumbre.pie', [
                        'fecha' => $diaCumbre['fecha']->translatedFormat('l j \d\e F'),
                        'visitas' => $formato($diaCumbre['visitantes']),
                    ]) }}
                @endif
            </p>
        </div>
    </div>

    {{-- ---------------- La gráfica ---------------- --}}
    <section aria-labelledby="huv-grafica" class="mb-9">
        <h2 id="huv-grafica" class="m-0 mb-4 font-display text-16-5 font-bold text-heading">
            {{ __('admin-estadisticas.grafica.titulo') }}
        </h2>

        {{-- Decorativa: lo que hay debajo, en la tabla, es la versión que se
             puede leer con un lector de pantalla y copiar a un informe. --}}
        <div aria-hidden="true"
             class="flex h-[180px] items-end gap-px overflow-x-auto rounded-[3px] border border-stroke
                    bg-card px-3 py-3">
            @foreach ($porDia as $dia)
                <div class="flex h-full min-w-[6px] flex-1 items-end"
                     title="{{ __('admin-estadisticas.grafica.dia', [
                         'fecha' => $dia['fecha']->translatedFormat('j M'),
                         'visitas' => $dia['visitantes'],
                         'paginas' => $dia['paginas'],
                     ]) }}">
                    <span class="w-full rounded-t-[2px] bg-azure"
                          style="height: {{ max(1, round($dia['visitantes'] / $techo * 100)) }}%"></span>
                </div>
            @endforeach
        </div>

        <details class="mt-3">
            <summary class="cursor-pointer text-13-5 font-semibold text-link">
                {{ __('admin-estadisticas.grafica.tabla') }}
            </summary>

            <div class="mt-3 max-h-[420px] overflow-auto rounded-[3px] border border-stroke bg-card">
                <table class="w-full border-collapse text-14">
                    <caption class="sr-only">{{ __('admin-estadisticas.grafica.titulo') }}</caption>
                    <thead>
                        <tr class="border-b border-stroke text-left">
                            <th scope="col" class="px-4 py-2 font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.grafica.columna_fecha') }}
                            </th>
                            <th scope="col" class="px-4 py-2 text-right font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.grafica.columna_visitas') }}
                            </th>
                            <th scope="col" class="px-4 py-2 text-right font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.grafica.columna_paginas') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($porDia->reverse() as $dia)
                            <tr class="border-b border-divider last:border-b-0">
                                <td class="px-4 py-[7px] text-body">
                                    {{ $dia['fecha']->translatedFormat('l j \d\e F \d\e Y') }}
                                </td>
                                <td class="px-4 py-[7px] text-right tabular-nums text-ink">{{ $formato($dia['visitantes']) }}</td>
                                <td class="px-4 py-[7px] text-right tabular-nums text-muted">{{ $formato($dia['paginas']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    {{-- ---------------- Páginas más vistas ---------------- --}}
    @if ($topPaths->isNotEmpty())
        <section aria-labelledby="huv-top" class="mb-9">
            <h2 id="huv-top" class="m-0 mb-4 font-display text-16-5 font-bold text-heading">
                {{ __('admin-estadisticas.top.titulo') }}
            </h2>

            <div class="overflow-x-auto rounded-[3px] border border-stroke bg-card">
                <table class="w-full border-collapse text-14">
                    <caption class="sr-only">{{ __('admin-estadisticas.top.titulo') }}</caption>
                    <thead>
                        <tr class="border-b border-stroke text-left">
                            <th scope="col" class="px-4 py-3 font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.top.columna_pagina') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-right font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.top.columna_paginas') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-right font-display text-13 font-bold text-heading">
                                {{ __('admin-estadisticas.top.columna_visitantes') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topPaths as $fila)
                            <tr class="border-b border-divider last:border-b-0">
                                <td class="px-4 py-[9px]">
                                    <a href="{{ url($fila['path']) }}" target="_blank" rel="noopener noreferrer"
                                       class="break-all text-link underline underline-offset-4">
                                        {{ $fila['path'] === '/' ? __('admin-estadisticas.top.portada') : $fila['path'] }}
                                    </a>
                                </td>
                                <td class="px-4 py-[9px] text-right tabular-nums text-ink">{{ $formato($fila['paginas']) }}</td>
                                <td class="px-4 py-[9px] text-right tabular-nums text-muted">{{ $formato($fila['visitantes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- ---------------- La letra pequeña ---------------- --}}
    <section aria-labelledby="huv-letra-pequena"
             class="rounded-[4px] border border-line bg-surface px-5 py-4">
        <h2 id="huv-letra-pequena" class="m-0 font-display text-14-5 font-bold text-heading">
            {{ __('admin-estadisticas.letra_pequena.titulo') }}
        </h2>

        <ul class="m-0 mt-2 flex max-w-[80ch] list-disc flex-col gap-[6px] pl-5 text-13-5 leading-[1.6] text-body">
            @foreach (['visita', 'cookie', 'excluidos', 'privacidad'] as $punto)
                <li>{{ __('admin-estadisticas.letra_pequena.'.$punto) }}</li>
            @endforeach
        </ul>
    </section>
@endsection
