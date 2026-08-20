<?php

return [

    // ---------------- Editor de un elemento de un tema ----------------

    // El rótulo del tipo cuando el tema mezcla varios: el nombre de cada uno
    // lo pone el modelo («documento», «enlace»…) y no se traduce aquí.
    'tipo' => [
        'etiqueta' => 'Tipo de contenido',
    ],

    'enlace' => [
        'etiqueta' => 'Link',
        'pista' => '(Agregar https:// o http://)',
        'marcador' => 'Link',
        'ayuda_documento' => 'Para documentos que se consultan en otro sitio. Si adjunta un archivo, no hace falta.',
        'ayuda_enlace' => 'Adonde lleva este contenido. Es lo que lo define, así que hace falta.',
    ],

    'titulo' => [
        'etiqueta' => 'Título',
        'limite' => '(150 caracteres)',
        'marcador' => 'Título',

        // El recuento lo calcula Alpine en el navegador, así que el marcador
        // se sustituye allí; aquí solo se guarda la frase.
        'restantes' => 'Caracteres restantes: :n',
    ],

    'expedicion' => [
        'etiqueta' => 'Fecha expedición',
        'ayuda' => 'La del documento en sí, no la del día en que se sube.',
    ],

    'evento' => [
        'organizador' => 'Organizador',
        'lugar' => 'Lugar',
        'limite' => '(70 caracteres)',
        'fecha' => 'Fecha inicio',
        'hora' => 'Hora inicio',
    ],

    'convocatoria' => [
        'apertura' => 'Fecha y hora de inicio',
        'cierre' => 'Fecha y hora de cierre',
        'ayuda' => 'Cerrada se sigue consultando: la fecha de cierre informa del proceso, '
            .'no retira la convocatoria del listado.',
    ],

    'caducidad' => [
        'sin_fecha' => 'Sin fecha final de visualización',
        'etiqueta' => 'Fecha final de visualización',
        'ayuda' => 'Pasada esa fecha deja de mostrarse, sin tener que retirarlo a mano.',
    ],

    'descripcion' => [
        'etiqueta' => 'Descripción',
        'editor' => 'Descripción del contenido',
        'sin_js' => 'El editor con formato necesita JavaScript.',
    ],

    'archivo' => [
        'actual' => 'Archivo principal:',
        'etiqueta' => 'Archivo principal',
        'reemplazar' => 'Reemplazar el archivo principal',
        'limite' => 'Peso máximo: 30 MB · pdf, doc, xls, ppt, csv, txt o zip',
        'descripcion' => 'Descripción del archivo',
        'descripcion_marcador' => 'Agregue una descripción al archivo',
    ],

    'categorias' => [
        'etiqueta' => 'Categorías',
        'agregar' => 'Agregar categoría',
        'leyenda' => 'Categorías de :tema',
        'nueva' => 'Nombre de la categoría nueva',
        'marcador' => 'Por ejemplo: :ejemplo',
    ],

    'publicacion' => [
        'destacar' => 'Destacar contenido',
        'muro' => 'Mostrar en muro de contenidos',
        'programar' => 'Programar',
        'publicar' => 'Publicar',
        'guardar_programado' => 'Guardar programado',
        'compartir' => 'Compartir en redes',
    ],

    'programacion' => [
        'etiqueta' => 'Publicar el',
        'aviso' => 'La fecha está por delante: no se verá hasta que llegue.',
    ],

    'pie' => [
        'tema' => 'Contenido en «:tema»',
    ],

    'eliminar' => [
        'confirmar' => '¿Eliminar este contenido? La acción no se puede deshacer.',
        'boton' => 'Eliminar contenido',
    ],

    // ---------------- Carga masiva ----------------

    'masiva' => [
        'titulo_pagina' => 'Carga masiva — :tema',
        'migaja' => 'Carga masiva',
        'encabezado' => 'Carga masiva de :tema',
        'error' => 'No se pudo cargar el archivo',
        'recomendaciones' => 'Tenga en cuenta las siguientes recomendaciones',
        'subir' => 'Suba el archivo con los datos a cargar',

        'pasos' => [
            'formato' => 'El archivo debe ser en formato Excel (xlsx).',
            'columnas' => 'Debe tener 3 columnas.',
            'nombre' => '— La primera el nombre (requerida, máximo 200 caracteres)',
            'descripcion' => '— La segunda la descripción (requerida)',
            'direccion' => '— La tercera la dirección (requerida)',
            'sin_encabezado' => 'Debe ir sin encabezado.',
        ],

        'agregar' => 'Agregar archivo',
        'cargar' => 'Cargar',
        'cancelar' => 'Cancelar',
    ],

];
