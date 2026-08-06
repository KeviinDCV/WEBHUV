@php $days = $calendar->days(); @endphp

<section id="eventos" aria-labelledby="huv-eventos" class="relative bg-page">
    <x-edit-chip section="eventos" label="el bloque de eventos"
                 :url="route('admin.events.block.edit')" floating />

    <x-container class="py-12 lg:py-14">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
            <h2 id="huv-eventos"
                class="m-0 font-display text-22 font-bold text-heading underline decoration-2 underline-offset-8 lg:text-26">
                {{ $eventsBlock->name }}
            </h2>

            <div class="flex flex-wrap items-center gap-4">
                @auth
                    <a href="{{ route('admin.events.create') }}"
                       x-show="$store.huvUi.editMode" x-cloak
                       class="inline-flex items-center rounded-full border-0 bg-azure px-5 py-[8px]
                              font-display text-12-5 font-bold tracking-[0.06em] text-on-accent uppercase
                              no-underline transition-colors hover:bg-azure-dark hover:text-on-accent
                              hover:no-underline">
                        Nuevo evento
                    </a>
                @endauth

            {{-- Cambio de vista sin JavaScript: es un formulario que recarga
                 con el nuevo parámetro y vuelve a esta misma sección. --}}
            <form method="GET" action="{{ url('/') }}#eventos" class="flex items-center gap-2">
                <label for="huv-vista" class="sr-only">Ver la agenda por</label>
                <select id="huv-vista" name="vista" onchange="this.form.requestSubmit()"
                        class="rounded-[3px] border border-stroke bg-card px-3 py-[6px] text-13-5
                               font-semibold text-heading">
                    <option value="semana" @selected($calendar->isWeekly())>Semana</option>
                    <option value="mes" @selected(! $calendar->isWeekly())>Mes</option>
                </select>
                <noscript>
                    <button type="submit"
                            class="rounded-[3px] border-0 bg-azure px-3 py-[7px] text-12-5 font-semibold text-on-accent">
                        Aplicar
                    </button>
                </noscript>
            </form>
            </div>
        </div>

        <div class="overflow-hidden rounded-[4px] border border-line">

            {{-- Cabecera del periodo --}}
            <div class="flex items-center justify-between gap-4 bg-azure px-4 py-3 text-on-accent">
                <a href="{{ url('/') }}?{{ http_build_query($calendar->queryFor(-1)) }}#eventos"
                   rel="prev" aria-label="Periodo anterior"
                   class="flex size-8 shrink-0 items-center justify-center rounded-full text-on-accent
                          no-underline hover:bg-black/15 hover:text-on-accent hover:no-underline">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 5-7 7 7 7" />
                    </svg>
                </a>

                <p class="m-0 text-center">
                    <span class="block font-display text-15 font-bold first-letter:uppercase lg:text-17">
                        {{ $calendar->label() }}
                    </span>
                    <span class="block text-12">{{ $calendar->year() }}</span>
                </p>

                <a href="{{ url('/') }}?{{ http_build_query($calendar->queryFor(1)) }}#eventos"
                   rel="next" aria-label="Periodo siguiente"
                   class="flex size-8 shrink-0 items-center justify-center rounded-full text-on-accent
                          no-underline hover:bg-black/15 hover:text-on-accent hover:no-underline">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 5 7 7-7 7" />
                    </svg>
                </a>
            </div>

            {{-- Rejilla. En móvil se apila un día por fila; desde md pasa a las
                 siete columnas de la semana. --}}
            <div class="grid grid-cols-1 md:grid-cols-7">
                @foreach ($days as $index => $day)
                    @php
                        $date = $day['date'];
                        $isFirstRow = $index < 7;
                    @endphp
                    <div class="flex min-h-[110px] flex-col gap-2 border-t border-line p-2
                                md:border-l md:first:border-l-0 md:[&:nth-child(7n+1)]:border-l-0
                                {{ $day['outside'] ? 'bg-surface' : 'bg-card' }}
                                {{ $day['today'] ? 'ring-2 ring-rule-accent ring-inset' : '' }}">

                        <p class="m-0 flex items-baseline justify-between gap-2 md:flex-col md:items-start md:gap-0">
                            <span class="text-11 font-bold tracking-[0.08em] text-faint uppercase {{ $isFirstRow ? 'md:block' : 'md:hidden' }}">
                                {{-- Carbon abrevia con punto («lun.»); el calendario lo omite. --}}
                                {{ rtrim($date->translatedFormat('D'), '.') }}
                            </span>
                            <span class="text-14 font-bold {{ $day['outside'] ? 'text-faint' : 'text-heading' }}">
                                {{ $date->format('j') }}
                                <span class="text-12 font-normal text-muted md:hidden">
                                    {{ $date->translatedFormat('M') }}
                                </span>
                            </span>
                            @if ($day['today'])
                                <span class="text-10-5 font-bold tracking-[0.06em] text-link uppercase">Hoy</span>
                            @endif
                        </p>

                        @foreach ($day['events'] as $event)
                            @php
                                // Con sesión iniciada el evento lleva a su
                                // edición; para el visitante, a su enlace.
                                $target = auth()->check()
                                    ? route('admin.events.edit', $event)
                                    : $event->link();
                                $tag = $target ? 'a' : 'div';
                            @endphp
                            <{{ $tag }} @if ($target) href="{{ $target }}" @endif
                               class="block rounded-[3px] px-2 py-[6px] text-11 leading-[1.3]
                                      font-semibold text-on-accent no-underline
                                      hover:text-on-accent hover:no-underline
                                      {{ $event->is_active ? 'bg-azure hover:bg-azure-dark' : 'bg-faint' }}">
                                <span class="block">{{ $event->starts_at->format('H:i') }}</span>
                                <span class="block">{{ $event->title }}</span>
                                @unless ($event->is_active)
                                    <span class="block text-10-5 uppercase">Inactivo</span>
                                @endunless
                            </{{ $tag }}>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        @if ($calendar->isEmpty())
            <p class="m-0 mt-5 text-14 text-muted">
                No hay eventos programados en este periodo.
            </p>
        @endif

        @auth
            @if (filled($eventsBlock->option('categories')))
                <p x-show="$store.huvUi.editMode" x-cloak class="m-0 mt-4 text-12-5 text-muted">
                    El bloque solo muestra los eventos de las categorías elegidas en su configuración.
                </p>
            @endif
        @endauth
    </x-container>
</section>
