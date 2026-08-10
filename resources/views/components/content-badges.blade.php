@props(['content'])

@auth
    {{-- Los contenidos inactivos u ocultos siguen apareciendo en la portada
         para quien administra —marcados como tales— porque si no, ocultar uno
         lo volvería inalcanzable: no habría desde dónde volver a mostrarlo. --}}
    @if (! $content->is_active || $content->is_hidden || $content->isScheduled() || $content->hasExpired())
        <p x-show="$store.huvUi.editMode" x-cloak class="m-0 flex flex-wrap gap-2">
            @if ($content->hasExpired())
                <span class="rounded-[2px] bg-danger px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-danger uppercase">
                    Caducado · {{ $content->expires_at->translatedFormat('j M Y') }}
                </span>
            @endif

            @if ($content->isScheduled())
                <span class="rounded-[2px] bg-azure px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-accent uppercase">
                    Programado · {{ $content->published_at->translatedFormat('j M Y, H:i') }}
                </span>
            @endif

            @unless ($content->is_active)
                <span class="rounded-[2px] bg-danger px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-danger uppercase">
                    Inactivo
                </span>
            @endunless

            @if ($content->is_hidden)
                <span class="rounded-[2px] bg-warning px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-warning uppercase"
                      style="color: #000">
                    Oculto en la portada
                </span>
            @endif
        </p>
    @endif
@endauth
