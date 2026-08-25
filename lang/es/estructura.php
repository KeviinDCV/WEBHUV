<?php

return [

    'esqueleto' => [
        'saltar' => 'Saltar al contenido principal',
    ],

    'navegacion' => [
        'menu_principal' => 'Menú principal',
        'abrir_menu_completo' => 'Abrir menú completo',
        'categorias_menu_completo' => 'Categorías del menú completo',
        'inicio' => 'Inicio',
        'menu' => 'Menú',
        'cerrar' => 'Cerrar',
        'cerrar_menu' => 'Cerrar menú',
    ],

    'barra_admin' => [
        'menu' => 'Menú del portal',
        'sesion_iniciada' => 'Sesión iniciada como',
        'controles_edicion' => 'Controles de edición',
        'cerrar_sesion' => 'Cerrar sesión',
    ],

    'pie' => [
        'ultima_modificacion' => 'Última modificación',
        'redes' => 'Redes sociales del hospital',
        'en_red' => 'en :red (se abre en una pestaña nueva)',
        'enlaces_legales' => 'Enlaces legales y de servicio',
    ],

    'hora_legal' => [
        'rotulo' => 'Hora legal',
        'pais' => 'República de Colombia',
        // El nombre del instituto va aparte: es un nombre propio y se queda en
        // español, así que se marca su idioma sin partir la frase en dos.
        'inm' => 'Instituto Nacional de Metrología de Colombia',
        'descripcion' => 'Hora legal de la República de Colombia, según el :entidad.',
    ],

    'accesibilidad' => [
        'herramientas' => 'Herramientas de accesibilidad',

        'contraste' => [
            'alternar' => 'Alternar alto contraste',
            'titulo' => 'Alto contraste',
        ],

        // «:tamano» lo sustituye Alpine con el tamaño vigente: el rótulo cambia
        // en el navegador y no puede resolverse al renderizar la página.
        'texto' => [
            'aumentar' => 'Aumentar tamaño del texto',
            'aumentar_titulo' => 'Aumentar texto (actual: :tamano)',
            'reducir' => 'Reducir tamaño del texto',
            'reducir_titulo' => 'Reducir texto (actual: :tamano)',
        ],

        'restablecer' => [
            'accion' => 'Restablecer preferencias de accesibilidad',
            'titulo' => 'Restablecer',
        ],

        'relevo' => [
            'enlace' => 'Centro de relevo — lengua de señas colombiana (se abre en una pestaña nueva)',
            'titulo' => 'Lengua de señas colombiana',
        ],

        'estado' => [
            'contraste_activado' => 'Alto contraste activado.',
            'contraste_desactivado' => 'Alto contraste desactivado.',
            'tamano_texto' => 'Tamaño de texto :tamano.',
        ],
    ],

    'volver_arriba' => [
        'rotulo' => 'Volver arriba',
    ],

    'salida' => [
        'cerrar' => 'Cerrar el aviso',
        'titulo' => 'Atención',
        'texto' => 'Está a punto de ser redirigido a',
        'aceptar' => 'Aceptar',
        'cancelar' => 'Cancelar',
    ],

    'utilidad' => [
        'encontraste' => '¿Encontraste lo que buscabas?',
        'util' => '¿Te pareció útil este contenido?',
        'si' => 'Sí',
        'no' => 'No',
        'gracias' => 'Gracias por su respuesta.',
    ],

    'participacion' => [
        'aviso' => 'Este contenido está abierto a la participación ciudadana. Puede enviar sus aportes por los canales de atención del hospital.',
    ],

    'medios' => [
        'video' => 'Vídeo',
        'video_titulo' => 'Vídeo del contenido',
        'documentos_adjuntos' => 'Documentos adjuntos',
        'sin_extension' => 'Archivo',
    ],

    'admin' => [
        'solo_administrador' => 'Solo para el administrador',
        'area' => 'Administración',
        'cerrar_sesion' => 'Cerrar sesión',
        'atras' => 'Atrás',
        'errores' => 'Revise los siguientes puntos',
    ],

];
