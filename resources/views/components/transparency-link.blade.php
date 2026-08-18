@props(['link', 'order', 'nested' => false])

@php
    $destino = App\Support\LegacyLink::resolve($link);
@endphp

{{--
    Una entrada del índice de Transparencia.

    El número va dentro del enlace, no al lado: es parte de cómo se cita el
    apartado —«el 1.4 del índice»— y quien navega con teclado o lector de
    pantalla necesita oírlo al llegar al enlace, no antes.
--}}
<a href="{{ $destino['href'] }}"
   @if ($destino['external']) target="_blank" rel="noopener noreferrer" @endif
   @class([
       'flex gap-2 px-4 py-[11px] text-14 leading-[1.45] text-link no-underline transition-colors lg:px-6',
       'hover:bg-tint hover:text-heading-hover hover:underline',
       'pl-9 lg:pl-12 text-13-5' => $nested,
   ])>
    <span class="shrink-0 tabular-nums">{{ $order }}.</span>
    <span>
        {{ $link['label'] }}
        @if ($destino['external'])
            <span class="sr-only">(se abre en una pestaña nueva)</span>
        @endif
    </span>
</a>
