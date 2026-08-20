<?php

return [

    // Rótulo que cada sección pasa a <x-edit-chip>; se lee como «Editar ...».
    'chip' => [
        'banner' => 'el banner',
        'noticias' => 'el bloque de noticias',
        'eventos' => 'el bloque de eventos',
        'boletines' => 'boletines',
        'entidades' => 'entidades',
    ],

    // sections/hero.blade.php
    'banner' => [
        'region' => 'Banner principal',
        'carrusel' => 'carrusel',
        'diapositiva' => 'diapositiva',
        'marcador' => 'Banner principal (:ancho × :alto)',
        'agregar_primero' => 'Agregar el primer banner',
        'posicion' => ':posicion de :total',
        'anterior' => 'Banner anterior',
        'siguiente' => 'Banner siguiente',
        'detener_automatico' => 'Detener la reproducción automática del banner',
        'reproducir_automatico' => 'Reproducir el banner automáticamente',
        'detener' => 'Detener',
        'seleccionar' => 'Seleccionar banner',
        'ir_a' => 'Ir al banner :posicion de :total',
    ],

    // sections/news.blade.php
    'noticias' => [
        'titulo' => 'Noticias del hospital',
        'vacio' => 'Todavía no hay noticias publicadas.',
        'agregar' => 'Agregar contenido',
        'foto_principal' => 'Foto principal (1200×768)',
        'miniatura' => 'Miniatura',
        'ver_todas' => 'Ver todas las noticias',
    ],

    // sections/quick-links.blade.php
    'atajos' => [
        'titulo' => 'Accesos directos a trámites y canales de atención',
        'vacio' => 'No hay barras de accesos directos configuradas.',
        'sin_publicar' => 'Sin publicar · faltan :faltan',
        'editar' => 'Editar',
        'editar_barra' => ' la barra «:barra»',
    ],

    // sections/content-feed.blade.php
    'contenidos' => [
        'titulo' => 'Todos los contenidos publicados',
        'orden' => 'Ordenar por:',
        'filtro_fecha' => [
            'rotulo' => 'Filtrar por fecha',
            'todas' => 'Filtrar por fecha',
            'semana' => 'Última semana',
            'mes' => 'Último mes',
            'anio' => 'Último año',
        ],
        'filtro_categoria' => [
            'rotulo' => 'Filtrar por tipo de contenido',
            'todas' => 'Todos los contenidos',
        ],
        'nuevo' => 'Nuevo contenido',
        'ocultar' => 'Ocultar',
        'vista' => [
            'grupo' => 'Forma de ver el listado',
            'cuadricula' => 'Ver en cuadrícula',
            'lista' => 'Ver en lista',
        ],
        'vacio' => 'Todavía no hay contenidos publicados.',
        'sin_resultados' => 'No hay contenidos que coincidan con los filtros seleccionados.',
        'quitar_filtros' => 'Quitar los filtros',
        'cargar_mas' => 'Cargar más contenidos',
        'mostrando' => 'Mostrando :visibles de :total contenidos',
    ],

    // sections/events.blade.php
    'eventos' => [
        'nuevo' => 'Nuevo evento',
        'vista' => 'Ver la agenda por',
        'semana' => 'Semana',
        'mes' => 'Mes',
        'aplicar' => 'Aplicar',
        'periodo_anterior' => 'Periodo anterior',
        'periodo_siguiente' => 'Periodo siguiente',
        'hoy' => 'Hoy',
        'inactivo' => 'Inactivo',
        'vacio' => 'No hay eventos programados en este periodo.',
        'aviso_categorias' => 'El bloque solo muestra los eventos de las categorías elegidas en su configuración.',
    ],

    // sections/bulletins.blade.php
    'boletines' => [
        'ver_todos' => 'Ver todos los boletines',
    ],

    // sections/partners.blade.php
    'entidades' => [
        'anteriores' => 'Ver entidades anteriores',
        'siguientes' => 'Ver más entidades',
    ],

    // sections/entity.blade.php
    'entidad' => [
        'foto' => 'Fachada del Hospital Universitario del Valle «Evaristo García» E.S.E.',
    ],

    // sections/contact-strip.blade.php
    'lineas' => [
        'titulo' => 'Líneas de atención',
    ],

    // Lo comparten los accesos directos y el mosaico de accesos rápidos.
    'nueva_pestana' => '(se abre en una pestaña nueva)',
];
