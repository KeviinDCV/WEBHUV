@php
    /*
     | La portada, y solo la portada.
     |
     | No se usa mainImage(): cuando nada está marcado devuelve la primera de
     | la lista, y un artículo puede traer siete láminas y ninguna portada. Esa
     | primera acababa arriba, a lo ancho, sin enlace para ampliarla y con el
     | alt vacío que trae la importación —anunciada como decorativa a un lector
     | de pantalla—, mientras sus seis hermanas iban a la galería con enlace y
     | texto de respaldo. Sin portada no hay portada: van las siete a la
     | galería, que además es lo que hace el portal.
     |
     | La otra cara de mainImage(), la de dar miniatura a las tarjetas del
     | listado, se queda como está: ahí sí conviene enseñar la que haya.
     */
    $mainImage = $item->images()->firstWhere('is_main', true);
    $gallery = $item->images()->reject(fn ($image) => $mainImage && $image->is($mainImage));
    $video = $item->video();
    $files = $item->files();
@endphp

{{--
    Imagen, cuerpo, vídeo, galería y adjuntos de un contenido.

    Lo comparten la ficha de una noticia y la de un artículo de un tema: son la
    misma página con distinto rastro de navegación, y tenerlo dos veces sería
    la clase de duplicado que se desincroniza a la primera corrección.
--}}

{{-- Imagen principal --}}
@if ($mainImage)
    <figure class="m-0 mt-7 bg-tint p-5 lg:p-8">
        <img src="{{ $mainImage->fileUrl() }}" alt="{{ $mainImage->alt }}"{!! App\Support\PortalLang::attribute() !!}
             loading="eager" fetchpriority="high" decoding="async"
             class="mx-auto block h-auto w-full max-w-[720px]">
        @if (filled($mainImage->alt))
            <x-texto-del-portal tag="figcaption" class="mt-3 text-center text-12-5 text-muted">{{ $mainImage->alt }}</x-texto-del-portal>
        @endif
    </figure>
@endif

{{-- Cuerpo. El HTML se depuró al guardarlo (App\Support\RichText). --}}
@if (filled($body))
    <x-texto-del-portal tag="div" class="huv-prose mt-8">{!! $body !!}</x-texto-del-portal>
@elseif (filled($fallback ?? null))
    <x-texto-del-portal tag="p" class="mt-8 text-15 leading-[1.75] text-body">{{ $fallback }}</x-texto-del-portal>
@endif

{{-- Vídeo --}}
@if ($video && $video->youtubeId())
    <div class="mt-8">
        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">{{ __('estructura.medios.video') }}</h2>
        <div class="aspect-video overflow-hidden rounded-[4px] bg-navy-deep">
            {{-- youtube-nocookie: no deja rastro publicitario en quien solo pasa
                 por la página. --}}
            <iframe src="https://www.youtube-nocookie.com/embed/{{ $video->youtubeId() }}"
                    title="{{ $video->alt ?: __('estructura.medios.video_titulo') }}"@if (filled($video->alt)){!! App\Support\PortalLang::attribute() !!}@endif
                    loading="lazy"
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    class="size-full border-0"></iframe>
        </div>
    </div>
@endif

{{-- Galería --}}
<x-media-gallery :images="$gallery" />

{{-- Archivos --}}
@if ($files->isNotEmpty())
    <div class="mt-8">
        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">{{ $filesTitle ?? __('estructura.medios.documentos_adjuntos') }}</h2>
        <ul class="flex flex-col gap-2">
            @foreach ($files as $file)
                <li>
                    <a href="{{ $file->fileUrl() }}" download
                       class="flex items-center gap-3 rounded-[3px] border border-line bg-card px-4 py-3
                              text-14 text-heading no-underline hover:bg-tint hover:no-underline">
                        <svg class="size-5 shrink-0 text-link" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 3v5h5" />
                            <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                        </svg>
                        <x-texto-del-portal class="min-w-0 flex-1 break-words">{{ $file->alt ?: $file->original_name }}</x-texto-del-portal>
                        <span class="shrink-0 text-12-5 text-muted">
                            {{ $file->extension() }}@if ($file->humanSize()) · {{ $file->humanSize() }}@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
