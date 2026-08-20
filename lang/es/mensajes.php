<?php

return [
    /*
     | Como se lee en pantalla cada categoria de contenido. El valor guardado
     | en la columna no cambia: es con el que se consulta y con el que la
     | importacion decide donde va cada cosa.
    */
    'categorias' => [
        'noticias' => 'Noticias',
        'notificaciones_judiciales' => 'Notificaciones Judiciales',
        'comunicados' => 'Comunicados',
    ],


    // ---------------- Acceso al portal ----------------

    'acceso' => [
        'iniciada' => 'Sesión iniciada. Ya puede editar el contenido del portal.',
        'cerrada' => 'Sesión cerrada correctamente.',

        // Un único mensaje genérico: distinguir «no existe» de «contraseña
        // incorrecta» permitiría averiguar qué correos tienen cuenta.
        'credenciales' => 'Las credenciales no coinciden con nuestros registros.',

        // Dos mensajes y no uno con la unidad aparte: en inglés el plural no se
        // forma igual y una plantilla con hueco dejaría «1 minutos».
        'demasiados_minutos' => 'Demasiados intentos fallidos. Vuelva a intentarlo en :n minutos.',
        'demasiados_segundos' => 'Demasiados intentos fallidos. Vuelva a intentarlo en :n segundos.',
    ],

    // ---------------- Banners de la portada ----------------

    'banner' => [
        'agregado' => 'Banner agregado correctamente.',
        'actualizado' => 'Banner actualizado correctamente.',
        'eliminado' => 'Banner eliminado.',
        'orden_guardado' => 'Cambios guardados.',
        'sin_sitio' => 'Ya hay :maximo banners publicados, el máximo permitido. '
            .'Elimine uno antes de agregar otro.',
    ],

    // ---------------- Bloques de la portada ----------------

    'bloque' => [
        'contenidos_guardado' => 'Bloque de contenidos guardado.',
        'eventos_guardado' => 'Bloque de eventos guardado.',
    ],

    // ---------------- Contenidos y elementos de un tema ----------------

    'contenido' => [
        'publicado' => 'Contenido publicado correctamente.',
        'actualizado' => 'Contenido actualizado correctamente.',
        'eliminado' => 'Contenido eliminado.',
        'destacado' => 'Contenido destacado.',
        'activado' => 'Contenido activado.',
        'inactivado' => 'Contenido inactivado.',
        'oculto_portada' => 'Contenido oculto en la portada.',
        'visible_portada' => 'Contenido visible en la portada.',
        'oculto_listado' => 'Contenido oculto en el listado.',
        'visible_listado' => 'Contenido visible en el listado.',
    ],

    // ---------------- Categorías ----------------

    'categoria' => [
        'creada' => 'Categoría creada.',
    ],

    // ---------------- Biblioteca de imágenes ----------------

    'biblioteca' => [
        'categoria_creada' => 'Categoría creada.',
        'imagen_agregada' => 'Imagen añadida a la biblioteca.',
        'imagen_eliminada' => 'Imagen eliminada de la biblioteca.',
    ],

    // ---------------- Barras de accesos directos ----------------

    'accesos' => [
        'barra_guardada' => 'Barra de accesos guardada.',
        'agregado' => 'Acceso directo agregado.',
        'actualizado' => 'Acceso directo actualizado.',
        'eliminado' => 'Acceso directo eliminado.',
        'sin_sitio' => 'Esta barra ya tiene :maximo accesos, el máximo permitido.',
    ],

    // ---------------- Carga masiva desde una hoja de cálculo ----------------

    'carga' => [
        'cargados' => ':n contenidos cargados.',
        'descartadas' => ':n filas quedaron fuera.',
        'tope' => 'Solo se leyeron las primeras :n filas.',
        'fila_incompleta' => 'Fila :fila: hacen falta el nombre y la dirección.',
        'fila_direccion' => 'Fila :fila: «:url» no es una dirección válida.',
    ],

    // ---------------- Orden del listado de un tema ----------------

    'orden' => [
        'recientes' => 'Recientes',
        'az' => 'A-Z',
        'expedicion' => 'Fecha de expedición',
        'destacados' => 'Destacados',
    ],

    // ---------------- Pestañas de moderación ----------------
    //
    // Solo se ven con sesión iniciada, y las montan dos componentes de Alpine
    // —el muro de la portada y el listado de un tema—, así que los rótulos
    // viajan desde la vista: dentro del JavaScript no hay forma de traducirlos.
    'moderacion' => [
        'inactivos' => 'Inactivos',
        'ocultos' => 'Ocultos',
    ],

    // ---------------- Cómo se llama lo que se publica ----------------
    //
    // Los rótulos del editor y del filtro por tipo. «Clasificado» y «Link» son
    // como los llama el portal institucional en su propio editor.

    'tipo' => [
        'documento' => 'Documento',
        'clasificado' => 'Clasificado',
        'link' => 'Link',
        'pregunta' => 'Pregunta',
        'convocatoria' => 'Convocatoria',
        'evento' => 'Evento',
        'tramite' => 'Trámite',
        'noticia' => 'Noticia',
    ],

    // Cómo se cuentan en el pie del listado: «Mostrando 6 de 85 documentos».
    'sustantivo' => [
        'documentos' => 'documentos',
        'contenidos' => 'contenidos',
    ],

    // ---------------- Datos de un trámite ----------------

    'tramite' => [
        'en_linea' => 'Trámite en línea',
        'presencial' => 'Trámite presencial',
        'sin_costo' => 'Trámite sin costo',
        'con_costo' => 'Trámite con costo',
        'costo_exacto' => 'Costo: :costo',
    ],

    // ---------------- Participación ciudadana de un contenido ----------------
    //
    // En el mismo orden y con las mismas palabras que el portal institucional.

    'participacion' => [
        'ninguna' => 'Contenido sin participación',
        'publica' => 'Contenido con participación pública',
        'privada' => 'Contenido con participación privada',
    ],

    // ---------------- Nombres de los campos de los formularios ----------------
    //
    // Con estos rellena Laravel el hueco :attribute de los mensajes de
    // validación, así que se escriben en minúscula y sin punto final.

    'campo' => [
        'titulo' => 'título',
        'subtitulo' => 'subtítulo',
        'descripcion' => 'descripción',
        'resumen' => 'resumen',
        'enlace' => 'enlace',
        'nombre' => 'nombre',
        'icono' => 'icono',
        'tema' => 'tema',
        'seccion' => 'sección',
        'categoria' => 'categoría',
        'categorias' => 'categorías',
        'categoria_nueva' => 'categoría nueva',
        'nombre_categoria' => 'nombre de la categoría',
        'nombre_bloque' => 'nombre del bloque',
        'numero_secciones' => 'número de secciones',
        'orden_contenidos' => 'orden de los contenidos',

        'imagen' => 'imagen',
        'imagen_fondo' => 'imagen de fondo',
        'texto_descriptivo' => 'texto descriptivo para accesibilidad',
        'color_filtro' => 'color del filtro',
        'opacidad_filtro' => 'opacidad del filtro',
        'color_titulo' => 'color del título',
        'fondo_titulo' => 'color de fondo del título',
        'tipografia_titulo' => 'tipografía del título',
        'color_subtitulo' => 'color del subtítulo',
        'fondo_subtitulo' => 'color de fondo del subtítulo',
        'tipografia_subtitulo' => 'tipografía del subtítulo',
        'justificacion' => 'justificación',

        'fotos' => 'fotos',
        'url_video' => 'URL del vídeo',
        'archivo' => 'archivo',
        'archivos' => 'archivos',
        'descripcion_archivo' => 'descripción del archivo',

        'organizador' => 'organizador',
        'lugar' => 'lugar',
        'fecha_inicio' => 'fecha de inicio',
        'hora_inicio' => 'hora de inicio',
        'fecha_cierre' => 'fecha de cierre',
        'fecha_expedicion' => 'fecha de expedición',
        'fecha_publicacion' => 'fecha de publicación',
        'fecha_visualizacion' => 'fecha de visualización',
        'fecha_final' => 'fecha final de visualización',

        'correo' => 'correo institucional',
        'contrasena' => 'contraseña',
    ],

    // ---------------- Mensajes de validación escritos a mano ----------------
    //
    // Los de las reglas de la plataforma están en validation.php. Aquí solo los
    // que dicen algo que la regla genérica no diría.

    'validacion' => [
        'enlace_http' => 'El enlace debe empezar por http:// o https://',
        'enlace_destino' => 'Indique la dirección a la que lleva este contenido.',
        'url_portal' => 'Escriba una dirección completa (con http:// o https://) o una ruta '
            .'del portal que empiece por «/».',

        'imagen_pesada' => 'La imagen no puede pesar más de 2 MB.',
        'imagen_pequena' => 'La imagen es demasiado pequeña. Se recomienda 3750 × 968 píxeles.',
        'banner_alt' => 'El texto descriptivo es obligatorio: sin él, el banner no se '
            .'puede entender con un lector de pantalla.',
        'imagen_alt' => 'La descripción es obligatoria: acompañará a la imagen en todos los '
            .'contenidos donde se use.',

        'foto_pesada' => 'Cada foto puede pesar como máximo 2 MB.',
        'foto_formato' => 'Solo se admiten imágenes en gif, jpg, jpeg, png, bmp o webp.',
        'foto_alt' => 'Cada foto necesita su descripción: sin ella, no se puede entender '
            .'con un lector de pantalla.',
        'video_youtube' => 'El vídeo debe ser una dirección de YouTube.',

        'archivo_obligatorio' => 'Adjunte el archivo o indique un enlace donde consultarlo.',
        'archivo_pesado' => 'El archivo puede pesar como máximo 30 MB.',
        'archivos_pesados' => 'Cada archivo puede pesar como máximo 30 MB.',
        'archivos_maximo' => 'Se pueden adjuntar como máximo 20 archivos de una vez.',
        'archivo_formatos' => 'Formatos admitidos: pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt y zip.',

        'fecha_final_posterior' => 'La fecha final debe ser posterior a la de publicación.',
        'cierre_posterior' => 'La fecha de cierre no puede ir antes de la de inicio.',

        'categoria_ajena' => 'Esa categoría no pertenece a este tema.',
        'categoria_repetida' => 'Ya existe una categoría con ese nombre.',

        'seccion_bloque' => 'Elija la sección que alimenta el bloque.',
        'titulo_seccion' => 'Cada sección necesita el título con el que se muestra.',
        'seccion_calendario' => 'Elija la sección que alimenta el calendario.',
        'rotacion' => 'La duración de rotación seleccionada no es válida.',

        'hoja_obligatoria' => 'Elija el archivo con los datos que va a cargar.',
        'hoja_formato' => 'El archivo debe ser una hoja de cálculo en formato xlsx.',
        'hoja_pesada' => 'El archivo puede pesar como máximo 10 MB.',
    ],

];
