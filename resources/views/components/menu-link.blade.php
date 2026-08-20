@props(['link'])

@php
    /*
     | Un enlace del menú declara 'url' (destino ajeno al portal) o 'path' (una
     | sección). Dónde acaba resolviéndose un 'path' lo decide
     | App\Support\LegacyLink: en este aplicativo si la sección ya se migró, y
     | en el portal actual mientras no lo esté.
    */
    ['href' => $href, 'external' => $external] = App\Support\LegacyLink::resolve($link);
@endphp

<a href="{{ $href }}"
   @if ($external) target="_blank" rel="noopener noreferrer" @endif
   {{ $attributes }}>
    {!! App\Support\ConfigLabel::marked($link) !!}
    @if ($external)
        <span class="sr-only">{{ __('componentes.enlace.pestana_nueva') }}</span>
        <svg class="ml-1 inline-block size-[11px] shrink-0 align-[-1px] opacity-60" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
             stroke-linejoin="round" aria-hidden="true">
            <path d="M14 4h6v6" />
            <path d="M20 4 11 13" />
            <path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
        </svg>
    @endif
</a>
