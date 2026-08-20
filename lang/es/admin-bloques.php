<?php

return [

    // ---------------- Común a los formularios de bloque ----------------

    'acciones' => [
        'agregar' => 'Agregar',
        'editar' => 'Editar',
        'cancelar' => 'Cancelar',
        'guardar' => 'Guardar',
        'vista_previa' => 'Vista previa',
    ],

    'comun' => [
        'nombre_bloque' => 'Nombre del bloque',
        'limite_30' => '(30 caracteres)',
        'nombre_acceso' => 'Nombre de acceso',
    ],

    // ---------------- Listado de banners ----------------

    'banners' => [
        'titulo' => 'Administración de banners',
        'descripcion' => 'Agrega y/o edita el contenido de los banners y el orden en que aparecerán '
            .'(máximo :maximo banners).',
        'completo' => 'Ya hay :maximo banners publicados, el máximo permitido. Elimine uno para poder agregar otro.',
        'vacio' => 'Todavía no hay banners. Use «Agregar» para publicar el primero.',
        'subir' => 'Subir el banner :numero',
        'bajar' => 'Bajar el banner :numero',
        'enlace' => 'Enlace:',
        'sin_enlace' => 'Sin enlace',
        'editar_detalle' => 'el banner «:texto»',

        'rotacion' => [
            'titulo' => 'Duración de rotación',
            'descripcion' => 'Define los segundos que durará la rotación automática de las imágenes.',
            'etiqueta' => 'Segundos entre banners',
            'opcion' => ':segundos segundos',
        ],
    ],

    // ---------------- Formulario de banner ----------------

    'banner' => [
        'titulo_editar' => 'Editar banner',
        'titulo_nuevo' => 'Agregar banner',
        'encabezado' => 'Configuración del banner',
        'descripcion_editar' => 'Modifique la imagen, los textos y el enlace de este banner.',
        'descripcion_nuevo' => 'Suba la imagen del banner y, si lo necesita, añada textos encima.',

        'imagen' => [
            'titulo' => 'Tipo de contenido',
            'descripcion' => 'Elija la imagen que se mostrará como fondo del banner.',
            'foto_fondo' => 'Foto de fondo',
            'seleccionar' => 'Seleccionar imagen del banner',
            'quitar' => 'Quitar la imagen seleccionada',
            'tamano' => 'Tamaño recomendado :ancho × :alto px.',
            'peso' => 'Peso máximo permitido 2 MB.',
            'formatos' => 'Formatos: gif, png, jpg, jpeg, bmp, webp.',
        ],

        'filtro' => [
            'titulo' => 'Filtro',
            'descripcion' => 'Color y transparencia de la capa que se superpone a la imagen para que '
                .'el texto se lea mejor.',
            'color' => 'Color',
            'opacidad' => 'Opacidad',
        ],

        // «:campo» es el nombre del campo en minúscula: lo pone la propia
        // vista, que recorre el título y el subtítulo con el mismo bloque.
        'titulo' => [
            'nombre' => 'Título',
            'minuscula' => 'título',
        ],

        'subtitulo' => [
            'nombre' => 'Subtítulo',
            'minuscula' => 'subtítulo',
        ],

        'texto' => [
            'descripcion' => 'Texto, colores y tipografía del :campo. Es opcional: si la imagen ya lleva '
                .'el texto incrustado, déjelo vacío.',
            'color_letra' => 'Color de letra',
            'color_fondo' => 'Color de fondo',
            'quitar_fondo' => 'Quitar el color de fondo del :campo',
            'tipografia' => 'Tipografía',
            'negrita' => 'Negrita del :campo',
            'cursiva' => 'Cursiva del :campo',
            'etiqueta' => ':campo del banner',
            'caracteres' => ':restantes caracteres',
        ],

        'justificacion' => [
            'titulo' => 'Justificación',
            'descripcion' => 'Alineación del título y el subtítulo.',
            'left' => 'Izquierda',
            'center' => 'Centro',
        ],

        'alternativo' => [
            'titulo' => 'Texto descriptivo para accesibilidad',
            'descripcion' => 'Describe el banner para quien no puede verlo. Es obligatorio.',
            'restantes' => 'Caracteres restantes: :restantes',
            'ayuda' => 'Describa lo que comunica el banner, no su apariencia. Si lleva texto incrustado, '
                .'inclúyalo aquí.',
        ],

        'enlace' => [
            'titulo' => 'Agregar enlace',
            'descripcion' => 'Es necesario incluir el http:// o https://',
        ],

        'eliminar' => [
            'confirmacion' => '¿Eliminar este banner? La acción no se puede deshacer.',
            'boton' => 'Eliminar banner',
        ],
    ],

    // ---------------- Bloque de contenidos ----------------

    // El aviso que se manda a la región `aria-live` al mover una fila: el
    // cambio es puramente visual y sin decirlo no se percibe con lector de
    // pantalla. Lo pinta Alpine, así que el rótulo viaja desde la vista.
    'orden_movido' => 'Movido a la posición :posicion de :total.',

    'bloque' => [
        'titulo' => 'Configuración del bloque',
        'descripcion' => 'Define de qué secciones se nutre este bloque de la portada y cómo se presenta.',
        'nombre_ayuda' => 'Rótulo interno para distinguir el bloque; no se muestra en la portada.',
        'seleccion' => 'Selecciona una sección',

        // La ruta que ocupa la sección dentro del portal, tal y como se lee en el
        // menú: «Inicio / Infórmate / Noticias».
        'ruta_seccion' => 'Inicio / Infórmate / :categoria',
        'numero' => 'Número de secciones a mostrar',

        'orden' => [
            'etiqueta' => 'Orden de los contenidos',
            'recent' => 'Más reciente',
            'oldest' => 'Más antiguo',
        ],

        'titulo_visible' => 'Habilitar título',
        'titulo_visible_ayuda' => 'Muestra en la portada el título de cada sección. Puedes cambiarlo en el '
            .'campo «Título que lleva esta sección».',

        'secciones' => 'Secciones de bloque',

        'ordinal' => [
            'uno' => 'uno',
            'dos' => 'dos',
            'tres' => 'tres',
        ],

        'seccion' => 'Sección :ordinal',
        'elige' => 'Elige la sección :ordinal',
        'titulo_seccion' => 'Título que lleva esta sección',
        'ocultar' => 'Ocultar en muro de contenidos',
        'ocultar_ayuda' => 'Los contenidos de esta sección salen del listado general de la portada, '
            .'pero siguen apareciendo en este bloque.',

        'tema' => [
            'titulo' => 'Selecciona un tema',
            'descripcion' => 'Color de fondo del bloque. Todos los tonos están oscurecidos lo necesario '
                .'para que el texto blanco se lea encima; los claros del portal original lo dejarían ilegible.',
            'etiqueta' => 'Tema de color del bloque',
            'vista_titulo' => 'Título',
            'vista_texto' => 'Texto descriptivo',
        ],
    ],

    // ---------------- Bloque de eventos ----------------

    'eventos' => [
        'titulo' => 'Configuración del bloque de eventos',
        'encabezado' => 'Configuración del bloque',
        'nombre_ayuda' => 'Es el título que se ve sobre el calendario.',
        'seccion' => 'Selecciona una sección',
        'categorias' => 'Selecciona una o varias categorías',
        'opcional' => '(opcional)',
        'categorias_ayuda' => 'Sin ninguna marcada, el calendario muestra toda la agenda.',
        'sin_categorias' => 'La sección elegida todavía no tiene categorías. Se crean al editar sus contenidos.',
    ],

    // ---------------- Barra de accesos directos ----------------

    'barra' => [
        'titulo' => 'Barra de accesos directos',
        'nombre_ayuda' => 'Es un rótulo interno para distinguir las barras; no se muestra en la portada.',
        'accesos' => 'Accesos directos',
        'minimo' => 'Debes configurar por lo menos :minimo accesos directos para que este control se '
            .'publique a los usuarios.',
        'completo' => 'Esta barra ya tiene :maximo accesos, el máximo permitido.',
        'vacio' => 'Esta barra todavía no tiene accesos directos.',
        'pendiente' => 'Con :actuales acceso(s) la barra no se publica todavía. Faltan :faltan.',
        'subir' => 'Subir «:texto»',
        'bajar' => 'Bajar «:texto»',
        'editar_detalle' => '«:texto»',

        'tema' => [
            'titulo' => 'Seleccionar un tema',
            'descripcion' => 'El color se aplica al icono. El rótulo conserva el azul institucional: varios '
                .'de estos tonos no alcanzan el contraste mínimo sobre fondo blanco, y el texto es lo que '
                .'hay que poder leer.',
            'etiqueta' => 'Tema de color de la barra',
        ],
    ],

    // ---------------- Acceso directo ----------------

    'acceso' => [
        'titulo_editar' => 'Editar acceso directo',
        'titulo_nuevo' => 'Nuevo acceso directo',
        'barra' => 'Barra «:nombre»',
        'nombre' => 'Nombre',
        'limite_40' => '(40 caracteres)',

        'enlace' => [
            'etiqueta' => 'Enlace',
            'ayuda' => 'Una dirección completa con http:// o https://, o una ruta del portal que empiece '
                .'por «/» —por ejemplo :ejemplo—. Las rutas se sirven del portal actual hasta que esa '
                .'sección exista aquí; entonces pasarán a resolverse contra este sitio sin tocar nada.',
            'marcador' => 'https://…  o  /tema/…',
            'destino' => 'Destino actual: :url',
        ],

        'icono' => 'Icono',

        // Las claves son las de Shortcut::ICONS y no cambian: el icono elegido
        // se guarda por clave, no por rótulo.
        'iconos' => [
            'calendar-check' => 'Calendario',
            'graduation' => 'Formación',
            'map-pin' => 'Ubicación',
            'lab' => 'Laboratorio',
            'payment' => 'Pagos',
            'inbox' => 'Buzón',
            'chart' => 'Indicadores',
            'info' => 'Información',
            'gavel' => 'Jurídico',
            'megaphone' => 'Anuncio',
        ],

        'eliminar' => [
            'confirmacion' => '¿Eliminar «:texto»?',
            'boton' => 'Eliminar acceso directo',
        ],
    ],

];
