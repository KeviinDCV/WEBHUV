@php
    use App\Models\TopicItem;

    // Seis por tanda, como el portal: comprobado en Preguntas y respuestas,
    // Presupuesto, Planes y Noticias, todos empiezan en seis y el botón añade
    // otros seis. Es el mismo número que el muro de la portada.
    $perPage = config('huv.content_feed.per_page', 6);

    $meta = $items->map(fn (TopicItem $item): array => [
        'id' => $item->id,
        'title' => $item->title,
        'kind' => $item->kind,
        // Lista y no un valor suelto: un contenido puede estar en varias
        // categorías del tema a la vez.
        'categories' => $item->categories->pluck('id'),
        'timestamp' => ($item->date() ?? $item->created_at)->getTimestampMs(),
        // Sin fecha de expedición se ordena al final, no al principio.
        'issued' => $item->issued_at?->getTimestampMs() ?? 0,
        'isActive' => $item->is_active,
        'isFeatured' => $item->is_featured,
        'isHidden' => $item->is_hidden,
    ]);

    // La frase del recuento se arma entera y no por trozos: en otro idioma las
    // cifras no tienen por qué caer en el mismo sitio de la frase. Los «${…}»
    // los resuelve Alpine, que lee el atributo como una plantilla de JavaScript.
    $recuento = __('paginas.listado.mostrando', [
        'visibles' => '${showing}',
        'total' => '${total}',
        'contenidos' => $topic->itemsNoun(),
    ]);

    // El arreglo se arma aquí: @json no admite varias líneas y rompería el PHP
    // generado, algo que `view:cache` no detecta porque solo falla al
    // ejecutarse la vista.
    $topicConfig = [
        'meta' => $meta,
        'categories' => $categories,
        'publicTabs' => $tabs,
        'moderationTabs' => App\Support\ListTabs::of('inactivos', 'destacados', 'ocultos'),
        'perPage' => $perPage,
        'canModerate' => auth()->check(),
        'openEditor' => $editing !== null || $errors->any(),
    ];
@endphp

@extends('layouts.app')

@section('title', $topic->name.' — '.config('huv.institution.short_name'))
@section('description', $topic->description ?: __('paginas.tema.descripcion', [
    'tema' => $topic->name,
    'entidad' => config('huv.institution.name_plain'),
]))

@auth
    @push('head')
        @vite('resources/js/admin.js')
    @endpush
@endauth

