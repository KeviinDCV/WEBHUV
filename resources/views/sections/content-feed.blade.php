@php
    use App\Models\Content;

    $perPage = config('huv.content_feed.per_page', 6);

    $meta = $feed->map(fn (Content $item): array => [
        'id' => $item->id,
        'category' => $item->category,
        'timestamp' => ($item->displayDate() ?? $item->created_at)->getTimestampMs(),
        'isActive' => $item->is_active,
        'isFeatured' => $item->is_featured,
        'isHidden' => $item->is_hidden,
    ]);

    // El arreglo se arma aquí: @json no admite varias líneas y rompería el
    // PHP generado, algo que `view:cache` no detecta porque solo falla al
    // ejecutarse la vista.
    $feedConfig = [
        'meta' => $meta,
        'perPage' => $perPage,
        'canModerate' => auth()->check(),
        // El editor se despliega solo si se llegó a editar un contenido o si
        // el guardado falló y hay que corregir algo.
        'openEditor' => isset($editing) || $errors->any(),
    ];
@endphp

@auth
    @push('head')
        @vite('resources/js/admin.js')
    @endpush
@endauth

<section aria-labelledby="huv-contenidos" class="border-y border-line-pale bg-surface"
         x-data='huvContentFeed(@json($feedConfig))'>
    <x-container class="py-12 lg:py-14">

        <h2 id="huv-contenidos" class="sr-only">Todos los contenidos publicados</h2>

        {{-- ---------------- Controles ---------------- --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-8 gap-y-4">

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <span id="huv-orden" class="text-13-5 text-muted">Ordenar por:</span>

                <div role="tablist" aria-labelledby="huv-orden" class="flex flex-wrap items-center gap-x-5 gap-y-2">
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

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                <div class="flex items-center gap-2">
                    <label for="huv-periodo" class="sr-only">Filtrar por fecha</label>
                    <select id="huv-periodo" x-model="period"
                            class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                                   font-semibold text-heading">
                        <option value="todos">Filtrar por fecha</option>
                        <option value="7">Última semana</option>
                        <option value="30">Último mes</option>
                        <option value="365">Último año</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label for="huv-categoria" class="sr-only">Filtrar por tipo de contenido</label>
                    <select id="huv-categoria" x-model="category"
                            class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                                   font-semibold text-heading">
                        <option value="todos">Todos los contenidos</option>
                        @foreach (Content::CATEGORIES as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ---------------- Alta y vista ---------------- --}}
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            @auth
                {{-- El editor se abre aquí mismo, sin salir de la portada. --}}
                <button type="button" @click="editor = ! editor"
                        x-show="$store.huvUi.editMode" x-cloak
                        :aria-expanded="editor ? 'true' : 'false'"
                        aria-controls="huv-editor-contenido"
                        data-huv-edit="contenidos"
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
            <div id="huv-editor-contenido" x-show="editor" x-cloak
                 class="mb-10 rounded-[4px] border border-line bg-card p-5 lg:p-8">
                @include('admin.contents.partials.editor', [
                    'content' => $editing ?? new \App\Models\Content([
                        'category' => \App\Models\Content::NEWS_CATEGORY,
                        'show_in_feed' => true,
                        'is_active' => true,
                        'published_at' => now(),
                    ]),
                    'uid' => '-inline',
                ])
            </div>
        @endauth

        {{-- ---------------- Listado ---------------- --}}
        @if ($feed->isEmpty())
            <p class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                      text-center text-14 text-muted">
                Todavía no hay contenidos publicados.
            </p>
        @else
            <ul class="grid grid-cols-1 gap-6" :class="view === 'grid' && 'md:grid-cols-2'">
                @foreach ($feed as $item)
                    <li x-show="isVisible({{ $item->id }})"
                        :style="{ order: positionOf({{ $item->id }}) }"
                        class="flex">
                        <x-content-card :content="$item" />
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Sin resultados tras filtrar --}}
        <p x-show="isEmpty" x-cloak
           class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-8 text-center text-14 text-muted">
            No hay contenidos que coincidan con los filtros seleccionados.
            <button type="button" @click="reset()"
                    class="border-0 bg-transparent p-0 font-semibold text-link underline underline-offset-4">
                Quitar los filtros
            </button>
        </p>

        {{-- Paginación --}}
        @if ($feed->isNotEmpty())
            <div class="mt-9 flex flex-col items-center gap-3">
                <button type="button" @click="loadMore()" x-show="hasMore" x-cloak
                        class="rounded-full border-0 bg-azure px-7 py-3 font-display text-12-5 font-bold
                               tracking-[0.08em] text-on-accent uppercase transition-colors hover:bg-azure-dark">
                    Cargar más contenidos
                </button>

                {{-- aria-live: al pulsar «cargar más» el recuento se anuncia. --}}
                <p class="m-0 text-12-5 text-muted" aria-live="polite" x-cloak x-show="! isEmpty">
                    Mostrando <span x-text="showing"></span> de <span x-text="total"></span> contenidos
                </p>
            </div>
        @endif
    </x-container>
</section>
