@props(['document'])

@auth
    {{-- Lo inactivo y lo oculto sigue apareciendo para quien administra
         —marcado como tal— porque si no, ocultar un documento lo volvería
         inalcanzable: no habría desde dónde volver a mostrarlo. --}}
    @if (! $document->is_active || $document->is_hidden || $document->isScheduled())
        <p x-show="$store.huvUi.editMode" x-cloak class="m-0 flex flex-wrap gap-2">
            @if ($document->isScheduled())
                <span class="rounded-[2px] bg-azure px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-accent uppercase">
                    Programado · {{ $document->published_at->translatedFormat('j M Y, H:i') }}
                </span>
            @endif

            @unless ($document->is_active)
                <span class="rounded-[2px] bg-[#8c1d18] px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-white uppercase">
                    Inactivo
                </span>
            @endunless

            @if ($document->is_hidden)
                <span class="rounded-[2px] bg-warning px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] uppercase"
                      style="color: #000">
                    Oculto en el listado
                </span>
            @endif
        </p>
    @endif
@endauth