@section('content')
    <div class="bg-page" x-data='huvTopicList(@json($topicConfig))'>
        <x-container class="py-8 lg:py-10">

            {{-- ---------------- Rastro de navegación ---------------- --}}
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
                <div class="mt-6 flex flex-wrap items-center gap-2" role="group"
                     aria-label="{{ __('paginas.listado.categorias.filtro') }}">
                    <button type="button" @click="category = 'todas'"
                            :aria-pressed="category === 'todas' ? 'true' : 'false'"
                            :class="category === 'todas'
                                ? 'bg-navy text-on-brand'
                                : 'bg-tint text-link hover:bg-stroke/40'"
                            class="rounded-full border-0 px-4 py-[6px] text-12-5 font-semibold">
                        {{ __('paginas.listado.categorias.todas') }}
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
                            x-text="allCategories ? @js(__('paginas.listado.categorias.ver_menos')) : @js(__('paginas.listado.categorias.ver_mas'))"
                            >{{ __('paginas.listado.categorias.ver_mas') }}</button>

                    {{-- Sin JavaScript las categorías se listan igualmente, para
                         no dejar el filtro inservible. --}}
                    <noscript>
                        <ul class="flex flex-wrap items-center gap-2">
                            @foreach ($categories as $category)
                                <li class="rounded-full bg-tint px-4 py-[6px] text-12-5 font-semibold text-link">
                                    <x-texto-del-portal>{{ $category['name'] }}</x-texto-del-portal> ({{ $category['count'] }})
                                </li>
                            @endforeach
                        </ul>
                    </noscript>
                </div>
            @endif

            {{-- ---------------- Búsqueda ---------------- --}}
            <div class="mt-6">
                <label for="huv-busca-tema" class="sr-only">{{ __('paginas.listado.busqueda.etiqueta', ['tema' => $topic->name]) }}</label>
                <div class="relative">
                    <input id="huv-busca-tema" type="search" x-model="search"
                           placeholder="{{ __('paginas.listado.busqueda.etiqueta', ['tema' => $topic->name]) }}"
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
            <div id="huv-listado" class="mt-6 flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
                {{-- En un tema de orden manual no hay nada que ordenar. Se
                     decide en el servidor y no con x-show: sin JavaScript
                     quedaría un «Ordenar por:» suelto y sin opciones debajo.
                     Con sesión iniciada siguen apareciendo las de moderación. --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2"
                     @if ($tabs === [] && ! auth()->check()) hidden @endif>
                    <span id="huv-orden-tema" class="text-13-5 text-muted">{{ __('paginas.listado.orden.etiqueta') }}</span>

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

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Un tema puede mezclar documentos, noticias y avisos; el
                         portal deja filtrar por tipo cuando eso ocurre. Se
                         decide en el servidor: dentro de un <template> el
                         desplegable estaría en el HTML de todos los temas.

                         Los tipos que hay, no los que el tema admite: «Planeación
                         y presupuesto participativo» declara doce en el origen y
                         publica tres, y ofrecer los otros nueve sería prometer
                         filtros que siempre devuelven la lista vacía. --}}
                    @php($tipos = $topic->kindsInUse())

                    @if (count($tipos) > 1)
                        <label for="huv-tipo-tema" class="sr-only">{{ __('paginas.listado.tipo.etiqueta') }}</label>
                        <select id="huv-tipo-tema" x-model="kind"
                                class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                                       font-semibold text-heading">
                            <option value="todos">{{ __('paginas.listado.tipo.todos') }}</option>
                            @foreach ($tipos as $option)
                                <option value="{{ $option }}">{{ $topic->itemNoun($option) }}</option>
                            @endforeach
                        </select>
                    @endif

                    <label for="huv-periodo-tema" class="sr-only">{{ __('paginas.listado.periodo.etiqueta') }}</label>
                    <select id="huv-periodo-tema" x-model="period"
                            class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                                   font-semibold text-heading">
                        <option value="todos">{{ __('paginas.listado.periodo.etiqueta') }}</option>
                        <option value="30">{{ __('paginas.listado.periodo.mes') }}</option>
                        <option value="180">{{ __('paginas.listado.periodo.semestre') }}</option>
                        <option value="365">{{ __('paginas.listado.periodo.ano') }}</option>
                        <option value="1095">{{ __('paginas.listado.periodo.trienio') }}</option>
                    </select>
                </div>
            </div>

            {{-- ---------------- Alta y vista ---------------- --}}
            <div class="mt-5 mb-8 flex flex-wrap items-center justify-between gap-4">
                @auth
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
                @endauth

                <div role="group" aria-label="{{ __('paginas.listado.vista.etiqueta') }}"
                     class="ml-auto flex items-center gap-1">
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
                        <span class="sr-only">{{ __('paginas.listado.vista.cuadricula') }}</span>
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
                        <span class="sr-only">{{ __('paginas.listado.vista.lista') }}</span>
                    </button>
                </div>
            </div>

            @auth
                {{-- ---------------- Editor incrustado ---------------- --}}
                <div id="huv-editor-tema" x-show="editor" x-cloak
                     class="mb-10 rounded-[4px] border border-line bg-card p-5 lg:p-8">
                    @include('admin.topics.partials.editor', [
                        'topic' => $topic,
                        // Un contenido nuevo arranca con la plantilla del tema,
                        // igual que en el portal actual.
                        'item' => $editing ?? new TopicItem([
                            'topic_id' => $topic->id,
                            'kind' => $topic->defaultKind(),
                            'body' => $topic->content_template,
                        ]),
                        'uid' => '-inline',
                    ])
                </div>
            @endauth

            {{-- ---------------- Listado ---------------- --}}
            @if ($items->isEmpty())
                <p class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    {{ __('paginas.listado.vacio', ['tema' => $topic->name]) }}
                </p>
            @else
                @if ($topic->isRowList())
                    {{-- Filas con una raya en medio, como publica el portal
                         «Normatividad»: sin tarjeta y a una sola columna. --}}
                    <ul class="flex flex-col">
                        @foreach ($items as $item)
                            <li x-show="isVisible({{ $item->id }})"
                                :style="{ order: positionOf({{ $item->id }}) }"
                                class="border-b border-line py-5 first:pt-0">
                                <x-topic-item-row :item="$item" />
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{-- Cada tarjeta mide lo que mide su texto: sin esto la rejilla estira la celda a la altura de la fila y el recuadro corto sale con un hueco dentro. --}}
                <ul class="grid grid-cols-1 items-start gap-5" :class="view === 'grid' && 'md:grid-cols-2'">
                        @foreach ($items as $item)
                            <li x-show="isVisible({{ $item->id }})"
                                :style="{ order: positionOf({{ $item->id }}) }"
                                class="flex">
                                <x-topic-item-card :item="$item" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            {{-- Sin resultados tras filtrar --}}
            <p x-show="isEmpty" x-cloak
               class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-8
                      text-center text-14 text-muted">
                {{ __('paginas.listado.sin_resultados') }}
                <button type="button" @click="reset()"
                        class="border-0 bg-transparent p-0 font-semibold text-link underline underline-offset-4">
                    {{ __('paginas.listado.quitar_filtros') }}
                </button>
            </p>

            {{-- Paginación --}}
            @if ($items->isNotEmpty())
                <div class="mt-9 flex flex-col items-center gap-3">
                    <button type="button" @click="loadMore()" x-show="hasMore" x-cloak
                            class="rounded-full border-0 bg-azure px-7 py-3 font-display text-12-5 font-bold
                                   tracking-[0.08em] text-on-accent uppercase transition-colors hover:bg-azure-dark">
                        {{ __('paginas.listado.cargar_mas') }}
                    </button>

                    {{-- aria-live: al pulsar «cargar más» el recuento se anuncia. --}}
                    <p class="m-0 text-12-5 text-muted" aria-live="polite" x-cloak x-show="! isEmpty"
                       x-text="`{{ $recuento }}`"></p>
                </div>
            @endif
        </x-container>
    </div>

    {{-- Fuera del contenedor: el mapa va de borde a borde, como en el portal. --}}
    @if ($map = $topic->map())
        <x-location-map :map="$map" />
    @endif
@endsection
