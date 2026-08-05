@php
    use Illuminate\Support\Carbon;

    $feed = config('huv.content_feed');

    // Se resuelven las fechas y se ordena de más reciente a más antiguo, que es
    // el estado inicial y también lo que ve quien navega sin JavaScript.
    $items = collect($feed['items'])
        ->map(fn (array $item, int $i): array => $item + [
            'id' => $i,
            'date' => Carbon::parse($item['published_at']),
        ])
        ->sortByDesc(fn (array $item) => $item['date'])
        ->values();

    $meta = $items->map(fn (array $item): array => [
        'id' => $item['id'],
        'category' => $item['category'],
        'timestamp' => $item['date']->getTimestampMs(),
    ]);
@endphp

<section aria-labelledby="huv-contenidos" class="border-y border-line-pale bg-surface"
         x-data='huvContentFeed(@json(["meta" => $meta, "perPage" => $feed["per_page"]]))'>
    <x-container class="py-12 lg:py-14">

        <x-edit-chip section="contenidos" label="contenidos" />

        <h2 id="huv-contenidos" class="sr-only">Todos los contenidos publicados</h2>

        {{-- Controles --}}
        <div class="mb-8 flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
            <div class="flex items-center gap-2">
                <label for="huv-orden" class="text-13-5 text-muted">Ordenar por:</label>
                <select id="huv-orden" x-model="order"
                        class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                               font-semibold text-heading">
                    <option value="recientes">Recientes</option>
                    <option value="antiguos">Más antiguos</option>
                </select>
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
                        @foreach ($feed['categories'] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Listado --}}
        <ul class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($items as $item)
                <li x-show="isVisible({{ $item['id'] }})"
                    :style="{ order: positionOf({{ $item['id'] }}) }"
                    class="flex">
                    <article class="flex w-full flex-col overflow-hidden rounded-[4px] border border-line bg-card
                                    transition hover:shadow-[0_10px_26px_rgba(23,32,64,0.1)]">
                        @if (array_key_exists('image_hint', $item))
                            <a href="{{ $item['url'] }}" tabindex="-1" aria-hidden="true" class="block h-[150px]">
                                <x-image-slot :src="$item['image']" :alt="''" :hint="$item['image_hint']" />
                            </a>
                        @endif

                        <div class="flex flex-1 flex-col gap-2 p-5">
                            <p class="m-0 flex flex-wrap items-center gap-x-2 text-12 text-faint">
                                <x-published-at :value="$item['date']" />
                                <span aria-hidden="true">·</span>
                                <span>{{ $item['category'] }}</span>
                            </p>

                            <h3 class="m-0 font-display text-15 leading-[1.4] font-bold text-balance">
                                <a href="{{ $item['url'] }}"
                                   class="text-heading underline decoration-1 underline-offset-4 hover:text-heading-hover">
                                    {{ $item['title'] }}
                                </a>
                            </h3>

                            <p class="m-0 text-13-5 leading-[1.6] text-pretty text-muted">{{ $item['excerpt'] }}</p>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>

        {{-- Sin resultados --}}
        <p x-show="isEmpty" x-cloak
           class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-8 text-center text-14 text-muted">
            No hay contenidos que coincidan con los filtros seleccionados.
            <button type="button" @click="reset()"
                    class="border-0 bg-transparent p-0 font-semibold text-link underline underline-offset-4">
                Quitar los filtros
            </button>
        </p>

        {{-- Paginación --}}
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
    </x-container>
</section>
