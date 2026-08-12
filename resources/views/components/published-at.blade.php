@props(['value'])

@php
    use Illuminate\Support\Carbon;

    $date = $value instanceof Carbon ? $value : Carbon::parse($value);

    /*
     | Siempre en relativo, y con la misma escala que el portal.
     |
     | Dos cosas que hay que reproducir a mano porque Carbon no las hace:
     |
     | 1. REDONDEA en vez de truncar, en TODAS las unidades. Un documento de
     |    2023-08-30 visto el 12 de agosto de 2026 lleva 2,95 años: el portal
     |    escribe «hace 3 años» y Carbon «hace 2 años». Lo mismo en días —5,65
     |    días son «hace 6 días» allí y «hace 5» aquí—.
     |
     | 2. Los saltos de unidad no están en el número redondo, sino donde los
     |    pone la biblioteca que usa el portal: 22 horas ya son «un día», 26
     |    días son «un mes» y 320 días son «un año». Con los saltos redondos,
     |    veinte días saldrían como «un mes» y el listado se desordenaría a la
     |    vista.
     |
     | La fecha exacta no se pierde: va en el atributo `datetime`, que es el que
     | leen buscadores y lectores de pantalla, y en el `title` al pasar el ratón.
    */
    $seconds = $date->diffInSeconds(now());
    $minutes = $seconds / 60;
    $hours = $minutes / 60;
    $days = $hours / 24;

    // El mes medio del calendario gregoriano, que es con el que cuenta el
    // portal: con 30 justos, once meses y medio se irían a «12 meses» en vez
    // de a «un año».
    $months = $days / 30.436875;

    $label = match (true) {
        $seconds < 45 => 'unos segundos',
        $seconds < 90 => 'un minuto',
        $minutes < 45 => round($minutes).' minutos',
        $minutes < 90 => 'una hora',
        $hours < 22 => round($hours).' horas',
        $hours < 36 => 'un día',
        $days < 26 => round($days).' días',
        $days < 46 => 'un mes',
        $days < 320 => round($months).' meses',
        $days < 548 => 'un año',
        default => round($months / 12).' años',
    };
@endphp

<time datetime="{{ $date->toIso8601String() }}" title="{{ $date->translatedFormat('l j \d\e F \d\e Y, H:i') }}"
      {{ $attributes }}>Hace {{ $label }}</time>
