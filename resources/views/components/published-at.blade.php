@props(['value', 'relativeDays' => 7])

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $date = $value instanceof Carbon ? $value : Carbon::parse($value);

    /*
     | Igual que el portal institucional: lo reciente se expresa en relativo
     | («Hace 6 horas») porque comunica mejor la actualidad, y a partir de una
     | semana se pasa a la fecha exacta, que es la que sirve para citar.
    */
    // Se comparan días de calendario: con la diferencia exacta, una fecha de
    // «hace 7 días» arrastra unos microsegundos de más y caería en el formato
    // absoluto por un pelo.
    $elapsedDays = $date->copy()->startOfDay()->diffInDays(now()->startOfDay());

    // `skip => week`: Carbon convertiría «7 días» en «1 semana», y el portal
    // institucional cuenta en días hasta que pasa al formato absoluto.
    $label = $elapsedDays <= $relativeDays
        ? Str::ucfirst($date->diffForHumans(['skip' => ['week']]))
        : $date->translatedFormat('j F Y').', '.Str::lower($date->format('g:i a'));
@endphp

<time datetime="{{ $date->toIso8601String() }}" title="{{ $date->translatedFormat('l j \d\e F \d\e Y, H:i') }}"
      {{ $attributes }}>{{ $label }}</time>
