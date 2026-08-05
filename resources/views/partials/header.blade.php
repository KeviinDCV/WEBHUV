@php $institution = config('huv.institution'); @endphp

<header class="border-b border-line-soft bg-card">
    <x-container class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4 py-4">

        {{-- El destino del botón «Volver arriba»: al subir, el foco aterriza aquí. --}}
        <a href="{{ url('/') }}" id="huv-inicio-pagina" class="block shrink-0"
           aria-label="{{ $institution['name'] }} — ir al inicio">
            {{-- width/height deben respetar la proporción real del archivo
                 (620×175). Declararla mal deforma el logotipo mientras carga y
                 provoca un salto de maquetación. --}}
            <img src="{{ asset('img/logo-huv.png') }}"
                 alt="{{ $institution['name'] }}"
                 width="620" height="175"
                 fetchpriority="high"
                 class="block h-[52px] w-auto sm:h-[66px] lg:h-[76px]">
        </a>

        <div class="flex w-full max-w-[520px] flex-1 flex-col items-stretch gap-[10px] sm:items-end">

            <div class="flex items-center gap-[14px] text-12-5 font-semibold">
                <div role="group" aria-label="Idioma del sitio"
                     class="flex items-center overflow-hidden rounded-[3px] border border-stroke">
                    <span aria-current="true"
                          class="bg-navy px-[10px] py-1 text-12 font-bold tracking-[0.04em] text-on-brand">ES</span>
                    <span aria-disabled="true" title="Versión en inglés — próximamente"
                          class="cursor-not-allowed bg-card px-[10px] py-1 text-12 font-bold tracking-[0.04em] text-disabled">EN</span>
                </div>
                <span class="text-divider" aria-hidden="true">·</span>
                @auth
                    <span class="text-muted">{{ auth()->user()->name }}</span>
                @else
                    <a href="{{ route('login') }}" class="text-heading">Inicia sesión</a>
                @endauth
            </div>

            {{-- TODO: apuntar a la ruta del buscador cuando exista el backend de búsqueda. --}}
            <form action="{{ url('/') }}" method="GET" role="search"
                  class="flex w-full items-center rounded-[26px] border border-stroke bg-card py-1 pr-1 pl-[18px]">
                <label for="huv-buscar" class="sr-only">Buscar en la entidad</label>
                <input id="huv-buscar" type="search" name="q" value="{{ request('q') }}"
                       placeholder="Buscar en la entidad" autocomplete="off"
                       class="min-w-0 flex-1 border-0 bg-transparent py-[6px] text-14 text-ink outline-0 placeholder:text-faint">
                <button type="submit" aria-label="Buscar"
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
