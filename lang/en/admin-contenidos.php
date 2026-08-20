<?php

return [

    'pantalla' => [
        'nuevo' => 'New content',
        'actualizar' => 'Update content',
    ],

    'errores' => [
        'titulo' => 'Please review the following points',
    ],

    'categoria' => [
        'etiqueta' => 'Category',
    ],

    'titulo' => [
        'etiqueta' => 'Title',
        'limite' => '(150 characters)',
        'restantes' => 'Characters remaining: :n',
    ],

    'fechas' => [
        'sin_inicio' => 'No display date',
        'publicacion' => 'Publication date',
        'programado' => 'The date is in the future: the content will be scheduled.',
        'sin_fin' => 'No end date for display',
        'fin' => 'End date for display',
        'fin_ayuda' => 'After that date it is no longer shown, with no need to remove it by hand.',
    ],

    'resumen' => [
        'etiqueta' => 'Summary',
        'limite' => '(optional, 400 characters)',
        'ayuda' => 'This is what is read on the home page. If left empty, the first lines of the '
            .'description are used.',
    ],

    'descripcion' => [
        'etiqueta' => 'Description',
        'editor' => 'Content description',
        'sin_javascript' => 'The rich text editor requires JavaScript.',
    ],

    'medios' => [
        'titulo' => 'Media',
        'ayuda' => 'Photos, video and documents that accompany the content.',
        'ayuda_archivos' => 'Files that accompany the content.',

        'fotos' => [
            'publicadas' => 'Published photos',
            'principal' => 'Main',
            'descripcion_numerada' => 'Description of photo :n',
            'descripcion' => 'Image description',
            'quitar' => 'Remove this photo',
            'agregar' => 'Add a photo',
            'dimension' => 'Recommended size :ancho × :alto px.',
            'limites' => 'Maximum size 2 MB. Formats gif, jpg, jpeg, png, bmp, webp.',
        ],

        'video' => [
            'agregar' => 'Add a video',
            'ayuda' => 'YouTube URL, starting with https://.',
            'vaciar' => 'Leave the field empty to remove the video.',
        ],

        'archivos' => [
            'agregar' => 'Add a file',
            'limites' => 'Maximum size 30 MB.',
            'titulo_nuevo' => 'Document title shown to visitors (optional)',
            'publicados' => 'Published documents',
            'titulo' => 'Document title',
            'quitar' => 'Remove',
        ],
    ],

    'biblioteca' => [
        'elegir' => 'Choose an image from the library',
        'todas' => 'All',
        'agregar_categoria' => 'Add a category +',
        'vacia' => 'There are no images in the library yet.',
        'seleccionada' => 'Selected',
        'sin_seleccionar' => 'Not selected',

        'elegidas' => ':n library image(s) in this content.',

        'compartidas' => 'These images are shared between contents: removing them here does not delete '
            .'them from the library.',

        'gestion' => [
            'titulo' => 'Manage the image library',
            'subir' => 'Upload an image to the library',
            'archivo' => 'File',
            'descripcion' => 'Description',
            'categoria' => 'Category',
            'sin_categoria' => 'No category',
            'descripcion_ayuda' => 'The description is required: it will accompany the image in every '
                .'content where it is used.',
            'subir_boton' => 'Upload image',
            'nueva_categoria' => 'Add a category',
            'nombre' => 'Name',
            'nombre_ejemplo' => 'For example: Facades',
            'crear_categoria' => 'Create category',
        ],
    ],

    'participacion' => [
        'etiqueta' => 'Citizen participation',
    ],

    'enlace' => [
        'etiqueta' => 'Link',
        'ayuda' => 'If the full content lives outside the portal. With http:// or https://',
    ],

    'publicacion' => [
        'destacar' => 'Feature this content',
        'muro' => 'Show on the content wall',
        'programar' => 'Schedule',
        'programar_bloqueado' => 'Choose a future date in order to schedule',
        'publicar' => 'Publish',
        'compartir' => 'Share on social media',

        'contexto' => 'Content in “:categoria”',
    ],

    'eliminar' => [
        'confirmar' => 'Delete this content? This action cannot be undone.',
        'boton' => 'Delete content',
    ],

];
