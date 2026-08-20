<?php

return [
    'paginacion' => [
        'pagina' => 'Página :numero',
        'etiqueta' => 'Paginación del listado',
        'anterior' => 'Anterior',
        'siguiente' => 'Siguiente',
    ],

    'enlace' => [
        'pestana_nueva' => '(se abre en una pestaña nueva)',
    ],

    'imagen' => [
        'pendiente' => 'Imagen pendiente',
    ],

    // La frase relativa sale entera del fichero, no el prefijo por un lado y el
    // número por otro: en inglés «Hace» no existe como prefijo y el orden es el
    // contrario. `formato_exacto` es el patrón de fecha del `title`, que en
    // español lleva los «de» escritos dentro.
    'fecha' => [
        'formato_exacto' => 'l j \d\e F \d\e Y, H:i',
        'segundos' => 'Hace unos segundos',
        'minuto' => 'Hace un minuto',
        'minutos' => 'Hace :cuenta minutos',
        'hora' => 'Hace una hora',
        'horas' => 'Hace :cuenta horas',
        'dia' => 'Hace un día',
        'dias' => 'Hace :cuenta días',
        'mes' => 'Hace un mes',
        'meses' => 'Hace :cuenta meses',
        'anio' => 'Hace un año',
        'anios' => 'Hace :cuenta años',
    ],

    'acciones' => [
        'menu_contenido' => 'Acciones del contenido «:titulo»',
        'menu_elemento' => 'Acciones de «:titulo»',
        'editar' => 'Editar',
        'destacar' => 'Destacar',
        'ya_destacado' => 'Ya está destacado',
        'activar' => 'Activar',
        'inactivar' => 'Inactivar',
        'mostrar' => 'Mostrar',
        'ocultar' => 'Ocultar',
        'eliminar' => 'Eliminar',
        'eliminar_confirmar' => '¿Eliminar «:titulo»? La acción no se puede deshacer.',
    ],

    'distintivos' => [
        'caducado' => 'Caducado · :fecha',
        'programado' => 'Programado · :fecha',
        'inactivo' => 'Inactivo',
        'oculto_portada' => 'Oculto en la portada',
        'oculto_listado' => 'Oculto en el listado',
    ],

    'editar' => [
        'rotulo' => 'Editar',
        'seccion' => 'Editar :seccion',
        'pendiente_titulo' => 'La edición de esta sección aún no está habilitada',
        'pendiente_nota' => '— pendiente de habilitar',
    ],

    // Separador de millares. Un listado de setecientas filas escribe «1.234» en
    // español y «1,234» en inglés; con el separador fijo, la cifra inglesa se
    // leía como un decimal.
    'numero' => [
        'millares' => '.',
    ],

    'mapa' => [
        'como_llegar' => 'Cómo llegar',
        'cerrar' => 'Cerrar la información',
        'nueva_pestana' => '(se abre en una pestaña nueva)',
        'aviso_rueda' => 'Pulsa en el mapa para acercar con la rueda',
        'ver_en_openstreetmap' => 'Ver la ubicación en OpenStreetMap',
        'acercar' => 'Acercar',
        'alejar' => 'Alejar',
        // Leaflet le pone `tabindex` al lienzo, así que se tabula hasta él y
        // hay que decir qué es y de dónde.
        'lienzo' => 'Mapa: :lugar',
        'lienzo_con_direccion' => 'Mapa: :lugar, :direccion',
    ],

    'galeria' => [
        'titulo' => 'Galería',
        'ampliar' => 'Ampliar la imagen :numero de :total',
    ],

    'participa' => [
        'rotulo' => 'Participa',
        'en' => 'en «:titulo»',
    ],

    'compartir' => [
        'rotulo' => 'Compartir',
        'correo' => 'Correo',
        'en_red' => 'Compartir en :red (se abre en una pestaña nueva)',
    ],

    'fila' => [
        'ver_mas' => 'Ver más',
        'ver_mas_sobre' => 'sobre :titulo (se abre en una pestaña nueva)',
        'ultima_modificacion' => 'Última modificación:',
        'publicacion' => 'Publicación:',
        'expedicion' => 'Expedición:',
        'modalidad' => 'Modalidad',
        'costo' => 'Costo',
        'duracion' => 'Duración',
        'duracion_valor' => 'Duración :tiempo',
    ],
];
