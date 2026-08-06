@php
    $mainImage = $content->mainImage();
    $gallery = $content->images()->reject(fn ($image) => $mainImage && $image->is($mainImage));
    $video = $content->video();
    $files = $content->files();
@endphp

@extends('layouts.app')

@section('title', $content->title.' — '.config('huv.institution.short_name'))
@section('description', $content->summary(160))

@push('head')
    @if (! $content->is_active || $content->is_hidden || $content->isScheduled())
        {{-- Lo no publicado no debe indexarse aunque alguien comparta el enlace. --}}
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:type" content="article">
    @if ($content->imageUrl())
        <meta property="og:image" content="{{ $content->imageUrl() }}">
    @endif
@endpush

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                {{-- Rastro de navegación --}}
                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ $content->category }}</li>
                    </ol>
                </nav>

                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    <p class="m-0 text-12 text-faint">
                        Modificación:
                        <time datetime="{{ $content->updated_at->toIso8601String() }}">
                            {{ $content->updated_at->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                        · Creación:
                        <time datetime="{{ $content->created_at->toIso8601String() }}">
                            {{ $content->created_at->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                    </p>

                    @auth
                        <a href="{{ route('admin.contents.edit', $content) }}"
                           x-show="$store.huvUi.editMode" x-cloak
                           data-huv-edit="contenido"
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
                    {{ $content->title }}
                </h1>

                @auth
                    <div class="mt-3"><x-content-badges :content="$content" /></div>
                @endauth

                @if ($content->displayDate())
                    <p class="m-0 mt-3 text-13-5 text-muted">
                        <x-published-at :value="$content->displayDate()" />
                    </p>
                @endif

                <x-share :title="$content->title" class="mt-5" />

                {{-- Imagen principal --}}
                @if ($mainImage)
                    <figure class="m-0 mt-7 bg-tint p-5 lg:p-8">
                        <img src="{{ $mainImage->fileUrl() }}" alt="{{ $mainImage->alt }}"
                             loading="eager" fetchpriority="high" decoding="async"
                             class="mx-auto block h-auto w-full max-w-[720px]">
                        @if (filled($mainImage->alt))
                            <figcaption class="mt-3 text-center text-12-5 text-muted">{{ $mainImage->alt }}</figcaption>
                        @endif
                    </figure>
                @endif

                {{-- Cuerpo. El HTML se depuró al guardarlo (App\Support\RichText). --}}
                @if (filled($content->body))
                    <div class="huv-prose mt-8">{!! $content->body !!}</div>
                @elseif (filled($content->excerpt))
                    <p class="mt-8 text-15 leading-[1.75] text-body">{{ $content->excerpt }}</p>
                @endif

                {{-- Vídeo --}}
                @if ($video && $video->youtubeId())
                    <div class="mt-8">
                        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Vídeo</h2>
                        <div class="aspect-video overflow-hidden rounded-[4px] bg-navy-deep">
                            {{-- youtube-nocookie: no deja rastro publicitario en quien
                                 solo pasa por la página. --}}
                            <iframe src="https://www.youtube-nocookie.com/embed/{{ $video->youtubeId() }}"
                                    title="{{ $video->alt ?: 'Vídeo del contenido' }}"
                                    loading="lazy"
                                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    class="size-full border-0"></iframe>
                        </div>
                    </div>
                @endif

                {{-- Galería --}}
                @if ($gallery->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Galería</h2>
                        <ul class="grid grid-cols-2 gap-4 md:grid-cols-3">
                            @foreach ($gallery as $image)
                                <li>
                                    <figure class="m-0">
                                        <img src="{{ $image->fileUrl() }}" alt="{{ $image->alt }}"
                                             loading="lazy" decoding="async"
                                             class="aspect-[4/3] w-full rounded-[3px] border border-line object-cover">
                                        @if (filled($image->alt))
                                            <figcaption class="mt-1 text-12 text-muted">{{ $image->alt }}</figcaption>
                                        @endif
                                    </figure>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Archivos --}}
                @if ($files->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Documentos adjuntos</h2>
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
                                        <span class="min-w-0 flex-1 break-words">
                                            {{ $file->alt ?: $file->original_name }}
                                        </span>
                                        <span class="shrink-0 text-12-5 text-muted">
                                            {{ $file->extension() }}@if ($file->humanSize()) · {{ $file->humanSize() }}@endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Etapa de participación ciudadana (Ley 1757 de 2015) --}}
                @if (filled($content->participation))
                    <aside class="mt-8 rounded-[4px] border border-line border-l-4 border-l-rule-accent bg-tint px-5 py-4">
                        <p class="m-0 text-13-5 text-body">
                            Este contenido hace parte de
                            <strong class="font-semibold text-heading">{{ $content->participation }}</strong>,
                            dentro del proceso de participación ciudadana del hospital.
                        </p>
                    </aside>
                @endif

                <hr class="my-10 border-0 border-t border-line">

                @include('partials.content-feedback')

                {{-- Contenidos relacionados --}}
                @if ($related->isNotEmpty())
                    <section aria-labelledby="huv-relacionados" class="mt-10">
                        <h2 id="huv-relacionados" class="m-0 mb-4 font-display text-17 font-bold text-heading">
                            También en {{ $content->category }}
                        </h2>
                        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ $item->url() }}"
                                       class="block h-full rounded-[4px] border border-line bg-card p-4 text-14
                                              leading-[1.45] font-semibold text-heading no-underline
                                              hover:bg-tint hover:no-underline">
                                        {{ $item->title }}
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
