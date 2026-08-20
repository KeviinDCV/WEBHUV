@php
    use App\Models\Banner;

    $count = $banners->count();
    $slideWidth = $count > 0 ? round(100 / $count, 4) : 100;
@endphp

@if ($count === 0)
    {{-- La sección se reserva aunque no haya banners: así el espacio del
         carrusel no desaparece y la portada no se recoloca al publicar el
         primero. --}}
    <section id="inicio" aria-label="{{ __('portada.banner.region') }}" class="relative overflow-hidden bg-hero-shell">

        <x-edit-chip section="banner" :label="__('portada.chip.banner')" :url="route('admin.banners.index')" floating />

        <div class="flex min-h-[440px] flex-col items-center justify-center gap-4 border border-dashed
                    border-stroke-strong px-5 text-center lg:min-h-[max(320px,25.81vw)]"
             style="aspect-ratio: {{ \App\Models\Banner::IMAGE_WIDTH }} / {{ \App\Models\Banner::IMAGE_HEIGHT }}">

            <p class="m-0 text-13-5 font-medium text-faint">
                {{ __('portada.banner.marcador', [
                    'ancho' => \App\Models\Banner::IMAGE_WIDTH,
                    'alto' => \App\Models\Banner::IMAGE_HEIGHT,
                ]) }}
            </p>

            @auth
                <a href="{{ route('admin.banners.index') }}"
                   class="inline-flex items-center rounded-full border border-rule-accent bg-card px-5 py-[10px]
                          font-display text-14 font-semibold text-link no-underline hover:bg-tint hover:no-underline">
                    {{ __('portada.banner.agregar_primero') }}
                </a>
            @endauth
        </div>
    </section>
