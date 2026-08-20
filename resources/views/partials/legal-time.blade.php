@php
    /*
     | Hora legal de la República de Colombia.
     |
     | El servidor entrega la marca de tiempo y el navegador solo la hace
     | avanzar: así un reloj mal ajustado en el equipo del visitante no altera
     | lo que se muestra. Ver la nota sobre sincronización NTP con el INM en
     | config/huv.php → footer.legal_time.
    */
    $now = now();
    $serverNow = $now->getTimestampMs();
@endphp

<div x-data="huvClock({{ $serverNow }}, '{{ $legalTime['timezone'] }}')"
     class="flex w-full items-stretch overflow-hidden rounded-[3px] bg-navy text-on-brand">

    <div class="flex shrink-0 items-center border-r border-white/20 px-3 py-2">
        <img src="{{ asset('img/inm-hora-legal.png') }}"
             alt="{{ __('estructura.hora_legal.inm') }}"{!! App\Support\PortalLang::attribute() !!}
             width="200" height="99" loading="lazy" decoding="async"
             class="block h-[38px] w-auto">
    </div>

    <div class="flex flex-1 items-center justify-between gap-3 px-4 py-2">
        {{-- Se renderiza ya con la hora del servidor: sin JavaScript el dato
             sigue siendo correcto, solo deja de avanzar. `tabular-nums` evita
             que la cifra baile al cambiar cada segundo. --}}
        <time x-text="display"
              datetime="{{ $now->toIso8601String() }}"
              :datetime="isoValue"
              class="font-display text-24 font-bold tracking-[0.02em] tabular-nums">{{ $now->format('H:i:s') }}</time>

        <span class="text-10-5 leading-[1.25] font-semibold tracking-[0.06em] uppercase">
            {{ __('estructura.hora_legal.rotulo') }}<br>{{ __('estructura.hora_legal.pais') }}
        </span>
    </div>

    {{-- Un reloj que se actualiza cada segundo no debe anunciarse: se expone
         una sola etiqueta estática a los lectores de pantalla. --}}
    <span class="sr-only">
        {!! __('estructura.hora_legal.descripcion', ['entidad' => App\Support\PortalLang::wrap(__('estructura.hora_legal.inm'))]) !!}
    </span>
</div>
