@props(['item'])

{{--
    Fila de un tema de enlaces.

    Sin tarjeta ni imagen: son cientos de registros de contratación y lo que se
    lee es el código, el contratista y el objeto. El título lleva a su ficha, y
    es allí donde está el enlace al expediente completo.
--}}
<p class="m-0 text-12 text-faint">
    @if ($item->date())
        <x-published-at :value="$item->date()" />
    @endif
</p>

<h3 class="m-0 mt-1 font-display text-15 leading-[1.4] font-bold">
    <a href="{{ $item->url() }}"
       class="text-link underline decoration-1 underline-offset-4 hover:text-heading-hover">
        {{ $item->title }}
    </a>
    <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
</h3>

@if (filled($item->summary(280)))
    <p class="m-0 mt-1 text-13 leading-[1.5] text-pretty text-muted">{{ $item->summary(280) }}</p>
@endif

<x-topic-item-badges :item="$item" />