@else
    <section id="inicio"
             role="region"
             aria-roledescription="{{ __('portada.banner.carrusel') }}"
             aria-label="{{ __('portada.banner.region') }}"
             x-data="huvCarousel({ count: {{ $count }}, autoplay: true, seconds: {{ $rotation }}, textos: {{ Illuminate\Support\Js::from([
             'detener' => __('portada.banner.detener'),
             'reproducir' => __('portada.banner.reproducir'),
             'posicion' => __('portada.banner.posicion', ['posicion' => ':posicion', 'total' => ':total']),
         ]) }} })"
             @mouseenter="hovering = true"
             @mouseleave="hovering = false"
             @focusin="focused = true"
             @focusout="focused = false"
             @keydown="onKeydown($event)"
             @touchstart.passive="onTouchStart($event)"
             @touchend.passive="onTouchEnd($event)"
             class="relative overflow-hidden bg-hero-shell">

        <x-edit-chip section="banner" :label="__('portada.chip.banner')" :url="route('admin.banners.index')" floating />

        <div class="relative w-full overflow-hidden">
            <div id="huv-hero-track" class="flex" style="width: {{ $count * 100 }}%" :style="trackStyle">

                @foreach ($banners as $index => $banner)
                    <div class="relative shrink-0 grow-0 bg-slide-shell"
                         style="width: {{ $slideWidth }}%"
                         role="group"
                         aria-roledescription="{{ __('portada.banner.diapositiva') }}"
                         aria-label="{{ __('portada.banner.posicion', ['posicion' => $index + 1, 'total' => $count]) }}"
                         :aria-hidden="! isActive({{ $index }})">

                        @php
                            // Sin enlace no debe haber ancla: un enlace que no
                            // lleva a ninguna parte confunde al navegar con teclado.
                            $tag = $banner->link ? 'a' : 'div';
                        @endphp

                        <{{ $tag }}
                            @if ($banner->link)
                                href="{{ $banner->link }}"
                                {{-- Si lleva fuera del portal, se pide confirmación antes de salir. --}}
                                data-huv-confirm-exit
                                :tabindex="isActive({{ $index }}) ? 0 : -1"
                            @endif
                            class="relative block min-h-[440px] lg:min-h-[max(320px,25.81vw)]"
                            style="aspect-ratio: {{ Banner::IMAGE_WIDTH }} / {{ Banner::IMAGE_HEIGHT }}">

                            <img src="{{ $banner->imageUrl() }}"
                                 alt="{{ $banner->alt_text }}"
                                 width="{{ Banner::IMAGE_WIDTH }}" height="{{ Banner::IMAGE_HEIGHT }}"
                                 @if ($index === 0) loading="eager" fetchpriority="high" @else loading="lazy" fetchpriority="low" @endif
                                 decoding="async"
                                 class="absolute inset-0 size-full object-cover">

                            @if ($banner->filterStyle())
                                <span class="absolute inset-0" aria-hidden="true"
                                      style="{{ $banner->filterStyle() }}"></span>
                            @endif

                            @if ($banner->hasOverlayText())
                                <div class="relative flex size-full flex-col justify-center gap-3 px-5 py-8
                                            md:px-16 lg:px-[165px]
                                            {{ $banner->alignment === 'center' ? 'items-center text-center' : 'items-start text-left' }}">
                                    @if ($banner->title)
                                        <x-texto-del-portal tag="p" class="m-0 text-[1.75rem] leading-[1.12] text-balance lg:text-40"
                                           style="{{ $banner->textStyle('title') }}">{{ $banner->title }}</x-texto-del-portal>
                                    @endif
                                    @if ($banner->subtitle)
                                        <x-texto-del-portal tag="p" class="m-0 max-w-[46ch] text-15 leading-[1.4] text-pretty lg:text-22"
                                           style="{{ $banner->textStyle('subtitle') }}">{{ $banner->subtitle }}</x-texto-del-portal>
                                    @endif
                                </div>
                            @endif
                        </{{ $tag }}>
                    </div>
                @endforeach
            </div>

            @if ($count > 1)
                {{-- Controles laterales --}}
                <button type="button" @click="prev()" aria-label="{{ __('portada.banner.anterior') }}"
                        aria-controls="huv-hero-track" data-huv-control
                        class="absolute top-1/2 left-2 z-20 flex size-11 -translate-y-1/2 items-center justify-center
                               rounded-full border-0 bg-control text-on-control transition-colors
                               hover:bg-control-hover lg:left-[92px] lg:size-[54px]">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 5-7 7 7 7" />
                    </svg>
                </button>

                <button type="button" @click="next()" aria-label="{{ __('portada.banner.siguiente') }}"
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
                            :aria-label="playing ? '{{ __('portada.banner.detener_automatico') }}' : '{{ __('portada.banner.reproducir_automatico') }}'"
                            class="flex items-center justify-self-start gap-[9px] border-0 bg-transparent text-13-5 font-semibold text-on-control">
                        <svg x-show="playing" class="size-[11px]" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                            <rect x="2" y="1" width="3" height="10" rx="0.5" />
                            <rect x="7" y="1" width="3" height="10" rx="0.5" />
                        </svg>
                        <svg x-show="! playing" x-cloak class="size-[11px]" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                            <polygon points="2,1 11,6 2,11" />
                        </svg>
                        <span x-text="playLabel" class="hidden sm:inline">{{ __('portada.banner.detener') }}</span>
                    </button>

                    <div role="group" aria-label="{{ __('portada.banner.seleccionar') }}" class="flex justify-self-center gap-3">
                        @foreach ($banners as $index => $banner)
                            <button type="button" @click="go({{ $index }})"
                                    aria-label="{{ __('portada.banner.ir_a', ['posicion' => $index + 1, 'total' => $count]) }}"
                                    :aria-current="isActive({{ $index }}) ? 'true' : 'false'"
                                    :class="isActive({{ $index }}) ? 'bg-dot-active' : 'bg-transparent'"
                                    class="size-[13px] rounded-full border-2 border-on-control p-0 transition-colors"></button>
                        @endforeach
                    </div>

                    <span aria-hidden="true" class="justify-self-end text-12 text-on-control/90"
                          x-text="statusLabel"></span>
                </div>
            @endif

            <span class="sr-only" :aria-live="playing ? 'off' : 'polite'" x-text="statusLabel"></span>
        </div>
    </section>
@endif
