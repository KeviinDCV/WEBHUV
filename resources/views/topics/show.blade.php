@php
    use App\Models\Document;

    $perPage = 20;

    $meta = $documents->map(fn (Document $item): array => [
        'id' => $item->id,
        'title' => $item->title,
        'category' => $item->topic_category_id,
        'timestamp' => ($item->date() ?? $item->created_at)->getTimestampMs(),
        // Sin fecha de expedición se ordena al final, no al principio.
        'issued' => $item->issued_at?->getTimestampMs() ?? 0,
        'isActive' => $item->is_active,
        'isFeatured' => $item->is_featured,
        'isHidden' => $item->is_hidden,
    ]);

    // El arreglo se arma aquí: @json no admite varias líneas y rompería el PHP
    // generado, algo que `view:cache` no detecta porque solo falla al
    // ejecutarse la vista.
    $topicConfig = [
        'meta' => $meta,
        'categories' => $categories,
        'perPage' => $perPage,
        'canModerate' => auth()->check(),
        'openEditor' => $editing !== null || $errors->any(),
    ];
@endphp

@extends('layouts.app')

@section('title', $topic->name.' — '.config('huv.institution.short_name'))
@section('description', $topic->description ?: 'Documentos de '.$topic->name.' del '.config('huv.institution.name_plain').'.')

@auth
    @push('head')
        @vite('resources/js/admin.js')
    @endpush
@endauth

