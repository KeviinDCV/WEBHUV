@php
    $items = config('huv.nav');
    $mega = config('huv.mega_menu');
    $megaKeys = array_column($mega, 'key');
@endphp

<nav aria-label="Menú principal"
     x-data="huvNav(@js($megaKeys))"
     @keydown.escape="onEscape($event)"
     @focusout="onFocusOut($event)"
     @click.outside="close()"
     class="relative border-b-[3px] border-rule-brand bg-surface">

    <x-container class="flex items-stretch">

        {{-- ---------------- Barra de escritorio ---------------- --}}
        <div class="hidden w-full items-stretch lg:flex">
            @foreach ($items as $item)
                @if (empty($item['children']))
                    @php
                        $aqui = App\Support\LegacyLink::isCurrent($item);
                    @endphp

                    <a href="{{ App\Support\LegacyLink::resolve($item)['href'] }}"
                       @if ($aqui) aria-current="page" @endif
                       class="{{ $aqui
                           ? 'bg-azure px-[34px] font-semibold text-14-5 text-on-accent hover:bg-azure-dark hover:text-on-accent'
                           : 'px-[22px] font-medium text-13-5 text-heading hover:bg-tint-hover' }}
                              flex min-h-[58px] items-center font-display leading-[1.3] no-underline hover:no-underline
                              {{ ! empty($item['narrow']) ? 'max-w-[210px]' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @else
                    @php
                        // El desplegable crece hacia los lados en vez de hacer scroll:
                        // los ítems fluyen en vertical y saltan a la columna siguiente
                        // al pasar de $maxPorColumna, como un listado a varias columnas.
                        $maxPorColumna = 9;
                        $total = count($item['children']);
                        $columnas = min(3, (int) ceil($total / $maxPorColumna));
                        $filas = (int) ceil($total / $columnas);

                        // El desplegable al que pertenece la página abierta.
                        $aqui = App\Support\LegacyLink::isCurrent($item);
                    @endphp
                    <div class="relative flex"
                         @mouseenter="hoverOpen('{{ $item['key'] }}')"
                         @mouseleave="hoverClose()">
                        <button type="button"
                                data-huv-trigger="{{ $item['key'] }}"
                                @click="toggle('{{ $item['key'] }}')"
                                aria-haspopup="true"
                                {{-- «true» y no «page»: la página abierta es una
                                     de las de dentro, no este botón. --}}
                                @if ($aqui) aria-current="true" @endif
                                :aria-expanded="isOpen('{{ $item['key'] }}') ? 'true' : 'false'"
                                aria-controls="huv-menu-{{ $item['key'] }}"
                                class="{{ $aqui
                                    ? 'bg-azure font-semibold text-on-accent hover:bg-azure-dark'
                                    : 'bg-transparent font-medium text-heading hover:bg-tint-hover' }}
                                       flex min-h-[58px] items-center gap-2 border-0 px-[22px]
                                       text-left font-display text-13-5 leading-[1.3]
                                       {{ ! empty($item['narrow']) ? 'max-w-[210px]' : '' }}">
                            {{ $item['label'] }}
                            <svg class="size-[9px] shrink-0 transition-transform duration-150"
                                 :class="isOpen('{{ $item['key'] }}') && 'rotate-180'"
                                 viewBox="0 0 10 6" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
                                <path d="m1 1 4 4 4-4" />
                            </svg>
                        </button>

                        <div x-show="isOpen('{{ $item['key'] }}')" x-cloak
                             id="huv-menu-{{ $item['key'] }}"
                             data-huv-panel
                             class="huv-fade absolute top-full left-0 z-40 w-max max-w-[calc(100vw-2rem)]
                                    border border-line border-t-[3px] border-t-rule-accent bg-card py-[10px]
                                    shadow-[0_14px_34px_rgba(23,32,64,0.16)]">
                            {{-- Ancho de columna fijo: obliga a las etiquetas largas a
                                 romper línea en vez de estirar el panel. --}}
                            <ul class="grid"
                                @if ($columnas > 1)
                                    style="grid-auto-flow: column;
                                           grid-template-rows: repeat({{ $filas }}, auto);
                                           grid-auto-columns: 16rem"
                                @else
                                    style="width: 20rem"
                                @endif>
                                @foreach ($item['children'] as $child)
                                    <li>
                                        <a href="{{ App\Support\LegacyLink::resolve($child)['href'] }}"
                                           class="block h-full px-[22px] py-[9px] text-13-5 text-ink no-underline
                                                  hover:bg-tint hover:text-heading hover:no-underline">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="ml-auto flex">
                <button type="button"
                        data-huv-trigger="mega"
                        @click="toggle('mega')"
                        aria-label="Abrir menú completo"
                        :aria-expanded="isOpen('mega') ? 'true' : 'false'"
                        aria-controls="huv-megamenu"
                        class="flex w-[62px] items-center justify-center border-0 bg-transparent text-heading hover:bg-tint-hover">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- ---------------- Barra móvil / tableta ---------------- --}}
        <div class="flex w-full items-stretch justify-between lg:hidden">
            {{-- En móvil solo cabe «Inicio», y se resalta únicamente cuando se
                 está en la portada: marcarlo siempre era decirle a quien navega
                 que está en una página en la que no está. --}}
            @php
                $enPortada = request()->getPathInfo() === '/';
            @endphp

            <a href="{{ url('/') }}"
               @if ($enPortada) aria-current="page" @endif
               class="{{ $enPortada
                   ? 'bg-azure text-on-accent hover:text-on-accent'
                   : 'text-heading hover:bg-tint-hover hover:text-heading' }}
                      flex min-h-[52px] items-center px-6 font-display text-14-5 font-semibold
                      no-underline hover:no-underline">
                Inicio
            </a>
            <button type="button"
                    x-ref="mobileTrigger"
                    @click="toggleMobile()"
                    :aria-expanded="mobileOpen ? 'true' : 'false'"
                    aria-controls="huv-menu-movil"
                    class="flex items-center gap-2 border-0 bg-transparent px-4 font-display text-13-5
                           font-semibold text-heading hover:bg-tint-hover">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path x-show="! mobileOpen" d="M3 6h18M3 12h18M3 18h18" />
                    <path x-show="mobileOpen" x-cloak d="M5 5l14 14M19 5L5 19" />
                </svg>
                <span x-text="mobileOpen ? 'Cerrar' : 'Menú'">Menú</span>
            </button>
        </div>
    </x-container>

    {{-- ---------------- Menú completo (escritorio) ----------------
         Dos niveles: las categorías a la izquierda y sus enlaces al lado.
         Sigue el patrón de pestañas verticales de WAI-ARIA. --}}
    <div x-show="isOpen('mega')" x-cloak
         id="huv-megamenu"
         @mouseleave="hoverClose()"
         data-huv-panel
         class="huv-fade absolute inset-x-0 top-full z-40 hidden border-t border-line bg-card
                shadow-[0_16px_30px_rgba(23,32,64,0.12)] lg:block">
        <x-container class="grid grid-cols-[260px_minmax(0,1fr)] py-6">

            <div data-huv-tablist role="tablist" aria-orientation="vertical"
                 aria-label="Categorías del menú completo"
                 class="flex flex-col border-r border-line pr-4">
                @foreach ($mega as $column)
                    <button type="button"
                            role="tab"
                            data-huv-tab="{{ $column['key'] }}"
                            id="huv-tab-{{ $column['key'] }}"
                            aria-controls="huv-tabpanel-{{ $column['key'] }}"
                            :aria-selected="isTab('{{ $column['key'] }}') ? 'true' : 'false'"
                            :tabindex="isTab('{{ $column['key'] }}') ? 0 : -1"
                            @click="selectTab('{{ $column['key'] }}')"
                            @mouseenter="selectTab('{{ $column['key'] }}')"
                            @keydown="onTabKeydown($event, '{{ $column['key'] }}')"
                            :class="isTab('{{ $column['key'] }}')
                                ? 'bg-tint text-heading border-l-rule-accent'
                                : 'border-l-transparent text-body hover:bg-tint hover:text-heading'"
                            class="flex items-center justify-between gap-3 border-0 border-l-[3px] bg-transparent
                                   px-4 py-[13px] text-left font-display text-14 font-semibold transition-colors">
                        {{ $column['title'] }}
                        <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m9 5 7 7-7 7" />
                        </svg>
                    </button>
                @endforeach
            </div>

            @foreach ($mega as $column)
                <div role="tabpanel"
                     id="huv-tabpanel-{{ $column['key'] }}"
                     aria-labelledby="huv-tab-{{ $column['key'] }}"
                     x-show="isTab('{{ $column['key'] }}')" x-cloak
                     tabindex="0"
                     class="col-start-2 row-start-1 max-h-[min(62vh,540px)] overflow-y-auto overscroll-contain pl-8">

                    <h3 class="m-0 mb-3 font-display text-13 font-bold tracking-[0.06em] text-heading uppercase">
                        {{ $column['title'] }}
                        <span class="ml-1 font-normal text-faint normal-case">
                            ({{ count($column['links']) }})
                        </span>
                    </h3>

                    {{-- Columnas fijas: las etiquetas largas rompen línea en
                         lugar de estirar el panel. --}}
                    <ul class="grid gap-x-8 gap-y-[7px]"
                        style="grid-template-columns: repeat({{ $column['columns'] ?? 2 }}, minmax(0, 1fr))">
                        @foreach ($column['links'] as $link)
                            <li>
                                <x-menu-link :link="$link"
                                             class="block text-13-5 leading-[1.4] text-body hover:text-heading" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </x-container>
    </div>

    {{-- ---------------- Cajón móvil ---------------- --}}
    <div x-show="mobileOpen" x-cloak class="lg:hidden">
        <div @click="toggleMobile()"
             class="fixed inset-0 z-40 bg-overlay"
             aria-hidden="true"></div>

        {{-- `x-trap.noscroll`: siendo un diálogo modal, el foco no debe poder
             escaparse al contenido de detrás mientras está abierto. --}}
        <div id="huv-menu-movil" role="dialog" aria-modal="true" aria-label="Menú principal"
             x-trap.noscroll="mobileOpen"
             data-huv-panel
             class="fixed inset-y-0 right-0 z-50 flex w-[min(88vw,380px)] flex-col overflow-y-auto border-l border-line bg-card
                    shadow-[-8px_0_30px_rgba(23,32,64,0.22)]">

            <div class="flex items-center justify-between border-b border-line bg-surface px-5 py-4">
                <span class="font-display text-14 font-bold tracking-[0.06em] text-heading uppercase">Menú</span>
                <button type="button" @click="toggleMobile()" aria-label="Cerrar menú"
                        class="flex size-9 items-center justify-center rounded-full border-0 bg-transparent text-heading hover:bg-tint">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M5 5l14 14M19 5L5 19" />
                    </svg>
                </button>
            </div>

            <ul class="flex flex-col">
                @foreach ($items as $item)
                    <li class="border-b border-line-soft">
                        @if (empty($item['children']))
                            <a href="{{ App\Support\LegacyLink::resolve($item)['href'] }}"
                               class="block px-5 py-[14px] font-display text-14 font-medium text-heading no-underline hover:bg-tint">
                                {{ $item['label'] }}
                            </a>
                        @else
                            <button type="button"
                                    @click="toggleMobileSection('{{ $item['key'] }}')"
                                    :aria-expanded="mobileSection === '{{ $item['key'] }}' ? 'true' : 'false'"
                                    aria-controls="huv-movil-{{ $item['key'] }}"
                                    class="flex w-full items-center justify-between gap-3 border-0 bg-transparent px-5 py-[14px]
                                           text-left font-display text-14 font-medium text-heading hover:bg-tint">
                                <span>{{ $item['label'] }}</span>
                                <svg class="size-3 shrink-0 transition-transform duration-150"
                                     :class="mobileSection === '{{ $item['key'] }}' && 'rotate-180'"
                                     viewBox="0 0 10 6" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true">
                                    <path d="m1 1 4 4 4-4" />
                                </svg>
                            </button>
                            <ul id="huv-movil-{{ $item['key'] }}"
                                x-show="mobileSection === '{{ $item['key'] }}'" x-cloak
                                x-collapse
                                class="bg-surface pb-2">
                                @foreach ($item['children'] as $child)
                                    <li>
                                        <a href="{{ App\Support\LegacyLink::resolve($child)['href'] }}"
                                           class="block px-5 py-[10px] pl-8 text-13-5 text-ink no-underline hover:text-heading">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Menú completo: en el cajón, las mismas categorías se despliegan
                 como acordeón en lugar de pestañas. --}}
            <ul class="flex flex-col">
                @foreach ($mega as $column)
                    <li class="border-b border-line-soft">
                        <button type="button"
                                @click="toggleMobileSection('{{ $column['key'] }}')"
                                :aria-expanded="mobileSection === '{{ $column['key'] }}' ? 'true' : 'false'"
                                aria-controls="huv-movil-{{ $column['key'] }}"
                                class="flex w-full items-center justify-between gap-3 border-0 bg-transparent px-5 py-[14px]
                                       text-left font-display text-14 font-medium text-heading hover:bg-tint">
                            <span>
                                {{ $column['title'] }}
                                <span class="font-normal text-faint">({{ count($column['links']) }})</span>
                            </span>
                            <svg class="size-3 shrink-0 transition-transform duration-150"
                                 :class="mobileSection === '{{ $column['key'] }}' && 'rotate-180'"
                                 viewBox="0 0 10 6" fill="none" stroke="currentColor"
                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
                                <path d="m1 1 4 4 4-4" />
                            </svg>
                        </button>

                        <ul id="huv-movil-{{ $column['key'] }}"
                            x-show="mobileSection === '{{ $column['key'] }}'" x-cloak
                            x-collapse
                            class="bg-surface pb-2">
                            @foreach ($column['links'] as $link)
                                <li>
                                    <x-menu-link :link="$link"
                                                 class="block px-5 py-[10px] pl-8 text-13-5 leading-[1.4] text-ink
                                                        no-underline hover:text-heading" />
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>

        </div>
    </div>
</nav>
