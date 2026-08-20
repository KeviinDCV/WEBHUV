@php
    use App\Models\TopicItem;
@endphp

@extends('layouts.app')

@section('title', $topic->name.' — '.config('huv.institution.short_name'))
@section('description', $topic->description ?: __('paginas.tema.descripcion_enlaces', [
    'tema' => $topic->name,
    'entidad' => config('huv.institution.name_plain'),
]))

@auth
    @push('head')
        @vite('resources/js/admin.js')
    @endpush
@endauth

@section('content')
    {{--
        Listado de un tema de enlaces.

        A diferencia del resto de temas, aquí se pagina en el servidor y los
        filtros viajan en la dirección: con setecientas contrataciones no se
        pueden imprimir todas y decidir después cuáles se ven. La consecuencia
        buena es que la página se puede compartir tal como se está viendo y que
        funciona igual sin JavaScript.
    --}}
    <div class="bg-page" x-data="{ editor: {{ $editing || $errors->any() ? 'true' : 'false' }} }">
        <x-container class="py-8 lg:py-10">

            <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                    <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                    <li aria-hidden="true">›</li>
                    <x-texto-del-portal tag="li" aria-current="page" class="font-semibold text-heading">{{ $topic->name }}</x-texto-del-portal>
                </ol>
            </nav>

            <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                <x-texto-del-portal>{{ $topic->name }}</x-texto-del-portal>
            </h1>

            @if (filled($topic->description))
                <x-texto-del-portal tag="p" class="m-0 mt-2 max-w-[70ch] text-14-5 text-muted">{{ $topic->description }}</x-texto-del-portal>
            @endif

            {{-- ---------------- Categorías ---------------- --}}
            @if ($categories->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center gap-2"
                     x-data="{ todas: false }" role="group"
                     aria-label="{{ __('paginas.listado.categorias.filtro') }}">
                    <a href="{{ route('topics.show', $topic) }}"
                       @class([
                           'rounded-full px-4 py-[6px] text-12-5 font-semibold no-underline hover:no-underline',
                           'bg-navy text-on-brand' => ! $categoriaActiva,
                           'bg-tint text-link hover:bg-stroke/40' => $categoriaActiva,
                       ])>
                        {{ __('paginas.listado.categorias.todas') }}
                    </a>

                    @foreach ($categories as $i => $category)
                        <a href="{{ route('topics.show', [$topic, 'categoria' => $category['id']]) }}"
                           @if ($i >= 5) x-show="todas" x-cloak @endif
                           @class([
                               'rounded-full px-4 py-[6px] text-12-5 font-semibold no-underline hover:no-underline',
                               'bg-navy text-on-brand' => $categoriaActiva === $category['id'],
                               'bg-tint text-link hover:bg-stroke/40' => $categoriaActiva !== $category['id'],
                           ])>
                            <x-texto-del-portal>{{ $category['name'] }}</x-texto-del-portal> ({{ $category['count'] }})
                        </a>
                    @endforeach

                    @if ($categories->count() > 5)
                        <button type="button" @click="todas = ! todas"
                                :aria-expanded="todas ? 'true' : 'false'"
                                class="border-0 bg-transparent px-2 py-[6px] text-12-5 font-semibold text-link
                                       underline underline-offset-4"
                                x-text="todas ? @js(__('paginas.listado.categorias.ver_menos')) : @js(__('paginas.listado.categorias.ver_mas'))"
                                >{{ __('paginas.listado.categorias.ver_mas') }}</button>
                    @endif
                </div>
            @endif

            {{-- ---------------- Búsqueda y orden ---------------- --}}
            <form method="GET" action="{{ route('topics.show', $topic) }}" class="mt-6">
                @if ($categoriaActiva)
                    <input type="hidden" name="categoria" value="{{ $categoriaActiva }}">
                @endif

                <div class="relative">
                    <label for="huv-busca-tema" class="sr-only">{{ __('paginas.listado.busqueda.etiqueta', ['tema' => $topic->name]) }}</label>
                    <input id="huv-busca-tema" name="buscar" type="search" value="{{ $buscar }}"
                           placeholder="{{ __('paginas.listado.busqueda.etiqueta', ['tema' => $topic->name]) }}"
                           class="w-full rounded-[3px] border border-stroke bg-card py-[10px] pr-12 pl-4
                                  text-14 text-ink">
                    <button type="submit"
                            class="absolute top-1/2 right-2 flex size-8 -translate-y-1/2 items-center
                                   justify-center rounded-full border-0 bg-azure text-on-accent
                                   hover:bg-azure-dark">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        <span class="sr-only">{{ __('paginas.listado.busqueda.boton') }}</span>
                    </button>
                </div>

                <div id="huv-listado" class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span class="text-13-5 text-muted">{{ __('paginas.listado.orden.etiqueta') }}</span>

                    @foreach (['recientes', 'az'] as $key)
                        {{-- `aria-current` se escribe a mano: Blade no tiene una
                             directiva para él, y `@aria-current(...)` se imprime
                             tal cual en el HTML, donde Alpine lo lee como el
                             atajo de un evento y revienta. --}}
                        <button type="submit" name="orden" value="{{ $key }}"
                                @if ($orden === $key) aria-current="true" @endif
                                @class([
                                    'border-0 bg-transparent p-0 text-13-5',
                                    'font-bold text-heading' => $orden === $key,
                                    'font-medium text-link hover:text-heading' => $orden !== $key,
                                ])>
                            {{ __('paginas.listado.orden.'.$key) }}
                        </button>
                    @endforeach
                </div>
            </form>

            {{-- ---------------- Alta ---------------- --}}
            @auth
                <div class="mt-5 mb-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.topics.bulk.create', $topic) }}"
                       x-show="$store.huvUi.editMode" x-cloak
                       class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[8px]
                              font-display text-13-5 font-semibold text-on-accent no-underline
                              transition-colors hover:bg-azure-dark hover:no-underline">
                        {{ __('paginas.listado.carga_masiva') }}
                    </a>

                    <button type="button" @click="editor = ! editor"
                            x-show="$store.huvUi.editMode" x-cloak
                            :aria-expanded="editor ? 'true' : 'false'"
                            aria-controls="huv-editor-tema"
                            data-huv-edit="tema"
                            class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[8px]
                                   font-display text-13-5 font-semibold text-on-accent
                                   transition-colors hover:bg-azure-dark"
                            x-text="editor ? @js(__('paginas.listado.ocultar')) : @js(__('paginas.listado.nuevo'))">
                        {{ __('paginas.listado.nuevo') }}
                    </button>
                </div>

                <div id="huv-editor-tema" x-show="editor" x-cloak
                     class="mb-10 rounded-[4px] border border-line bg-card p-5 lg:p-8">
                    @include('admin.topics.partials.editor', [
                        'topic' => $topic,
                        'item' => $editing ?? new TopicItem([
                            'topic_id' => $topic->id,
                            'kind' => TopicItem::KIND_LINK,
                            'body' => $topic->content_template,
                        ]),
                        'uid' => '-inline',
                    ])
                </div>
            @endauth

            {{-- ---------------- Listado ---------------- --}}
            @if ($items->isEmpty())
                <p class="m-0 mt-8 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    @if ($buscar !== '' || $categoriaActiva)
                        {{ __('paginas.listado.sin_resultados') }}
                        <a href="{{ route('topics.show', $topic) }}"
                           class="font-semibold text-link underline underline-offset-4">{{ __('paginas.listado.quitar_filtros') }}</a>
                    @else
                        {{ __('paginas.listado.vacio', ['tema' => $topic->name]) }}
                    @endif
                </p>
            @else
                <ul class="mt-8 flex flex-col">
                    @foreach ($items as $item)
                        <li class="border-b border-line py-5 first:pt-0">
                            <x-topic-item-row :item="$item" />
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    {{ $items->onEachSide(1)->links('vendor.pagination.huv') }}
                </div>

                <p class="m-0 mt-3 text-center text-12-5 text-muted" aria-live="polite">
                    {{ __('paginas.listado.mostrando_pagina', [
                        'desde' => $items->firstItem(),
                        'hasta' => $items->lastItem(),
                        {{-- El separador de millares lo pone el idioma en curso: en
                             inglés «1,234» y en español «1.234». No se usa
                             Number::format() porque exige la extensión intl,
                             que el servidor no tiene. --}}
                        'total' => number_format($items->total(), 0, '', __('componentes.numero.millares')),
                    ]) }}
                </p>
            @endif
        </x-container>
    </div>
@endsection
