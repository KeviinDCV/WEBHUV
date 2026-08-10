@props(['item', 'tone' => 'default'])

@php
    // Sobre el azul de una convocatoria los colores de texto habituales no se
    // leen: se cambian por los del acento, que el modo de alto contraste
    // también sabe invertir.
    $sobreColor = $tone === 'accent';
@endphp

{{--
    Cuerpo de texto de la ficha, igual para documentos y artículos: solo cambia
    lo que va antes —el recuadro de la extensión o la imagen—.
--}}
@if ($item->date())
    <p @class(['m-0 text-12', 'text-on-accent/75' => $sobreColor, 'text-faint' => ! $sobreColor])>
        <x-published-at :value="$item->date()" />
    </p>
@endif

{{-- El nombre del tema, donde el muro de la portada pone la categoría. --}}
<p @class(['m-0 text-12-5', 'text-on-accent/85' => $sobreColor, 'text-link' => ! $sobreColor])>
    {{ $item->categories->isNotEmpty() ? $item->categories->pluck('name')->join(', ') : $item->topic->name }}
</p>

<h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
    <a href="{{ $item->url() }}"
       @class([
           'underline decoration-1 underline-offset-4',
           'text-on-accent hover:text-on-accent' => $sobreColor,
           'text-heading hover:text-heading-hover' => ! $sobreColor,
       ])>
        {{ $item->title }}
    </a>
    {{-- El lápiz va pegado al título, como en el portal actual. --}}
    <x-topic-item-actions :item="$item" class="ml-1 inline-flex align-[-5px]" />
</h3>

@if (filled($item->summary()))
    <p @class([
        'm-0 text-13-5 leading-[1.6] text-pretty',
        'text-on-accent/90' => $sobreColor,
        'text-muted' => ! $sobreColor,
    ])>{{ $item->summary() }}</p>
@endif

<x-participa-link :item="$item" class="mt-1" />

<x-topic-item-badges :item="$item" />
