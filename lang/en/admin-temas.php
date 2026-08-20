<?php

return [

    'tipo' => [
        'etiqueta' => 'Content type',
    ],

    'enlace' => [
        'etiqueta' => 'Link',
        'pista' => '(Include https:// or http://)',
        'marcador' => 'Link',
        'ayuda_documento' => 'For documents hosted on another site. Not needed if you attach a file.',
        'ayuda_enlace' => 'Where this content leads. It is what defines it, so it is required.',
    ],

    'titulo' => [
        'etiqueta' => 'Title',
        'limite' => '(150 characters)',
        'marcador' => 'Title',
        'restantes' => 'Characters remaining: :n',
    ],

    'expedicion' => [
        'etiqueta' => 'Date of issue',
        'ayuda' => 'The date of the document itself, not the day it is uploaded.',
    ],

    'evento' => [
        'organizador' => 'Organizer',
        'lugar' => 'Location',
        'limite' => '(70 characters)',
        'fecha' => 'Start date',
        'hora' => 'Start time',
    ],

    'convocatoria' => [
        'apertura' => 'Opening date and time',
        'cierre' => 'Closing date and time',
        'ayuda' => 'A closed call can still be consulted: the closing date reports on the process, '
            .'it does not remove the call from the listing.',
    ],

    'caducidad' => [
        'sin_fecha' => 'No end date for display',
        'etiqueta' => 'End date for display',
        'ayuda' => 'After that date it is no longer shown, without having to remove it by hand.',
    ],

    'descripcion' => [
        'etiqueta' => 'Description',
        'editor' => 'Content description',
        'sin_js' => 'The rich text editor requires JavaScript.',
    ],

    'archivo' => [
        'actual' => 'Main file:',
        'etiqueta' => 'Main file',
        'reemplazar' => 'Replace the main file',
        'limite' => 'Maximum size: 30 MB · pdf, doc, xls, ppt, csv, txt or zip',
        'descripcion' => 'File description',
        'descripcion_marcador' => 'Add a description for the file',
    ],

    'categorias' => [
        'etiqueta' => 'Categories',
        'agregar' => 'Add category',
        'leyenda' => 'Categories of :tema',
        'nueva' => 'Name of the new category',
        'marcador' => 'For example: :ejemplo',
    ],

    'publicacion' => [
        'destacar' => 'Feature this content',
        'muro' => 'Show on the content wall',
        'programar' => 'Schedule',
        'publicar' => 'Publish',
        'guardar_programado' => 'Save as scheduled',
        'compartir' => 'Share on social media',
    ],

    'programacion' => [
        'etiqueta' => 'Publish on',
        'aviso' => 'The date is in the future: it will not be visible until then.',
    ],

    'pie' => [
        'tema' => 'Content in “:tema”',
    ],

    'eliminar' => [
        'confirmar' => 'Delete this content? This action cannot be undone.',
        'boton' => 'Delete content',
    ],

    'masiva' => [
        'titulo_pagina' => 'Bulk upload — :tema',
        'migaja' => 'Bulk upload',
        'encabezado' => 'Bulk upload of :tema',
        'error' => 'The file could not be uploaded',
        'recomendaciones' => 'Please take the following recommendations into account',
        'subir' => 'Upload the file with the data to be loaded',

        'pasos' => [
            'formato' => 'The file must be in Excel format (xlsx).',
            'columnas' => 'It must have 3 columns.',
            'nombre' => '— The first, the name (required, up to 200 characters)',
            'descripcion' => '— The second, the description (required)',
            'direccion' => '— The third, the address (required)',
            'sin_encabezado' => 'It must have no header row.',
        ],

        'agregar' => 'Add file',
        'cargar' => 'Upload',
        'cancelar' => 'Cancel',
    ],

];
