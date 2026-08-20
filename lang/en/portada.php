<?php

return [

    // Rótulo que cada sección pasa a <x-edit-chip>; se lee como «Editar ...».
    'chip' => [
        'banner' => 'the banner',
        'noticias' => 'the news block',
        'eventos' => 'the events block',
        'boletines' => 'bulletins',
        'entidades' => 'organizations',
    ],

    // sections/hero.blade.php
    'banner' => [
        'region' => 'Main banner',
        'carrusel' => 'carousel',
        'diapositiva' => 'slide',
        'marcador' => 'Main banner (:ancho × :alto)',
        'agregar_primero' => 'Add the first banner',
        'posicion' => ':posicion of :total',
        'anterior' => 'Previous banner',
        'siguiente' => 'Next banner',
        'detener_automatico' => 'Stop the banner from playing automatically',
        'reproducir_automatico' => 'Play the banner automatically',
        'detener' => 'Stop',
        'seleccionar' => 'Select banner',
        'ir_a' => 'Go to banner :posicion of :total',
    ],

    // sections/news.blade.php
    'noticias' => [
        'titulo' => 'Hospital news',
        'vacio' => 'No news has been published yet.',
        'agregar' => 'Add content',
        'foto_principal' => 'Main photo (1200×768)',
        'miniatura' => 'Thumbnail',
        'ver_todas' => 'View all news',
    ],

    // sections/quick-links.blade.php
    'atajos' => [
        'titulo' => 'Shortcuts to procedures and service channels',
        'vacio' => 'No shortcut bars have been set up.',
        'sin_publicar' => 'Unpublished · :faltan missing',
        'editar' => 'Edit',
        'editar_barra' => ' the “:barra” bar',
    ],

    // sections/content-feed.blade.php
    'contenidos' => [
        'titulo' => 'All published content',
        'orden' => 'Sort by:',
        'filtro_fecha' => [
            'rotulo' => 'Filter by date',
            'todas' => 'Filter by date',
            'semana' => 'Last week',
            'mes' => 'Last month',
            'anio' => 'Last year',
        ],
        'filtro_categoria' => [
            'rotulo' => 'Filter by content type',
            'todas' => 'All content',
        ],
        'nuevo' => 'New content',
        'ocultar' => 'Hide',
        'vista' => [
            'grupo' => 'Listing layout',
            'cuadricula' => 'View as a grid',
            'lista' => 'View as a list',
        ],
        'vacio' => 'No content has been published yet.',
        'sin_resultados' => 'No content matches the selected filters.',
        'quitar_filtros' => 'Clear the filters',
        'cargar_mas' => 'Load more content',
        'mostrando' => 'Showing :visibles of :total items',
    ],

    // sections/events.blade.php
    'eventos' => [
        'nuevo' => 'New event',
        'vista' => 'View the calendar by',
        'semana' => 'Week',
        'mes' => 'Month',
        'aplicar' => 'Apply',
        'periodo_anterior' => 'Previous period',
        'periodo_siguiente' => 'Next period',
        'hoy' => 'Today',
        'inactivo' => 'Inactive',
        'vacio' => 'There are no events scheduled for this period.',
        'aviso_categorias' => 'The block only shows events from the categories selected in its settings.',
    ],

    // sections/bulletins.blade.php
    'boletines' => [
        'ver_todos' => 'View all bulletins',
    ],

    // sections/partners.blade.php
    'entidades' => [
        'anteriores' => 'View previous organizations',
        'siguientes' => 'View more organizations',
    ],

    // sections/entity.blade.php
    'entidad' => [
        'foto' => 'Facade of the Hospital Universitario del Valle «Evaristo García» E.S.E.',
    ],

    // sections/contact-strip.blade.php
    'lineas' => [
        'titulo' => 'Contact lines',
    ],

    // Lo comparten los accesos directos y el mosaico de accesos rápidos.
    'nueva_pestana' => '(opens in a new tab)',
];
