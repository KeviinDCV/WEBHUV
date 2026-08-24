@props([
    /** La entrada de configuración: una sección de la barra o un enlace suyo. */
    'link',
    /** De dónde sale el rótulo: 'label' en el menú, 'title' en el menú completo. */
    'field' => 'label',
])

@php
    // Sin destino declarado no hay enlace que resolver: son los nodos que solo
    // agrupan —«Atención y Servicios a la ciudadanía», «Documentos»—, que en el
    // portal se publican como <a href=""> y ahí no llevan a ninguna parte.
    $destino = isset($link['path']) || isset($link['url'])
        ? App\Support\LegacyLink::resolve($link)
        : null;
@endphp

@if ($destino)
    <a href="{{ $destino['href'] }}"
       @if ($destino['external']) target="_blank" rel="noopener noreferrer" @endif
       {{ $attributes->class(['inline-block py-1 text-link no-underline hover:text-heading-hover hover:underline']) }}>
        {!! App\Support\ConfigLabel::marked($link, $field) !!}
        @if ($destino['external'])
            <span class="sr-only">{{ __('paginas.enlace.pestana_nueva') }}</span>
        @endif
    </a>
@else
    <span {{ $attributes->class(['inline-block py-1 font-semibold text-heading']) }}>
        {!! App\Support\ConfigLabel::marked($link, $field) !!}
    </span>
@endif
