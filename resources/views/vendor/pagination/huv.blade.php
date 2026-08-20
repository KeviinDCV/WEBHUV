@if ($paginator->hasPages())
    {{--
        Paginador del portal: números, puntos suspensivos donde se salta y
        «Siguiente» al final.

        Propio y no el de Laravel por dos razones: el suyo rotula los botones
        con cadenas en inglés que esta aplicación no traduce —se veían tal cual,
        «pagination.previous»— y su maquetación no es la del portal.
    --}}
    <nav role="navigation" aria-label="{{ __('componentes.paginacion.etiqueta') }}"
         class="flex flex-wrap items-center justify-center gap-2">

        @if ($paginator->onFirstPage())
            <span class="px-3 py-[6px] text-13-5 text-faint" aria-hidden="true">{{ __('componentes.paginacion.anterior') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="px-3 py-[6px] text-13-5 font-semibold text-link underline underline-offset-4
                      hover:text-heading hover:no-underline">
                {{ __('componentes.paginacion.anterior') }}
            </a>
        @endif

        @foreach ($elements as $element)
            {{-- Un tramo saltado llega como una cadena suelta. --}}
            @if (is_string($element))
                <span class="px-2 py-[6px] text-13-5 text-muted" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="flex min-w-8 items-center justify-center rounded-[3px] bg-azure px-2 py-[6px]
                                     text-13-5 font-bold text-on-accent">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="flex min-w-8 items-center justify-center rounded-[3px] px-2 py-[6px]
                                  text-13-5 font-semibold text-link no-underline hover:bg-tint hover:no-underline">
                            {{ $page }}
                            <span class="sr-only">{{ __('componentes.paginacion.pagina', ['numero' => $page]) }}</span>
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="px-3 py-[6px] text-13-5 font-semibold text-link underline underline-offset-4
                      hover:text-heading hover:no-underline">
                {{ __('componentes.paginacion.siguiente') }}
            </a>
        @else
            <span class="px-3 py-[6px] text-13-5 text-faint" aria-hidden="true">{{ __('componentes.paginacion.siguiente') }}</span>
        @endif
    </nav>
@endif
