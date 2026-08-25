<?php

return [

    'titulo' => 'Estadísticas de uso',
    'subtitulo' => 'Cuánta gente entra al portal.',

    // ---------------- Periodo ----------------

    'periodo' => [
        'etiqueta' => 'Periodo',
        7 => 'Última semana',
        30 => 'Último mes',
        90 => 'Últimos tres meses',
        365 => 'Último año',
        'rango' => 'Del :desde al :hasta',
    ],

    // ---------------- Cifras ----------------

    'promedio' => [
        'titulo' => 'Visitas al día',
        'pie' => 'Promedio de los :dias días del periodo, contando también los días sin nadie.',
    ],

    'total' => [
        'titulo' => 'Visitas del periodo',
        'pie' => 'Suma de las visitas de cada día.',
    ],

    'paginas' => [
        'titulo' => 'Páginas vistas',
        'pie' => 'Una por cada página abierta; :media por visita.',
    ],

    'cumbre' => [
        'titulo' => 'Día de más',
        'pie' => ':fecha, con :visitas visitas.',
    ],

    // ---------------- Gráfica ----------------

    'grafica' => [
        'titulo' => 'Visitas por día',
        'dia' => ':fecha: :visitas visitas, :paginas páginas',
        'tabla' => 'Los mismos datos, en tabla',
        'columna_fecha' => 'Día',
        'columna_visitas' => 'Visitas',
        'columna_paginas' => 'Páginas vistas',
    ],

    // ---------------- Páginas más vistas ----------------

    'top' => [
        'titulo' => 'Las páginas más vistas',
        'columna_pagina' => 'Página',
        'columna_paginas' => 'Veces abierta',
        'columna_visitantes' => 'Visitas distintas',
        'portada' => 'Portada',
    ],

    // ---------------- Avisos ----------------

    'vacio' => [
        'titulo' => 'Todavía no hay nada que enseñar',
        'texto' => 'El recuento acaba de ponerse en marcha. En cuanto entre gente al portal, esta '
            .'pantalla empezará a llenarse; con un par de semanas ya se ve una tendencia.',
    ],

    'desde_cuando' => 'Se cuenta desde el :fecha.',

    // El aviso más importante de la pantalla: qué significan de verdad estas
    // cifras. Sin esto, «visitas» se lee como «personas» y no es lo mismo.
    'letra_pequena' => [
        'titulo' => 'Qué cuentan exactamente estas cifras',
        'visita' => 'Una «visita» es un navegador en un día. Si la misma persona entra desde el '
            .'teléfono y desde el computador, son dos; y si vuelve mañana, cuenta otra vez.',
        'cookie' => 'A quien no acepte la cookie de sesión se le cuenta una visita nueva en cada '
            .'página, así que el número real de personas es algo menor que el que sale aquí. Mire la '
            .'tendencia antes que el número exacto.',
        'excluidos' => 'No se cuentan los rastreadores conocidos, ni las páginas de administración, ni '
            .'a nadie con la sesión iniciada: quien edita el portal no es público.',
        'privacidad' => 'No se guarda la dirección IP, ni el navegador, ni ninguna cookie nueva. El '
            .'identificador de cada visita cambia cada medianoche, así que no se puede seguir a nadie '
            .'de un día para otro.',
    ],

];
