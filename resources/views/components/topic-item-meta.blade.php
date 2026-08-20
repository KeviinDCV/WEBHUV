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

{{--
    El nombre del tema, donde el muro de la portada pone la categoría.

    Siempre el tema, aunque el contenido tenga categorías. El portal hace justo
    eso: en «Centro Integral de Atención al Usuario - CIAU», donde cada
    documento lleva una o dos —«2026», «Informes Trimestrales PQRSFD»—, las seis
    tarjetas rotulan igual, con el nombre del tema. Las categorías tienen su
    sitio: los botones de filtro de arriba, y en los temas que se publican en
    filas, encima del título.
--}}
<x-texto-del-portal tag="p" @class(['m-0 text-12-5', 'text-on-accent/85' => $sobreColor, 'text-link' => ! $sobreColor])>{{ $item->topic->name }}</x-texto-del-portal>

<h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
    <a href="{{ $item->url() }}"
       @class([
           'underline decoration-1 underline-offset-4',
           'text-on-accent hover:text-on-accent' => $sobreColor,
           'text-heading hover:text-heading-hover' => ! $sobreColor,
       ])>
        <x-texto-del-portal>{{ $item->title }}</x-texto-del-portal>
    </a>
    {{-- El lápiz va pegado al título, como en el portal actual. --}}
    <x-topic-item-actions :item="$item" :tone="$tone" class="ml-1 inline-flex align-[-5px]" />
</h3>

@if (filled($item->summary()))
    <x-texto-del-portal tag="p" @class([
        'm-0 text-13-5 leading-[1.6] whitespace-pre-line text-pretty',
        'text-on-accent/90' => $sobreColor,
        'text-muted' => ! $sobreColor,
    ])>{{ $item->summary() }}</x-texto-del-portal>
@endif

<x-participa-link :item="$item" class="mt-1" />

<x-topic-item-badges :item="$item" :tone="$tone" />
