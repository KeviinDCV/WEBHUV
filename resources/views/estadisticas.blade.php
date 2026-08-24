@extends('layouts.app')

@section('title', __('paginas.estadisticas.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.estadisticas.descripcion', [
    'entidad' => config('huv.institution.name_plain'),
]))

@push('head')
    {{-- Sin contenido no hay nada que indexar, y una página en blanco dentro del
         índice le dice al buscador que el sitio publica páginas vacías. Se
         quita del índice pero se deja seguir sus enlaces: la cabecera y el pie
         llevan a todo lo demás. Cuando la página tenga cifras, esto se borra y
         se añade la ruta a SitemapController::fixedPages(). --}}
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    {{--
        Estadísticas.

        En blanco a propósito, y aquí queda dicho por qué para que nadie la dé
        por rota ni la «arregle» con datos inventados.

        La página existe porque el pie la enlaza, y el enlace del pie llevaba al
        portal anterior: quien lo pulsaba salía de este sitio sin enterarse y
        seguía navegando la web vieja creyendo que estaba en la nueva. Con la
        página aquí, el enlace se queda en casa.

        Y está vacía porque no hay nada honesto que poner todavía. El portal de
        origen tampoco enseña nada: comprobado, su /estadisticas no pide ni una
        cifra al servidor cuando quien mira no es administrador, y con sesión de
        administrador añade un rango de fechas y sigue sin pintar dato alguno.
        Aquí no se copia ese «Rango de fechas», que es el rótulo de un
        formulario que no existe.

        Lo que iría dentro son estadísticas de uso —visitas, secciones más
        consultadas—, y este aplicativo no las mide: no hay tabla, ni contador,
        ni servicio de analítica. Inventarlas o rellenar el hueco con cuántos
        documentos hay publicados sería llamar «estadísticas» a otra cosa.
    --}}
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.estadisticas.titulo') }}</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    {{ __('paginas.estadisticas.titulo') }}
                </h1>
            </div>
        </x-container>
    </div>
@endsection
