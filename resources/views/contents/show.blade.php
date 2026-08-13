@extends('layouts.app')

@section('title', $content->title.' — '.config('huv.institution.short_name'))
@section('description', Str::squish($content->summary(160)))

@section('og_type', 'article')
@if ($content->imageUrl())
    @section('og_image', $content->imageUrl())
@endif

@push('head')
    @if (! $content->is_active || $content->is_hidden || $content->isScheduled())
        {{-- Lo no publicado no debe indexarse aunque alguien comparta el enlace. --}}
        <meta name="robots" content="noindex, nofollow">
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

                @include('partials.ficha-medios', [
                    'item' => $content,
                    'body' => $content->body,
                    'fallback' => $content->excerpt,
                ])

                @include('partials.aviso-participacion', ['item' => $content])

                <hr class="my-10 border-0 border-t border-line">

                @include('partials.content-feedback', ['key' => $content->id])

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
