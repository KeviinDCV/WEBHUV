@php $institution = config('huv.institution'); @endphp

<header class="border-b border-line-soft bg-card">
    <x-container class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4 py-4">

        {{-- El destino del botón «Volver arriba»: al subir, el foco aterriza aquí. --}}
        <a href="{{ url('/') }}" id="huv-inicio-pagina" class="block shrink-0"
           aria-label="{{ __('cabecera.ir_al_inicio', ['entidad' => $institution['name']]) }}"{!! App\Support\PortalLang::attribute() !!}>
            {{-- width/height deben respetar la proporción real del archivo
                 (620×175). Declararla mal deforma el logotipo mientras carga y
                 provoca un salto de maquetación. --}}
            <img src="{{ asset('img/logo-huv.png') }}"
                 alt="{{ $institution['name'] }}"{!! App\Support\PortalLang::attribute() !!}
                 width="620" height="175"
                 fetchpriority="high"
                 class="block h-[52px] w-auto sm:h-[66px] lg:h-[76px]">
        </a>

        <div class="flex w-full max-w-[520px] flex-1 flex-col items-stretch gap-[10px] sm:items-end">

            <div class="flex items-center gap-[14px] text-12-5 font-semibold">
                {{--
                    Interruptor de idioma.

                    Son enlaces a la misma página con «?idioma=», no botones con
                    JavaScript: así funciona sin scripts, se puede abrir en otra
                    pestaña y una dirección concreta se puede compartir ya en el
                    idioma que se quiera.

                    Cada uno lleva `lang` y `hreflang` con su propio idioma: el
                    rótulo «EN» está en inglés dentro de una página en español, y
                    sin eso un lector de pantalla lo pronuncia como si fuera
                    español (WCAG 3.1.2).
                --}}
                <div role="group" aria-label="{{ __('cabecera.idioma') }}"
                     class="flex items-center overflow-hidden rounded-[3px] border border-stroke">
                    @foreach (App\Http\Middleware\SetLocale::SUPPORTED as $idioma)
                        @php $actual = app()->getLocale() === $idioma; @endphp

                        <a href="{{ request()->fullUrlWithQuery(['idioma' => $idioma]) }}"
                           lang="{{ $idioma }}" hreflang="{{ $idioma }}"
                           @if ($actual) aria-current="true" @endif
                           @class([
                               'px-[10px] py-1 text-12 font-bold tracking-[0.04em] no-underline hover:no-underline',
                               'bg-navy text-on-brand hover:text-on-brand' => $actual,
                               'bg-card text-link hover:bg-tint' => ! $actual,
                           ])>
                            {{ strtoupper($idioma) }}
                            <span class="sr-only">{{ __('cabecera.ver_en.'.$idioma) }}</span>
                        </a>
                    @endforeach
                </div>
                <span class="text-divider" aria-hidden="true">·</span>
                @auth
                    <span class="text-muted">{{ auth()->user()->name }}</span>
                @else
                    <a href="{{ route('login') }}" class="text-heading">{{ __('cabecera.iniciar_sesion') }}</a>
                @endauth
            </div>

            {{-- TODO: apuntar a la ruta del buscador cuando exista el backend de búsqueda. --}}
            <form action="{{ url('/') }}" method="GET" role="search"
                  class="flex w-full items-center rounded-[26px] border border-stroke bg-card py-1 pr-1 pl-[18px]">
                <label for="huv-buscar" class="sr-only">{{ __('cabecera.buscar.etiqueta') }}</label>
                <input id="huv-buscar" type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('cabecera.buscar.etiqueta') }}" autocomplete="off"
                       class="min-w-0 flex-1 border-0 bg-transparent py-[6px] text-14 text-ink outline-0 placeholder:text-faint">
                <button type="submit" aria-label="{{ __('cabecera.buscar.boton') }}"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full border-0 bg-azure text-on-accent transition-colors hover:bg-azure-dark">
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.6-3.6" />
                    </svg>
                </button>
            </form>
        </div>
    </x-container>
</header>
