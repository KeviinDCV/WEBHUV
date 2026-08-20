<?php

return [
    /*
     | Como se lee en pantalla cada categoria de contenido. El valor guardado
     | en la columna no cambia: es con el que se consulta y con el que la
     | importacion decide donde va cada cosa.
    */
    'categorias' => [
        'noticias' => 'News',
        'notificaciones_judiciales' => 'Judicial Notices',
        'comunicados' => 'Press releases',
    ],


    'acceso' => [
        'iniciada' => 'Signed in. You can now edit the portal content.',
        'cerrada' => 'Signed out successfully.',
        'credenciales' => 'These credentials do not match our records.',
        'demasiados_minutos' => 'Too many failed attempts. Please try again in :n minutes.',
        'demasiados_segundos' => 'Too many failed attempts. Please try again in :n seconds.',
    ],

    'banner' => [
        'agregado' => 'Banner added successfully.',
        'actualizado' => 'Banner updated successfully.',
        'eliminado' => 'Banner deleted.',
        'orden_guardado' => 'Changes saved.',
        'sin_sitio' => 'There are already :maximo published banners, the maximum allowed. '
            .'Delete one before adding another.',
    ],

    'bloque' => [
        'contenidos_guardado' => 'Content block saved.',
        'eventos_guardado' => 'Events block saved.',
    ],

    'contenido' => [
        'publicado' => 'Content published successfully.',
        'actualizado' => 'Content updated successfully.',
        'eliminado' => 'Content deleted.',
        'destacado' => 'Content featured.',
        'activado' => 'Content activated.',
        'inactivado' => 'Content deactivated.',
        'oculto_portada' => 'Content hidden on the home page.',
        'visible_portada' => 'Content visible on the home page.',
        'oculto_listado' => 'Content hidden in the listing.',
        'visible_listado' => 'Content visible in the listing.',
    ],

    'categoria' => [
        'creada' => 'Category created.',
    ],

    'biblioteca' => [
        'categoria_creada' => 'Category created.',
        'imagen_agregada' => 'Image added to the library.',
        'imagen_eliminada' => 'Image deleted from the library.',
    ],

    'accesos' => [
        'barra_guardada' => 'Shortcut bar saved.',
        'agregado' => 'Shortcut added.',
        'actualizado' => 'Shortcut updated.',
        'eliminado' => 'Shortcut deleted.',
        'sin_sitio' => 'This bar already has :maximo shortcuts, the maximum allowed.',
    ],

    'carga' => [
        'cargados' => ':n items loaded.',
        'descartadas' => ':n rows were left out.',
        'tope' => 'Only the first :n rows were read.',
        'fila_incompleta' => 'Row :fila: the name and the address are missing.',
        'fila_direccion' => 'Row :fila: “:url” is not a valid address.',
    ],

    'orden' => [
        'recientes' => 'Most recent',
        'az' => 'A-Z',
        'expedicion' => 'Date of issue',
        'destacados' => 'Featured',
    ],

    'moderacion' => [
        'inactivos' => 'Inactive',
        'ocultos' => 'Hidden',
    ],

    'tipo' => [
        'documento' => 'Document',
        'clasificado' => 'Classified ad',
        'link' => 'Link',
        'pregunta' => 'Question',
        'convocatoria' => 'Open call',
        'evento' => 'Event',
        'tramite' => 'Procedure',
        'noticia' => 'News item',
    ],

    'sustantivo' => [
        'documentos' => 'documents',
        'contenidos' => 'items',
    ],

    'tramite' => [
        'en_linea' => 'Online procedure',
        'presencial' => 'In-person procedure',
        'sin_costo' => 'Free of charge',
        'con_costo' => 'Subject to a fee',
        'costo_exacto' => 'Cost: :costo',
    ],

    'participacion' => [
        'ninguna' => 'Content with no participation',
        'publica' => 'Content with public participation',
        'privada' => 'Content with private participation',
    ],

    'campo' => [
        'titulo' => 'title',
        'subtitulo' => 'subtitle',
        'descripcion' => 'description',
        'resumen' => 'summary',
        'enlace' => 'link',
        'nombre' => 'name',
        'icono' => 'icon',
        'tema' => 'theme',
        'seccion' => 'section',
        'categoria' => 'category',
        'categorias' => 'categories',
        'categoria_nueva' => 'new category',
        'nombre_categoria' => 'category name',
        'nombre_bloque' => 'block name',
        'numero_secciones' => 'number of sections',
        'orden_contenidos' => 'content order',

        'imagen' => 'image',
        'imagen_fondo' => 'background image',
        'texto_descriptivo' => 'accessible description',
        'color_filtro' => 'filter color',
        'opacidad_filtro' => 'filter opacity',
        'color_titulo' => 'title color',
        'fondo_titulo' => 'title background color',
        'tipografia_titulo' => 'title typeface',
        'color_subtitulo' => 'subtitle color',
        'fondo_subtitulo' => 'subtitle background color',
        'tipografia_subtitulo' => 'subtitle typeface',
        'justificacion' => 'alignment',

        'fotos' => 'photos',
        'url_video' => 'video URL',
        'archivo' => 'file',
        'archivos' => 'files',
        'descripcion_archivo' => 'file description',

        'organizador' => 'organizer',
        'lugar' => 'location',
        'fecha_inicio' => 'start date',
        'hora_inicio' => 'start time',
        'fecha_cierre' => 'closing date',
        'fecha_expedicion' => 'date of issue',
        'fecha_publicacion' => 'publication date',
        'fecha_visualizacion' => 'display date',
        'fecha_final' => 'end date for display',

        'correo' => 'institutional email',
        'contrasena' => 'password',
    ],

    'validacion' => [
        'enlace_http' => 'The link must start with http:// or https://',
        'enlace_destino' => 'Enter the address this content leads to.',
        'url_portal' => 'Enter a full address (with http:// or https://) or a portal path '
            .'starting with “/”.',

        'imagen_pesada' => 'The image cannot be larger than 2 MB.',
        'imagen_pequena' => 'The image is too small. 3750 × 968 pixels is recommended.',
        'banner_alt' => 'The accessible description is required: without it, the banner cannot '
            .'be understood with a screen reader.',
        'imagen_alt' => 'The description is required: it will accompany the image in every piece '
            .'of content where it is used.',

        'foto_pesada' => 'Each photo can be at most 2 MB.',
        'foto_formato' => 'Only gif, jpg, jpeg, png, bmp or webp images are accepted.',
        'foto_alt' => 'Each photo needs its description: without it, the photo cannot be '
            .'understood with a screen reader.',
        'video_youtube' => 'The video must be a YouTube address.',

        'archivo_obligatorio' => 'Attach the file or provide a link where it can be consulted.',
        'archivo_pesado' => 'The file can be at most 30 MB.',
        'archivos_pesados' => 'Each file can be at most 30 MB.',
        'archivos_maximo' => 'At most 20 files can be attached at once.',
        'archivo_formatos' => 'Accepted formats: pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt and zip.',

        'fecha_final_posterior' => 'The end date must be later than the publication date.',
        'cierre_posterior' => 'The closing date cannot come before the opening date.',

        'categoria_ajena' => 'That category does not belong to this topic.',
        'categoria_repetida' => 'A category with that name already exists.',

        'seccion_bloque' => 'Choose the section that feeds the block.',
        'titulo_seccion' => 'Each section needs the title it is shown with.',
        'seccion_calendario' => 'Choose the section that feeds the calendar.',
        'rotacion' => 'The selected rotation duration is not valid.',

        'hoja_obligatoria' => 'Choose the file with the data you are going to upload.',
        'hoja_formato' => 'The file must be a spreadsheet in xlsx format.',
        'hoja_pesada' => 'The file can be at most 10 MB.',
    ],

];
