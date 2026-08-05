@props(['link'])

@php
    /*
     | Un enlace del menú declara 'url' (destino externo) o 'path' (sección del
     | portal). Mientras la sección no exista aquí, 'path' se resuelve contra
     | huv.legacy_base; el día que se migre basta con vaciar esa opción para
     | que el mismo enlace apunte a este aplicativo.
    */
    $external = isset($link['url']);
    $href = $external
        ? $link['url']
        : rtrim((string) config('huv.legacy_base'), '/').$link['path'];
@endphp

<a href="{{ $href }}"
   @if ($external) target="_blank" rel="noopener noreferrer" @endif
   {{ $attributes }}>
    {{ $link['label'] }}
    @if ($external)
        <span class="sr-only">(se abre en una pestaña nueva)</span>
        <svg class="ml-1 inline-block size-[11px] shrink-0 align-[-1px] opacity-60" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
             stroke-linejoin="round" aria-hidden="true">
            <path d="M14 4h6v6" />
            <path d="M20 4 11 13" />
            <path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
        </svg>
    @endif
</a>
