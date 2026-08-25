<?php

return [

    'titulo' => 'Usage statistics',
    'subtitulo' => 'How many people come to the portal.',

    'periodo' => [
        'etiqueta' => 'Period',
        7 => 'Last week',
        30 => 'Last month',
        90 => 'Last three months',
        365 => 'Last year',
        'rango' => 'From :desde to :hasta',
    ],

    'promedio' => [
        'titulo' => 'Visits per day',
        'pie' => 'Average over the :dias days of the period, counting days with nobody as well.',
    ],

    'total' => [
        'titulo' => 'Visits in the period',
        'pie' => 'The sum of each day’s visits.',
    ],

    'paginas' => [
        'titulo' => 'Page views',
        'pie' => 'One for every page opened; :media per visit.',
    ],

    'cumbre' => [
        'titulo' => 'Busiest day',
        'pie' => ':fecha, with :visitas visits.',
    ],

    'grafica' => [
        'titulo' => 'Visits per day',
        'dia' => ':fecha: :visitas visits, :paginas pages',
        'tabla' => 'The same figures, as a table',
        'columna_fecha' => 'Day',
        'columna_visitas' => 'Visits',
        'columna_paginas' => 'Page views',
    ],

    'top' => [
        'titulo' => 'Most visited pages',
        'columna_pagina' => 'Page',
        'columna_paginas' => 'Times opened',
        'columna_visitantes' => 'Distinct visits',
        'portada' => 'Home page',
    ],

    'vacio' => [
        'titulo' => 'Nothing to show yet',
        'texto' => 'Counting has only just started. As soon as people come to the portal this screen '
            .'will begin to fill up; a couple of weeks is enough to see a trend.',
    ],

    'desde_cuando' => 'Counting since :fecha.',

    'letra_pequena' => [
        'titulo' => 'What these figures actually count',
        'visita' => 'A “visit” is one browser on one day. If the same person comes from their phone and '
            .'from their computer, that is two; and if they return tomorrow, it counts again.',
        'cookie' => 'Anyone who does not accept the session cookie is counted as a new visit on every '
            .'page, so the real number of people is somewhat lower than the one shown here. Read the '
            .'trend before the exact figure.',
        'excluidos' => 'Known crawlers are not counted, nor the administration pages, nor anyone who is '
            .'signed in: the people who edit the portal are not its public.',
        'privacidad' => 'No IP address is stored, no browser details, no new cookie. Each visit’s '
            .'identifier changes at midnight, so nobody can be followed from one day to the next.',
    ],

];