@section('content')
    <div class="bg-page" x-data='huvTopicDocuments(@json($topicConfig))'>
        <x-container class="py-8 lg:py-10">

            {{-- ---------------- Rastro de navegación ---------------- --}}
            <nav aria-label="Ruta de navegación" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                    <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                    <li aria-hidden="true">›</li>
                    <li aria-current="page" class="font-semibold text-heading">{{ $topic->name }}</li>
                </ol>
            </nav>

            <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                {{ $topic->name }}
            </h1>

            @if (filled($topic->description))
                <p class="m-0 mt-2 max-w-[70ch] text-14-5 text-muted">{{ $topic->description }}</p>
            @endif

            {{-- ---------------- Categorías ---------------- --}}
            @if ($categories->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center gap-2" role="group" aria-label="Filtrar por categoría">
                    <button type="button" @click="category = 'todas'"
                            :aria-pressed="category === 'todas' ? 'true' : 'false'"
                            :class="category === 'todas'
                                ? 'bg-navy text-on-brand'
                                : 'bg-tint text-link hover:bg-stroke/40'"
                            class="rounded-full border-0 px-4 py-[6px] text-12-5 font-semibold">
                        Todas las categorías
                    </button>

                    <template x-for="item in visibleCategories" :key="item.id">
                        <button type="button" @click="category = item.id"
                                :aria-pressed="category === item.id ? 'true' : 'false'"
                                :class="category === item.id
                                    ? 'bg-navy text-on-brand'
                                    : 'bg-tint text-link hover:bg-stroke/40'"
                                class="rounded-full border-0 px-4 py-[6px] text-12-5 font-semibold"
                                x-text="`${item.name} (${item.count})`"></button>
                    </template>

                    <button type="button" x-show="hasMoreCategories" x-cloak
                            @click="allCategories = ! allCategories"
                            :aria-expanded="allCategories ? 'true' : 'false'"
                            class="border-0 bg-transparent px-2 py-[6px] text-12-5 font-semibold text-link
                                   underline underline-offset-4"
                            x-text="allCategories ? 'Ver menos' : 'Ver más'">Ver más</button>

                    {{-- Sin JavaScript las categorías se listan como enlaces
                         normales, para no dejar el filtro inservible. --}}
                    <noscript>
                        <ul class="flex flex-wrap items-center gap-2">
                            @foreach ($categories as $category)
                                <li class="rounded-full bg-tint px-4 py-[6px] text-12-5 font-semibold text-link">
                                    {{ $category['name'] }} ({{ $category['count'] }})
                                </li>
                            @endforeach
                        </ul>
                    </noscript>
                </div>
            @endif

            {{-- ---------------- Búsqueda ---------------- --}}
            <div class="mt-6">
                <label for="huv-busca-tema" class="sr-only">Busca en {{ $topic->name }}</label>
                <div class="relative">
                    <input id="huv-busca-tema" type="search" x-model="search"
                           placeholder="Busca en {{ $topic->name }}"
                           class="w-full rounded-[3px] border border-stroke bg-card py-[10px] pr-12 pl-4
                                  text-14 text-ink">
                    <span class="pointer-events-none absolute top-1/2 right-2 flex size-8 -translate-y-1/2
                                 items-center justify-center rounded-full bg-azure text-on-accent">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- ---------------- Orden y filtros ---------------- --}}
            <div id="huv-documentos" class="mt-6 flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span id="huv-orden-tema" class="text-13-5 text-muted">Ordenar por:</span>

                    <div role="tablist" aria-labelledby="huv-orden-tema"
                         class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <template x-for="option in tabs" :key="option.key">
                            <button type="button" role="tab"
                                    @click="tab = option.key"
                                    :aria-selected="tab === option.key ? 'true' : 'false'"
                                    :class="tab === option.key
                                        ? 'text-heading font-bold'
                                        : 'text-link font-medium hover:text-heading'"
                                    class="border-0 bg-transparent p-0 text-13-5"
                                    x-text="option.label"></button>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <label for="huv-periodo-tema" class="sr-only">Filtrar por fecha</label>
                    <select id="huv-periodo-tema" x-model="period"
                            class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                                   font-semibold text-heading">
                        <option value="todos">Filtrar por fecha</option>
                        <option value="30">Último mes</option>
                        <option value="180">Últimos seis meses</option>
                        <option value="365">Último año</option>
                        <option value="1095">Últimos tres años</option>
                    </select>
                </div>
            </div>

            {{-- ---------------- Alta y vista ---------------- --}}
            <div class="mt-5 mb-8 flex flex-wrap items-center justify-between gap-4">
                @auth
                    <button type="button" @click="editor = ! editor"
                            x-show="$store.huvUi.editMode" x-cloak
                            :aria-expanded="editor ? 'true' : 'false'"
                            aria-controls="huv-editor-documento"
                            data-huv-edit="documentos"
                            class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[8px]
                                   font-display text-13-5 font-semibold text-on-accent
                                   transition-colors hover:bg-azure-dark"
                            x-text="editor ? 'Ocultar' : 'Nuevo contenido'">
                        Nuevo contenido
                    </button>
                @endauth

                <div role="group" aria-label="Forma de ver el listado" class="ml-auto flex items-center gap-1">
                    <button type="button" @click="setView('grid')"
                            :aria-pressed="view === 'grid' ? 'true' : 'false'"
                            :class="view === 'grid' ? 'bg-navy text-on-brand' : 'bg-tint text-muted hover:text-heading'"
                            class="flex size-8 items-center justify-center rounded-[3px] border-0">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <rect x="3" y="3" width="8" height="8" rx="1" />
                            <rect x="13" y="3" width="8" height="8" rx="1" />
                            <rect x="3" y="13" width="8" height="8" rx="1" />
                            <rect x="13" y="13" width="8" height="8" rx="1" />
                        </svg>
                        <span class="sr-only">Ver en cuadrícula</span>
                    </button>

                    <button type="button" @click="setView('list')"
                            :aria-pressed="view === 'list' ? 'true' : 'false'"
                            :class="view === 'list' ? 'bg-navy text-on-brand' : 'bg-tint text-muted hover:text-heading'"
                            class="flex size-8 items-center justify-center rounded-[3px] border-0">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="4" rx="1" />
                            <rect x="3" y="10" width="18" height="4" rx="1" />
                            <rect x="3" y="16" width="18" height="4" rx="1" />
                        </svg>
                        <span class="sr-only">Ver en lista</span>
                    </button>
                </div>
            </div>

            @auth
                {{-- ---------------- Editor incrustado ---------------- --}}
                <div id="huv-editor-documento" x-show="editor" x-cloak
                     class="mb-10 rounded-[4px] border border-line bg-card p-5 lg:p-8">
                    @include('admin.documents.partials.editor', [
                        'topic' => $topic,
                        'document' => $editing ?? new Document(['topic_id' => $topic->id]),
                        'uid' => '-inline',
                    ])
                </div>
            @endauth

            {{-- ---------------- Listado ---------------- --}}
            @if ($documents->isEmpty())
                <p class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    Todavía no hay documentos publicados en {{ $topic->name }}.
                </p>
            @else
                <ul class="grid grid-cols-1 gap-5" :class="view === 'grid' && 'md:grid-cols-2'">
                    @foreach ($documents as $item)
                        <li x-show="isVisible({{ $item->id }})"
                            :style="{ order: positionOf({{ $item->id }}) }"
                            class="flex">
                            <x-document-card :document="$item" />
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Sin resultados tras filtrar --}}
            <p x-show="isEmpty" x-cloak
               class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-8
                      text-center text-14 text-muted">
                No hay documentos que coincidan con la búsqueda.
                <button type="button" @click="reset()"
                        class="border-0 bg-transparent p-0 font-semibold text-link underline underline-offset-4">
                    Quitar los filtros
                </button>
            </p>

            {{-- Paginación --}}
            @if ($documents->isNotEmpty())
                <div class="mt-9 flex flex-col items-center gap-3">
                    <button type="button" @click="loadMore()" x-show="hasMore" x-cloak
                            class="rounded-full border-0 bg-azure px-7 py-3 font-display text-12-5 font-bold
                                   tracking-[0.08em] text-on-accent uppercase transition-colors hover:bg-azure-dark">
                        Cargar más contenidos
                    </button>

                    {{-- aria-live: al pulsar «cargar más» el recuento se anuncia. --}}
                    <p class="m-0 text-12-5 text-muted" aria-live="polite" x-cloak x-show="! isEmpty">
                        Mostrando <span x-text="showing"></span> de <span x-text="total"></span> documentos
                    </p>
                </div>
            @endif
        </x-container>
    </div>
@endsection
