@extends('layouts.app')

@section('title', __('paginas.mapa.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.mapa.descripcion', [
    'entidad' => config('huv.institution.name_plain'),
]))

@section('content')
    {{--
        Mapa del sitio.

        El mismo árbol del portal: la portada arriba y, colgando de ella, las
        secciones de la barra y los cuatro grupos del menú completo. No se arma
        con una consulta sino con la misma configuración que pinta el menú, y es
        eso lo que lo mantiene fiel: el día que se añada un tema al menú,
        aparece aquí solo y sin que nadie se acuerde de venir.

        No confundir con /sitemap.xml, que es el mapa para los buscadores: ese
        enumera las más de dos mil direcciones publicadas, y este las secciones
        por las que se navega.

        Dos cosas del portal NO se copian, porque son defectos y no rasgos:

        · La raíz se llama «Inicio», no «Home»: el portal deja ahí el rótulo en
          inglés de la plataforma en una página que está en español, y el suyo
          propio dice «Inicio» tres centímetros más arriba, en la ruta.

        · Los nodos que no llevan a ninguna parte van como texto. El portal los
          publica como <a href="">, que para el navegador es un enlace a la
          página actual: se pulsa, la página se recarga y no ha pasado nada. Y
          para un lector de pantalla son seis enlaces sin destino en medio de
          la lista.
    --}}
    @php
        $nav = config('huv.nav');

        // La portada es la raíz y todo lo demás cuelga de ella. Las secciones
        // de la barra y los grupos del menú completo son la misma cosa vista
        // desde dos sitios; lo único que cambia es de qué campo sale el rótulo
        // y cómo se llaman sus hijos, así que se normalizan aquí y la vista de
        // abajo ya no tiene que saber de cuál de los dos venía cada rama.
        $raiz = $nav[0];

        $ramas = collect(array_slice($nav, 1))
            ->map(fn (array $item): array => [
                'entry' => $item,
                'field' => 'label',
                'children' => $item['children'] ?? [],
            ])
            ->concat(collect(config('huv.mega_menu'))->map(fn (array $grupo): array => [
                'entry' => $grupo,
                'field' => 'title',
                'children' => $grupo['links'],
            ]));
    @endphp

    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[900px]">

                <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.mapa.titulo') }}</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    {{ __('paginas.mapa.titulo') }}
                </h1>

                <p class="mt-3 max-w-[68ch] text-14 leading-[1.6] text-muted">
                    {{ __('paginas.mapa.entrada') }}
                </p>

                {{-- Un <nav> con nombre: es una lista de ciento treinta enlaces y
                     quien navega con lector de pantalla necesita poder saltársela
                     entera de una vez. El árbol se dibuja con listas anidadas de
                     verdad —no con sangrados— para que se anuncie el nivel. --}}
                <nav aria-labelledby="huv-mapa-arbol" class="huv-tree mt-8 text-14 leading-[1.5]">
                    <h2 id="huv-mapa-arbol" class="sr-only">{{ __('paginas.mapa.arbol') }}</h2>

                    <ul>
                        <li>
                            <x-sitemap-node :link="$raiz" class="font-semibold" />

                            <ul>
                                @foreach ($ramas as $rama)
                                    <li>
                                        <x-sitemap-node :link="$rama['entry']" :field="$rama['field']" />

                                        @if (! empty($rama['children']))
                                            <ul>
                                                @foreach ($rama['children'] as $hijo)
                                                    <li><x-sitemap-node :link="$hijo" /></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </x-container>
    </div>
@endsection
