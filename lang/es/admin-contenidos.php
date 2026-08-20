<?php

return [

    // ---------------- Pantalla propia del editor ----------------

    'pantalla' => [
        'nuevo' => 'Nuevo contenido',
        'actualizar' => 'Actualizar contenido',
    ],

    // ---------------- Errores de validación ----------------

    'errores' => [
        'titulo' => 'Revise los siguientes puntos',
    ],

    // ---------------- Categoría ----------------

    'categoria' => [
        'etiqueta' => 'Categoría',
    ],

    // ---------------- Título ----------------

    'titulo' => [
        'etiqueta' => 'Título',
        'limite' => '(150 caracteres)',

        // El contador lo pinta Alpine, que sustituye :n por los caracteres
        // que quedan libres.
        'restantes' => 'Caracteres restantes: :n',
    ],

    // ---------------- Fechas ----------------

    'fechas' => [
        'sin_inicio' => 'Sin fecha de visualización',
        'publicacion' => 'Fecha de publicación',
        'programado' => 'La fecha está por delante: quedará programado.',
        'sin_fin' => 'Sin fecha final de visualización',
        'fin' => 'Fecha final de visualización',
        'fin_ayuda' => 'Pasada esa fecha deja de mostrarse, sin tener que retirarlo a mano.',
    ],

    // ---------------- Resumen ----------------

    'resumen' => [
        'etiqueta' => 'Resumen',
        'limite' => '(opcional, 400 caracteres)',
        'ayuda' => 'Es lo que se lee en la portada. Vacío, se toman las primeras líneas de la descripción.',
    ],

    // ---------------- Descripción ----------------

    'descripcion' => [
        'etiqueta' => 'Descripción',
        'editor' => 'Descripción del contenido',
        'sin_javascript' => 'El editor con formato necesita JavaScript.',
    ],

    // ---------------- Medios ----------------

    'medios' => [
        'titulo' => 'Medios',
        'ayuda' => 'Fotos, vídeo y documentos que acompañan al contenido.',
        'ayuda_archivos' => 'Archivos que acompañan al contenido.',

        'fotos' => [
            'publicadas' => 'Fotos publicadas',
            'principal' => 'Principal',
            'descripcion_numerada' => 'Descripción de la foto :n',
            'descripcion' => 'Descripción de la imagen',
            'quitar' => 'Quitar esta foto',
            'agregar' => 'Agrega foto',
            'dimension' => 'Dimensión recomendada :ancho × :alto px.',
            'limites' => 'Peso máximo 2 MB. Formatos gif, jpg, jpeg, png, bmp, webp.',
        ],

        'video' => [
            'agregar' => 'Agrega vídeo',
            'ayuda' => 'URL de YouTube, con https:// por delante.',
            'vaciar' => 'Deje el campo vacío para quitar el vídeo.',
        ],

        'archivos' => [
            'agregar' => 'Agrega archivo',
            'limites' => 'Peso máximo 30 MB.',
            'titulo_nuevo' => 'Título visible del documento (opcional)',
            'publicados' => 'Documentos publicados',
            'titulo' => 'Título del documento',
            'quitar' => 'Quitar',
        ],
    ],

    // ---------------- Biblioteca de imágenes ----------------

    'biblioteca' => [
        'elegir' => 'Elige una imagen de la biblioteca',
        'todas' => 'Todas',
        'agregar_categoria' => 'Agregar categoría +',
        'vacia' => 'No hay imágenes en la biblioteca todavía.',
        'seleccionada' => 'Seleccionada',
        'sin_seleccionar' => 'Sin seleccionar',

        // El recuento lo pinta Alpine, que sustituye :n por las imágenes
        // marcadas en la rejilla.
        'elegidas' => ':n imagen(es) de la biblioteca en este contenido.',

        'compartidas' => 'Estas imágenes se comparten entre contenidos: al quitarlas de aquí no se borran '
            .'de la biblioteca.',

        'gestion' => [
            'titulo' => 'Gestionar la biblioteca de imágenes',
            'subir' => 'Subir una imagen a la biblioteca',
            'archivo' => 'Archivo',
            'descripcion' => 'Descripción',
            'categoria' => 'Categoría',
            'sin_categoria' => 'Sin categoría',
            'descripcion_ayuda' => 'La descripción es obligatoria: acompañará a la imagen en todos los '
                .'contenidos donde se use.',
            'subir_boton' => 'Subir imagen',
            'nueva_categoria' => 'Agregar una categoría',
            'nombre' => 'Nombre',
            'nombre_ejemplo' => 'Por ejemplo: Fachadas',
            'crear_categoria' => 'Crear categoría',
        ],
    ],

    // ---------------- Participación ----------------

    'participacion' => [
        'etiqueta' => 'Participación ciudadana',
    ],

    // ---------------- Enlace ----------------

    'enlace' => [
        'etiqueta' => 'Enlace',
        'ayuda' => 'Si el contenido completo vive fuera del portal. Con http:// o https://',
    ],

    // ---------------- Publicación ----------------

    'publicacion' => [
        'destacar' => 'Destacar contenido',
        'muro' => 'Mostrar en muro de contenidos',
        'programar' => 'Programar',
        'programar_bloqueado' => 'Elija una fecha futura para poder programar',
        'publicar' => 'Publicar',
        'compartir' => 'Compartir en redes',

        // :categoria es el nombre del tema, que decide el modelo y no se
        // traduce aquí.
        'contexto' => 'Contenido en «:categoria»',
    ],

    // ---------------- Eliminar ----------------

    'eliminar' => [
        'confirmar' => '¿Eliminar este contenido? La acción no se puede deshacer.',
        'boton' => 'Eliminar contenido',
    ],

];
