<?php

return [

    'acciones' => [
        'agregar' => 'Add',
        'editar' => 'Edit',
        'cancelar' => 'Cancel',
        'guardar' => 'Save',
        'vista_previa' => 'Preview',
    ],

    'comun' => [
        'nombre_bloque' => 'Block name',
        'limite_30' => '(30 characters)',
        'nombre_acceso' => 'Shortcut name',
    ],

    'banners' => [
        'titulo' => 'Banner management',
        'descripcion' => 'Add and edit the content of the banners and the order in which they will appear '
            .'(maximum :maximo banners).',
        'completo' => 'There are already :maximo published banners, the maximum allowed. Delete one before '
            .'adding another.',
        'vacio' => 'There are no banners yet. Use “Add” to publish the first one.',
        'subir' => 'Move banner :numero up',
        'bajar' => 'Move banner :numero down',
        'enlace' => 'Link:',
        'sin_enlace' => 'No link',
        'editar_detalle' => 'the banner “:texto”',

        'rotacion' => [
            'titulo' => 'Rotation time',
            'descripcion' => 'Set how many seconds the automatic image rotation will take.',
            'etiqueta' => 'Seconds between banners',
            'opcion' => ':segundos seconds',
        ],
    ],

    'banner' => [
        'titulo_editar' => 'Edit banner',
        'titulo_nuevo' => 'Add banner',
        'encabezado' => 'Banner settings',
        'descripcion_editar' => 'Change the image, the text and the link of this banner.',
        'descripcion_nuevo' => 'Upload the banner image and, if you need to, add text on top of it.',

        'imagen' => [
            'titulo' => 'Content type',
            'descripcion' => 'Choose the image that will be shown as the banner background.',
            'foto_fondo' => 'Background photo',
            'seleccionar' => 'Select the banner image',
            'quitar' => 'Remove the selected image',
            'tamano' => 'Recommended size :ancho × :alto px.',
            'peso' => 'Maximum file size 2 MB.',
            'formatos' => 'Formats: gif, png, jpg, jpeg, bmp, webp.',
        ],

        'filtro' => [
            'titulo' => 'Filter',
            'descripcion' => 'Color and transparency of the layer placed over the image so that the text '
                .'is easier to read.',
            'color' => 'Color',
            'opacidad' => 'Opacity',
        ],

        'titulo' => [
            'nombre' => 'Title',
            'minuscula' => 'title',
        ],

        'subtitulo' => [
            'nombre' => 'Subtitle',
            'minuscula' => 'subtitle',
        ],

        'texto' => [
            'descripcion' => 'Text, colors and typeface of the :campo. It is optional: if the image already '
                .'has the text embedded, leave it empty.',
            'color_letra' => 'Font color',
            'color_fondo' => 'Background color',
            'quitar_fondo' => 'Remove the background color of the :campo',
            'tipografia' => 'Typeface',
            'negrita' => 'Bold :campo',
            'cursiva' => 'Italic :campo',
            'etiqueta' => ':campo of the banner',
            'caracteres' => ':restantes characters',
        ],

        'justificacion' => [
            'titulo' => 'Alignment',
            'descripcion' => 'Alignment of the title and the subtitle.',
            'left' => 'Left',
            'center' => 'Center',
        ],

        'alternativo' => [
            'titulo' => 'Descriptive text for accessibility',
            'descripcion' => 'Describe the banner for those who cannot see it. It is required.',
            'restantes' => 'Characters remaining: :restantes',
            'ayuda' => 'Describe what the banner communicates, not how it looks. If it has embedded text, '
                .'include it here.',
        ],

        'enlace' => [
            'titulo' => 'Add a link',
            'descripcion' => 'You must include http:// or https://',
        ],

        'eliminar' => [
            'confirmacion' => 'Delete this banner? This action cannot be undone.',
            'boton' => 'Delete banner',
        ],
    ],

    'orden_movido' => 'Moved to position :posicion of :total.',

    'bloque' => [
        'titulo' => 'Block settings',
        'descripcion' => 'Choose which sections feed this home page block and how it is presented.',
        'nombre_ayuda' => 'Internal label used to tell the blocks apart; it is not shown on the home page.',
        'seleccion' => 'Select a section',

        'ruta_seccion' => 'Home / Get informed / :categoria',
        'numero' => 'Number of sections to show',

        'orden' => [
            'etiqueta' => 'Order of the content',
            'recent' => 'Most recent',
            'oldest' => 'Oldest',
        ],

        'titulo_visible' => 'Enable the title',
        'titulo_visible_ayuda' => 'Shows the title of each section on the home page. You can change it in '
            .'the “Title for this section” field.',

        'secciones' => 'Block sections',

        'ordinal' => [
            'uno' => 'one',
            'dos' => 'two',
            'tres' => 'three',
        ],

        'seccion' => 'Section :ordinal',
        'elige' => 'Choose section :ordinal',
        'titulo_seccion' => 'Title for this section',
        'ocultar' => 'Hide from the content wall',
        'ocultar_ayuda' => 'The content in this section is removed from the general listing on the home '
            .'page, but it still appears in this block.',

        'tema' => [
            'titulo' => 'Select a theme',
            'descripcion' => 'Background color of the block. Every shade is darkened as much as needed for '
                .'white text to be readable on top; the light ones from the original portal would leave it '
                .'illegible.',
            'etiqueta' => 'Block color theme',
            'vista_titulo' => 'Title',
            'vista_texto' => 'Descriptive text',
        ],
    ],

    'eventos' => [
        'titulo' => 'Event block settings',
        'encabezado' => 'Block settings',
        'nombre_ayuda' => 'This is the title shown above the calendar.',
        'seccion' => 'Select a section',
        'categorias' => 'Select one or more categories',
        'opcional' => '(optional)',
        'categorias_ayuda' => 'With none selected, the calendar shows the whole agenda.',
        'sin_categorias' => 'The section you chose does not have categories yet. They are created when you '
            .'edit its content.',
    ],

    'barra' => [
        'titulo' => 'Shortcut bar',
        'nombre_ayuda' => 'Internal label used to tell the bars apart; it is not shown on the home page.',
        'accesos' => 'Shortcuts',
        'minimo' => 'You must set up at least :minimo shortcuts for this control to be published to users.',
        'completo' => 'This bar already has :maximo shortcuts, the maximum allowed.',
        'vacio' => 'This bar does not have any shortcuts yet.',
        'pendiente' => 'With :actuales shortcut(s) the bar is not published yet. :faltan more needed.',
        'subir' => 'Move “:texto” up',
        'bajar' => 'Move “:texto” down',
        'editar_detalle' => '“:texto”',

        'tema' => [
            'titulo' => 'Select a theme',
            'descripcion' => 'The color is applied to the icon. The label keeps the institutional blue: '
                .'several of these shades do not reach the minimum contrast on a white background, and the '
                .'text is what has to be readable.',
            'etiqueta' => 'Bar color theme',
        ],
    ],

    'acceso' => [
        'titulo_editar' => 'Edit shortcut',
        'titulo_nuevo' => 'New shortcut',
        'barra' => 'Bar “:nombre”',
        'nombre' => 'Name',
        'limite_40' => '(40 characters)',

        'enlace' => [
            'etiqueta' => 'Link',
            'ayuda' => 'A full address with http:// or https://, or a portal path starting with “/” '
                .'—for example :ejemplo—. Paths are served from the current portal until that section '
                .'exists here; they will then resolve against this site with nothing else to change.',
            'marcador' => 'https://…  or  /tema/…',
            'destino' => 'Current destination: :url',
        ],

        'icono' => 'Icon',

        'iconos' => [
            'calendar-check' => 'Calendar',
            'graduation' => 'Training',
            'map-pin' => 'Location',
            'lab' => 'Laboratory',
            'payment' => 'Payments',
            'inbox' => 'Inbox',
            'chart' => 'Indicators',
            'info' => 'Information',
            'gavel' => 'Legal',
            'megaphone' => 'Announcement',
        ],

        'eliminar' => [
            'confirmacion' => 'Delete “:texto”?',
            'boton' => 'Delete shortcut',
        ],
    ],

];
