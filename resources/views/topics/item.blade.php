@extends('layouts.app')

@section('title', $item->title.' — '.$topic->name.' — '.config('huv.institution.short_name'))
@section('description', Str::squish($item->summary(160)) ?: $item->title)

@if ($item->isArticle())
    @section('og_type', 'article')
    @if ($item->imageUrl())
        @section('og_image', $item->imageUrl())
    @endif
@endif

@push('head')
    @if (! $item->isPublic())
        {{-- Lo no publicado no debe indexarse aunque alguien comparta el enlace. --}}
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                {{-- ---------------- Rastro de navegación ---------------- --}}
                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li><a href="{{ route('topics.show', $topic) }}" class="text-link">{{ $topic->name }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">
                            {{ Str::limit($item->title, 60) }}
                        </li>
                    </ol>
                </nav>

                @if ($item->categories->isNotEmpty())
                    <p class="m-0 mb-3 flex flex-wrap gap-2">
                        @foreach ($item->categories as $category)
                            <span class="rounded-full bg-tint px-4 py-[5px] text-12-5 font-semibold text-link">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </p>
                @endif

                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    <p class="m-0 text-12 text-faint">
                        Modificación:
                        <time datetime="{{ ($item->modified_at ?? $item->updated_at)->toIso8601String() }}">
                            {{ ($item->modified_at ?? $item->updated_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                        · Creación:
                        <time datetime="{{ ($item->published_at ?? $item->created_at)->toIso8601String() }}">
                            {{ ($item->published_at ?? $item->created_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                    </p>

                    @auth
                        <a href="{{ route('topics.show', $topic) }}?editar={{ $item->id }}#huv-editor-tema"
                           x-show="$store.huvUi.editMode" x-cloak
                           data-huv-edit="tema-elemento"
                           class="inline-flex items-center gap-[6px] text-13-5 font-semibold text-link">
                            Editar
                            <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                        </a>
                    @endauth
                </div>

                <h1 class="m-0 font-display text-25 leading-[1.25] font-bold tracking-[-0.015em] text-balance text-heading lg:text-33">
                    {{ $item->title }}
                </h1>

                @auth
                    <div class="mt-3"><x-topic-item-badges :item="$item" /></div>
                @endauth

                @if ($item->issued_at)
                    <p class="m-0 mt-3 text-13-5 text-muted">
                        Fecha de expedición:
                        {{-- Con el sello tal cual, como el portal: la hora es
                             siempre un relleno, pero es lo que enseña. --}}
                        <time datetime="{{ $item->issued_at->toIso8601String() }}"
                              class="font-semibold text-heading">
                            {{ $item->issued_at->format('Y/m/d H:i:s') }}
                        </time>
                    </p>
                @endif

                <x-share :title="$item->title" class="mt-5" />

                {{--
                    La convocatoria se lee como un documento: texto y archivos,
                    sin imagen ni galería. En el portal comparten la misma
                    rejilla de descargas, así que aquí comparten rama.
                --}}
                @if ($item->isDocument() || $item->isConvocation())
                    @if (filled($item->body))
                        <div class="huv-prose mt-8">{!! $item->body !!}</div>
                    @endif

                    {{--
                        Fotos adjuntas. El origen mezcla imágenes y documentos en
                        la misma lista y las marca con `isImage`; sin esto, la
                        foto de un documento no aparecía por ningún lado: esta
                        rama solo pinta descargas, y una imagen no es una.

                        Todas, sin apartar la principal: esta ficha no tiene
                        bloque de imagen de portada donde enseñarla, así que
                        filtrarla la dejaría otra vez sin sitio.
                    --}}
                    <x-media-gallery :images="$item->images()" />

                    {{-- ---------------- Archivos ---------------- --}}
                    @php($attachments = $item->attachments())

                    {{--
                        Una sola lista, con el mismo marcado para todos.

                        Un documento del portal puede traer veinticinco archivos.
                        Aquí el primero vive en columnas propias y el resto en
                        `content_media`, pero eso es cosa nuestra: pintarlos con
                        dos aspectos distintos delataría la costura y no se
                        parecería al original, que los publica todos iguales.
                    --}}
                    @if ($attachments->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Archivos para descargar</h2>

                            <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($attachments as $file)
                                    <li>
                                        <a href="{{ $file['url'] }}"
                                           @if ($file['downloaded']) download @else target="_blank" rel="noopener noreferrer" @endif
                                           class="flex h-full items-center gap-3 rounded-[3px] border border-line bg-card
                                                  px-4 py-3 text-14 text-heading no-underline hover:bg-tint
                                                  hover:no-underline">
                                            <span class="flex size-11 shrink-0 flex-col items-center justify-center
                                                         rounded-[3px] border-2 border-rule-accent">
                                                <span class="font-display text-10-5 font-bold tracking-[0.04em] text-link">
                                                    {{ $file['extension'] }}
                                                </span>
                                            </span>
                                            <span class="min-w-0 flex-1 break-words">{{ $file['name'] }}</span>
                                            <span class="shrink-0 text-12-5 text-muted">
                                                @if ($file['size']) {{ $file['size'] }} @endif
                                                @unless ($file['downloaded'])
                                                    <span class="sr-only">(se abre en una pestaña nueva)</span>
                                                @endunless
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @elseif ($item->isLink())
                    {{-- El detalle completo vive fuera: aquí queda la ficha
                         breve y el enlace al expediente. --}}
                    @if (filled($item->body))
                        <div class="huv-prose mt-8">{!! $item->body !!}</div>
                    @endif

                    @if (filled($item->source_url))
                        <p class="m-0 mt-8">
                            <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-6 py-[10px]
                                      font-display text-13-5 font-semibold text-on-accent no-underline
                                      transition-colors hover:bg-azure-dark hover:no-underline">
                                Consultar el expediente completo
                                <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 4h6v6" />
                                    <path d="M20 4 11 13" />
                                    <path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
                                </svg>
                                <span class="sr-only">(se abre en una pestaña nueva)</span>
                            </a>
                        </p>
                    @endif
                @else
                    @include('partials.ficha-medios', [
                        'item' => $item,
                        'body' => $item->body,
                        'filesTitle' => 'Archivos para descargar',
                    ])
                @endif

                @include('partials.aviso-participacion', ['item' => $item])

                <hr class="my-10 border-0 border-t border-line">

                @include('partials.content-feedback', ['key' => 'tema-'.$item->id])

                {{-- ---------------- Contenidos relacionados ---------------- --}}
                @if ($related->isNotEmpty())
                    <section aria-labelledby="huv-relacionados" class="mt-10">
                        <h2 id="huv-relacionados" class="m-0 mb-4 font-display text-17 font-bold text-heading">
                            También en {{ $item->categories->first()?->name ?: $topic->name }}
                        </h2>
                        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach ($related as $other)
                                <li>
                                    <a href="{{ $other->url() }}"
                                       class="block h-full rounded-[4px] border border-line bg-card p-4 text-14
                                              leading-[1.45] font-semibold text-heading no-underline
                                              hover:bg-tint hover:no-underline">
                                        {{ $other->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </x-container>
    </div>
@endsection
