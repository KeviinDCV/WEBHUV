<?php

return [

    'titulo' => 'Menú del portal',
    'subtitulo' => 'La barra de la cabecera y el menú del botón ☰. Lo que se cambie aquí se ve en todas las páginas.',

    // ---------------- Las dos áreas ----------------

    'area' => [
        'bar' => [
            'titulo' => 'Barra de navegación',
            'texto' => 'La fila de secciones que va bajo el logotipo. Una sección con entradas dentro '
                .'se muestra como desplegable.',
            'tope' => 'Hay :actuales secciones y en la barra caben :maximo en una sola línea. Con más, la '
                .'cabecera se parte en dos renglones y da un salto al cambiar de página.',
        ],
        'mega' => [
            'titulo' => 'Menú completo (☰)',
            'texto' => 'El panel que abre el botón de las tres rayas. Cada grupo es una pestaña con sus '
                .'enlaces al lado.',
        ],
    ],

    // ---------------- Todavía sin editar ----------------

    'sin_editar' => [
        'titulo' => 'El menú se está sirviendo de la configuración',
        'texto' => 'Todavía no hay nada en la base de datos, así que el portal usa el menú escrito en el '
            .'código y esta pantalla lo enseña sin poder tocarlo. Al copiarlo se queda exactamente igual: '
            .'lo que cambia es que a partir de ahí se puede editar desde aquí.',
        'boton' => 'Copiar el menú y empezar a editarlo',
    ],

    // ---------------- Filas ----------------

    'fila' => [
        'entradas' => '{0} sin entradas|{1} 1 entrada|[2,*] :count entradas',
        'sin_destino' => 'Solo agrupa',
        'oculta' => 'Oculta',
        'externo' => 'Sale del portal',
        'sin_migrar' => 'Todavía en el portal anterior',
    ],

    // ---------------- Acciones ----------------

    'accion' => [
        'ocultar_corto' => 'Ocultar',
        'mostrar_corto' => 'Mostrar',
        'editar_corto' => 'Editar',
        'borrar_corto' => 'Borrar',
        'agregar_entrada' => 'Añadir una entrada aquí',
        'agregar_grupo' => 'Añadir una sección',
        'editar' => 'Editar :rotulo',
        'borrar' => 'Borrar :rotulo',
        'subir' => 'Subir :rotulo',
        'bajar' => 'Bajar :rotulo',
        'ocultar' => 'Ocultar :rotulo',
        'mostrar' => 'Mostrar :rotulo',
        'confirmar_borrado' => '¿Borrar «:rotulo»? Si tiene entradas dentro, se van con ella.',
    ],

    // ---------------- Formulario ----------------

    'form' => [
        'nueva' => 'Nueva entrada del menú',
        'nueva_en' => 'Nueva entrada dentro de «:grupo»',
        'editar' => 'Editar «:rotulo»',
        'guardar' => 'Guardar',
        'cancelar' => 'Cancelar',
    ],

    'campo' => [
        'rotulo' => 'Rótulo',
        'rotulo_ayuda' => 'Lo que lee el visitante. Escríbalo en español: la traducción al inglés se '
            .'gestiona aparte.',
        'ruta' => 'Ruta',
        'direccion' => 'Dirección',
        'estrecho' => 'Recortar el ancho en la barra',
        'estrecho_ayuda' => 'Para rótulos largos: los parte en varias líneas en vez de dejar que empujen '
            .'a las demás secciones.',
        'columnas' => 'Columnas del panel',
        'columnas_ayuda' => 'Con muchas entradas, tres columnas evitan una lista interminable.',
    ],

    'destino' => [
        'titulo' => 'A dónde lleva',
        'interno' => 'A una sección de este portal',
        'interno_ayuda' => 'Empiece por «/». Por ejemplo, «/tema/noticias» o «/transparencia». Si la '
            .'sección todavía no se ha migrado, el enlace sigue sirviéndose del portal anterior hasta que '
            .'se importe, y entonces se muda solo.',
        'externo' => 'A otro sitio web',
        'externo_ayuda' => 'La dirección completa. Se abrirá en una pestaña nueva.',
        'ninguno' => 'A ningún sitio: solo agrupa a las de dentro',
        'ninguno_ayuda' => 'Como «Documentos» o «Participa»: el rótulo abre el desplegable y no es un '
            .'enlace.',
    ],

    'error' => [
        'ruta_barra' => 'La ruta tiene que empezar por «/».',
    ],

    // ---------------- Mensajes ----------------

    'mensaje' => [
        'copiado' => 'El menú ya está en la base de datos y se puede editar.',
        'creada' => 'Se añadió «:rotulo» al menú.',
        'guardada' => 'Se guardó «:rotulo».',
        'borrada' => 'Se borró «:rotulo» del menú.',
        'visible' => '«:rotulo» vuelve a verse en el menú.',
        'oculta' => '«:rotulo» ya no se ve en el menú, pero sigue guardada.',
    ],

];
