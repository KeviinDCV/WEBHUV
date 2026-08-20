@props(['item', 'tone' => 'default'])

@php
    // «Programado» es azul sobre blanco; sobre la tarjeta azul de una
    // convocatoria desaparece. Se oscurece para que siga leyéndose.
    $sobreColor = $tone === 'accent';
@endphp

@auth
    {{-- Lo inactivo y lo oculto sigue apareciendo para quien administra
         —marcado como tal— porque si no, ocultar algo lo volvería inalcanzable:
         no habría desde dónde volver a mostrarlo. --}}
    @if (! $item->is_active || $item->is_hidden || $item->isScheduled() || $item->hasExpired())
        <p x-show="$store.huvUi.editMode" x-cloak class="m-0 flex flex-wrap gap-2">
            @if ($item->hasExpired())
                <span class="rounded-[2px] bg-danger px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-danger uppercase">
                    {{ __('componentes.distintivos.caducado', ['fecha' => $item->expires_at->translatedFormat('j M Y')]) }}
                </span>
            @endif

            @if ($item->isScheduled())
                <span @class([
                    'rounded-[2px] px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] uppercase',
                    'bg-navy-deep text-on-brand' => $sobreColor,
                    'bg-azure text-on-accent' => ! $sobreColor,
                ])>
                    {{ __('componentes.distintivos.programado', ['fecha' => $item->published_at->translatedFormat('j M Y, H:i')]) }}
                </span>
            @endif

            @unless ($item->is_active)
                <span class="rounded-[2px] bg-danger px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] text-on-danger uppercase">
                    {{ __('componentes.distintivos.inactivo') }}
                </span>
            @endunless

            @if ($item->is_hidden)
                <span class="rounded-[2px] bg-warning px-2 py-[2px] text-10-5 font-bold tracking-[0.06em] uppercase"
                      style="color: #000">
                    {{ __('componentes.distintivos.oculto_listado') }}
                </span>
            @endif
        </p>
    @endif
@endauth
