@props([
    'content',
    /** 'wall' deja que el muro alterne rejilla y lista; 'topic' apila siempre. */
    'layout' => 'wall',
])

@php
    $enTema = $layout === 'topic';
@endphp

{{--
    Tarjeta de una noticia.

    La usan el muro de la portada y el listado del tema «Noticias», que muestran
    exactamente lo mismo: en el portal son una sola cosa vista desde dos sitios.

    Las clases se componen en un único atributo. Mezclar `class` con `@class` en
    la misma etiqueta emite DOS atributos y el navegador se queda con el
    primero: así se perdió el apilado y las fotos salieron a tamaño natural.
--}}
<article class="flex w-full overflow-hidden rounded-[4px] border border-line bg-card
                transition hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)] {{ $enTema ? 'flex-col' : '' }}"
         @unless ($enTema) :class="view === 'grid' ? 'flex-col' : 'flex-col sm:flex-row'" @endunless>

    @if ($content->imageUrl())
        <a href="{{ $content->url() }}" tabindex="-1" aria-hidden="true"
           @if ($content->isExternal()) target="_blank" rel="noopener noreferrer" @endif
           class="block shrink-0 {{ $enTema ? 'w-full' : '' }}"
           @unless ($enTema) :class="view === 'grid' ? 'h-[150px]' : 'h-[150px] sm:h-auto sm:w-[220px]'" @endunless>
            {{-- En el tema manda la proporción, no la altura del padre: con
                 `h-full` sobre un contenedor sin altura, la foto se dibuja a su
                 tamaño real y desborda la tarjeta. --}}
            <img src="{{ $content->imageUrl() }}" alt=""
                 loading="lazy" decoding="async"
                 class="{{ $enTema ? 'aspect-[16/9] w-full object-cover' : 'size-full object-cover' }}">
        </a>
    @endif

    <div class="flex flex-1 flex-col gap-2 p-5">
        <p class="m-0 flex flex-wrap items-center gap-x-2 text-12 text-faint">
            @if ($content->displayDate())
                <x-published-at :value="$content->displayDate()" />
            @endif
        </p>

        <p class="m-0 text-12-5 text-link">{{ $content->category }}</p>

        <h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
            <a href="{{ $content->url() }}"
               @if ($content->isExternal()) target="_blank" rel="noopener noreferrer" @endif
               class="text-heading underline decoration-1 underline-offset-4 hover:text-heading-hover">
                {{ $content->title }}
            </a>
            {{-- El lápiz va pegado al título, como en el portal actual. --}}
            <x-content-actions :content="$content" class="ml-1 inline-flex align-[-5px]" />
        </h3>

        <p class="m-0 text-13-5 leading-[1.6] whitespace-pre-line text-pretty text-muted">{{ $content->summary() }}</p>

        <x-participa-link :item="$content" class="mt-1" />

        <x-content-badges :content="$content" />
    </div>
</article>
