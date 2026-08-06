@extends('layouts.app')

@section('title', $document->title.' — '.$topic->name.' — '.config('huv.institution.short_name'))
@section('description', $document->summary(160) ?: $document->title)

@push('head')
    @if (! $document->isPublic())
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
                            {{ Str::limit($document->title, 60) }}
                        </li>
                    </ol>
                </nav>

                @if ($document->category)
                    <p class="m-0 mb-3">
                        <span class="rounded-full bg-tint px-4 py-[5px] text-12-5 font-semibold text-link">
                            {{ $document->category->name }}
                        </span>
                    </p>
                @endif

                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    <p class="m-0 text-12 text-faint">
                        Modificación:
                        <time datetime="{{ ($document->modified_at ?? $document->updated_at)->toIso8601String() }}">
                            {{ ($document->modified_at ?? $document->updated_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                        · Creación:
                        <time datetime="{{ ($document->published_at ?? $document->created_at)->toIso8601String() }}">
                            {{ ($document->published_at ?? $document->created_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                    </p>

                    @auth
                        <a href="{{ route('topics.show', $topic) }}?editar={{ $document->id }}#huv-editor-documento"
                           x-show="$store.huvUi.editMode" x-cloak
                           data-huv-edit="documento"
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
                    {{ $document->title }}
                </h1>

                @auth
                    <div class="mt-3"><x-document-badges :document="$document" /></div>
                @endauth

                @if ($document->issued_at)
                    <p class="m-0 mt-3 text-13-5 text-muted">
                        Fecha de expedición:
                        <time datetime="{{ $document->issued_at->toIso8601String() }}"
                              class="font-semibold text-heading">
                            {{ $document->issued_at->translatedFormat('j \d\e F \d\e Y') }}
                        </time>
                    </p>
                @endif

                <x-share :title="$document->title" class="mt-5" />

                {{-- Descripción. El HTML se depuró al guardarlo (App\Support\RichText). --}}
                @if (filled($document->description))
                    <div class="huv-prose mt-8">{!! $document->description !!}</div>
                @endif

                {{-- ---------------- Archivo ---------------- --}}
                @if ($document->fileUrl())
                    <div class="mt-8">
                        <h2 class="m-0 mb-3 font-display text-17 font-bold text-heading">Archivos para descargar</h2>

                        <a href="{{ $document->fileUrl() }}"
                           @if ($document->isDownloaded()) download @else target="_blank" rel="noopener noreferrer" @endif
                           class="flex items-center gap-3 rounded-[3px] border border-line bg-card px-4 py-3
                                  text-14 text-heading no-underline hover:bg-tint hover:no-underline">
                            <span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-[3px]
                                         border-2 border-rule-accent">
                                <span class="font-display text-10-5 font-bold tracking-[0.04em] text-link">
                                    {{ $document->extension() }}
                                </span>
                            </span>
                            <span class="min-w-0 flex-1 break-words">
                                {{ $document->file_name ?: $document->title }}
                            </span>
                            <span class="shrink-0 text-12-5 text-muted">
                                @if ($document->humanSize()) {{ $document->humanSize() }} @endif
                                @unless ($document->isDownloaded())
                                    <span class="sr-only">(se abre en una pestaña nueva)</span>
                                @endunless
                            </span>
                        </a>
                    </div>
                @endif

                <hr class="my-10 border-0 border-t border-line">

                @include('partials.content-feedback', ['key' => 'documento-'.$document->id])

                {{-- ---------------- Documentos relacionados ---------------- --}}
                @if ($related->isNotEmpty())
                    <section aria-labelledby="huv-relacionados" class="mt-10">
                        <h2 id="huv-relacionados" class="m-0 mb-4 font-display text-17 font-bold text-heading">
                            También en {{ $document->category?->name ?: $topic->name }}
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
