@php
    $hero = config('huv.hero');
    $slides = $hero['slides'];
    $count = count($slides);
    $slideWidth = round(100 / $count, 4);
@endphp

<section id="inicio"
         role="region"
         aria-roledescription="carrusel"
         aria-label="Banner principal"
         x-data="huvCarousel({ count: {{ $count }}, autoplay: {{ $hero['autoplay'] ? 'true' : 'false' }}, seconds: {{ $hero['seconds'] }} })"
         @mouseenter="hovering = true"
         @mouseleave="hovering = false"
         @focusin="focused = true"
         @focusout="focused = false"
         @keydown="onKeydown($event)"
         @touchstart.passive="onTouchStart($event)"
         @touchend.passive="onTouchEnd($event)"
         class="relative overflow-hidden bg-hero-shell">

    <div class="relative w-full overflow-hidden">
        <div id="huv-hero-track" class="flex" style="width: {{ $count * 100 }}%" :style="trackStyle">

            @foreach ($slides as $index => $slide)
                <div class="relative shrink-0 grow-0 {{ ($slide['theme'] ?? null) === 'dark' ? 'bg-navy-deep' : 'bg-slide-shell' }}"
                     style="width: {{ $slideWidth }}%"
                     role="group"
                     aria-roledescription="diapositiva"
                     aria-label="{{ $index + 1 }} de {{ $count }}"
                     :aria-hidden="! isActive({{ $index }})">

                    <div class="absolute inset-0">
                        <x-image-slot :src="$slide['image']"
                                      :alt="$slide['title'] ?? $slide['headline'] ?? ''"
                                      :hint="$slide['image_hint']"
                                      :priority="$index === 0"
                                      :bordered="false" />
                    </div>

                    @if ($slide['type'] === 'award')
                        <div class="absolute inset-0" aria-hidden="true"
                             style="background: var(--huv-scrim-light)"></div>

                        <div class="relative grid min-h-[440px] grid-cols-1 items-center gap-6 px-5 py-10
                                    md:px-16 lg:min-h-[470px] lg:grid-cols-2 lg:gap-10 lg:px-[165px] lg:py-[34px]">

                            <div class="flex flex-col gap-[6px]">
                                <p class="m-0 font-display text-19 font-normal text-ink-soft lg:text-25">
                                    {{ $slide['overline'] }}
                                </p>
                                <p class="m-0 font-display text-24 leading-[1.08] font-extrabold tracking-[-0.01em] text-link lg:text-33">
                                    {{ $slide['headline'] }}
                                </p>
                                <p class="m-0 mb-1 font-display text-19 font-normal text-ink-soft lg:text-24">
                                    {{ $slide['subline'] }}
                                </p>

                                <div class="mt-[6px] flex items-end gap-[14px]">
                                    <span class="font-display text-[3rem] leading-[0.8] font-extrabold tracking-[-0.04em] text-heading lg:text-62">
                                        {{ $slide['rank_number'] }}
                                    </span>
                                    <div class="flex flex-col gap-[3px]">
                                        <span class="font-display text-19 leading-none font-extrabold text-heading lg:text-21">{{ $slide['rank_title'] }}</span>
                                        <span class="font-display text-14 leading-none font-bold text-heading lg:text-15">{{ $slide['rank_subtitle'] }}</span>
                                        <span class="bg-azure px-2 py-[3px] font-display text-12 leading-[1.2] font-bold text-on-accent">{{ $slide['rank_badge'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-[14px]">
                                <p class="m-0 font-display text-19 leading-[1.25] font-normal text-ink-soft lg:text-22">
                                    {{ $slide['intro'] }}
                                </p>
                                <p class="m-0 bg-navy px-5 py-3 font-display text-16-5 leading-[1.2] font-bold text-on-brand lg:text-20">
                                    {{ $slide['highlight'] }}
                                </p>
                                <p class="m-0 font-display text-22 font-extrabold tracking-[0.01em] text-heading lg:text-27">
                                    {{ $slide['year'] }}
                                </p>

                                <ul class="mt-[6px] flex flex-wrap gap-[7px]">
                                    @foreach ($slide['tags'] as $tag)
                                        <li class="rounded-[3px] border border-stroke-strong bg-card/90 px-[9px] py-[5px] text-10-5 font-semibold text-heading">
                                            {{ $tag }}
                                        </li>
                                    @endforeach
                                </ul>

                                <p class="m-0 mt-[2px] text-11 text-muted italic">{{ $slide['source'] }}</p>
                            </div>
                        </div>

                    @elseif (($slide['theme'] ?? null) === 'dark')
                        <div class="absolute inset-0" aria-hidden="true"
                             style="background: var(--huv-scrim-dark)"></div>

                        <div class="relative flex min-h-[440px] flex-col justify-center gap-4 px-5 py-10
                                    md:px-16 lg:min-h-[470px] lg:px-[165px] lg:py-10">
                            <span class="font-display text-13 font-bold tracking-[0.18em] text-azure-mist uppercase">
                                {{ $slide['eyebrow'] }}
                            </span>
                            <h2 class="m-0 max-w-[720px] font-display text-[1.75rem] leading-[1.14] font-extrabold tracking-[-0.015em] text-balance text-on-brand lg:text-40">
                                {{ $slide['title'] }}
                            </h2>
                            <p class="m-0 max-w-[640px] text-15 leading-[1.6] text-pretty text-on-brand-text lg:text-17">
                                {{ $slide['text'] }}
                            </p>
                        </div>

                    @else
                        <div class="absolute inset-0" aria-hidden="true"
                             style="background: var(--huv-scrim-light-strong)"></div>

                        <div class="relative flex min-h-[440px] flex-col justify-center gap-[14px] px-5 py-10
                                    md:px-16 lg:min-h-[470px] lg:px-[165px] lg:py-10">
                            <span class="font-display text-12-5 font-bold tracking-[0.16em] text-link uppercase">
                                {{ $slide['eyebrow'] }}
                            </span>
                            <h2 class="m-0 max-w-[700px] font-display text-[1.6rem] leading-[1.16] font-extrabold tracking-[-0.015em] text-balance text-heading lg:text-37">
                                {{ $slide['title'] }}
                            </h2>
                            <p class="m-0 max-w-[620px] text-15 leading-[1.6] text-pretty text-ink-soft lg:text-16-5">
                                {{ $slide['text'] }}
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Controles laterales --}}
        <button type="button" @click="prev()" aria-label="Banner anterior"
                aria-controls="huv-hero-track" data-huv-control
                class="absolute top-1/2 left-2 z-20 flex size-11 -translate-y-1/2 items-center justify-center
                       rounded-full border-0 bg-control text-on-control transition-colors
                       hover:bg-control-hover lg:left-[92px] lg:size-[54px]">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 5-7 7 7 7" />
            </svg>
        </button>

        <button type="button" @click="next()" aria-label="Banner siguiente"
                aria-controls="huv-hero-track" data-huv-control
                class="absolute top-1/2 right-2 z-20 flex size-11 -translate-y-1/2 items-center justify-center
                       rounded-full border-0 bg-control text-on-control transition-colors
                       hover:bg-control-hover lg:right-[92px] lg:size-[54px]">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m9 5 7 7-7 7" />
            </svg>
        </button>

        {{-- Barra inferior: reproducir/detener + indicadores --}}
        <div data-huv-controlbar
             class="absolute inset-x-0 bottom-0 z-20 grid grid-cols-[1fr_auto_1fr] items-center
                    bg-controlbar px-4 py-[9px] lg:px-[26px]">

            <button type="button" @click="togglePlay()"
                    :aria-label="playing ? 'Detener la reproducción automática del banner' : 'Reproducir el banner automáticamente'"
                    class="flex justify-self-start items-center gap-[9px] border-0 bg-transparent text-13-5 font-semibold text-on-control">
                <svg x-show="playing" class="size-[11px]" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                    <rect x="2" y="1" width="3" height="10" rx="0.5" />
                    <rect x="7" y="1" width="3" height="10" rx="0.5" />
                </svg>
                <svg x-show="! playing" x-cloak class="size-[11px]" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                    <polygon points="2,1 11,6 2,11" />
                </svg>
                <span x-text="playLabel" class="hidden sm:inline">Detener</span>
            </button>

            <div role="group" aria-label="Seleccionar banner" class="flex justify-self-center gap-3">
                @foreach ($slides as $index => $slide)
                    <button type="button" @click="go({{ $index }})"
                            aria-label="Ir al banner {{ $index + 1 }} de {{ $count }}"
                            :aria-current="isActive({{ $index }}) ? 'true' : 'false'"
                            :class="isActive({{ $index }}) ? 'bg-dot-active' : 'bg-transparent'"
                            class="size-[13px] rounded-full border-2 border-on-control p-0 transition-colors"></button>
                @endforeach
            </div>

            {{-- Posición visible; se anuncia por la región aria-live de abajo. --}}
            <span aria-hidden="true" class="justify-self-end text-12 text-on-control/90"
                  x-text="statusLabel"></span>
        </div>

        {{-- Estado del carrusel para lectores de pantalla (solo al estar detenido). --}}
        <span class="sr-only" :aria-live="playing ? 'off' : 'polite'" x-text="statusLabel"></span>
    </div>
</section>
