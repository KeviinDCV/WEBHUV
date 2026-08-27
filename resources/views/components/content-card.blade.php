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
           @unless ($enTema) :class="view === 'grid' ? '' : 'sm:w-[220px]'" @endunless>
            {{-- La foto entera, con su proporción, y sin recortarla.

                 Es lo que hace el portal de origen: cada noticia trae la suya
                 como la subieron —hay de 4:3, de 3:2 y verticales de 0,79— y la
                 tarjeta se adapta a ella. Aquí se forzaba una altura de 150 px
                 con `object-cover`, así que una foto de grupo salía partida por
                 la mitad. Que las tarjetas queden de distinta altura no es un
                 problema: para eso está el acomodo en mampostería.

                 Y con miniatura si la hay: el original de una noticia llega a
                 pesar dos megas y aquí se pinta a 550 px de ancho como mucho.
                 Sin miniatura generada todavía, se sirve el original y se ve
                 igual.

                 `width` y `height` son los de verdad, leídos del fichero, y
                 ahora sí hacen falta: sin ellos el navegador no sabe cuánta
                 altura reservar —ya no la fija el contenedor— y la página daría
                 un salto al cargar cada foto. --}}
            @php
                $imagen = $content->mainImage();
                $miniatura = App\Support\ResponsiveImage::srcset($imagen?->path, 'public', App\Support\ResponsiveImage::CARD_WIDTHS);
                $medidas = App\Support\ResponsiveImage::dimensions($imagen?->path);
            @endphp

            <picture>
                @if ($miniatura)
                    {{-- En rejilla la tarjeta ocupa media fila; en lista, 220 px.
                         Se declara la primera porque quedarse corto se ve —una
                         foto borrosa— y pasarse solo cuesta unos kilobytes. --}}
                    <source type="image/webp" srcset="{{ $miniatura }}"
                            sizes="(min-width: 768px) 50vw, 100vw">
                @endif

                <img src="{{ $content->imageUrl() }}" alt=""
                     @if ($medidas) width="{{ $medidas[0] }}" height="{{ $medidas[1] }}" @endif
                     loading="lazy" decoding="async"
                     class="block h-auto w-full">
            </picture>
        </a>
    @endif

    <div class="flex flex-1 flex-col gap-2 p-5">
        <p class="m-0 flex flex-wrap items-center gap-x-2 text-12 text-faint">
            @if ($content->displayDate())
                <x-published-at :value="$content->displayDate()" />
            @endif
        </p>

        <p class="m-0 text-12-5 text-link">{{ $content->feedLabel() }}</p>

        <h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
            <a href="{{ $content->url() }}"
               @if ($content->isExternal()) target="_blank" rel="noopener noreferrer" @endif
               class="text-heading underline decoration-1 underline-offset-4 hover:text-heading-hover">
                <x-texto-del-portal>{{ $content->title }}</x-texto-del-portal>
            </a>
            {{-- El lápiz va pegado al título, como en el portal actual. --}}
            <x-content-actions :content="$content" class="ml-1 inline-flex align-[-5px]" />
        </h3>

        <x-texto-del-portal tag="p" class="m-0 text-13-5 leading-[1.6] whitespace-pre-line text-pretty text-muted">{{ $content->summary() }}</x-texto-del-portal>

        <x-participa-link :item="$content" class="mt-1" />

        <x-content-badges :content="$content" />
    </div>
</article>
