<?php

return [

    'titulo' => 'Portal menu',
    'subtitulo' => 'The header bar and the ☰ menu. Anything changed here shows on every page.',

    'area' => [
        'bar' => [
            'titulo' => 'Navigation bar',
            'texto' => 'The row of sections under the logo. A section with entries inside it becomes a '
                .'dropdown.',
            'tope' => 'There are :actuales sections and the bar fits :maximo on a single line. Beyond that '
                .'the header wraps onto two rows and jumps as you move between pages.',
        ],
        'mega' => [
            'titulo' => 'Full menu (☰)',
            'texto' => 'The panel opened by the three-line button. Each group is a tab with its links '
                .'beside it.',
        ],
    ],

    'sin_editar' => [
        'titulo' => 'The menu is still being served from the configuration',
        'texto' => 'There is nothing in the database yet, so the portal uses the menu written in the code '
            .'and this screen shows it read-only. Copying it changes nothing on the site: it only makes '
            .'the menu editable from here.',
        'boton' => 'Copy the menu and start editing it',
    ],

    'fila' => [
        'entradas' => '{0} no entries|{1} 1 entry|[2,*] :count entries',
        'sin_destino' => 'Grouping only',
        'oculta' => 'Hidden',
        'externo' => 'Leaves the portal',
        'sin_migrar' => 'Still on the previous portal',
    ],

    'accion' => [
        'ocultar_corto' => 'Hide',
        'mostrar_corto' => 'Show',
        'editar_corto' => 'Edit',
        'borrar_corto' => 'Delete',
        'agregar_entrada' => 'Add an entry here',
        'agregar_grupo' => 'Add a section',
        'editar' => 'Edit :rotulo',
        'borrar' => 'Delete :rotulo',
        'subir' => 'Move :rotulo up',
        'bajar' => 'Move :rotulo down',
        'ocultar' => 'Hide :rotulo',
        'mostrar' => 'Show :rotulo',
        'confirmar_borrado' => 'Delete “:rotulo”? Any entries inside it go too.',
    ],

    'form' => [
        'nueva' => 'New menu entry',
        'nueva_en' => 'New entry inside “:grupo”',
        'editar' => 'Edit “:rotulo”',
        'guardar' => 'Save',
        'cancelar' => 'Cancel',
    ],

    'campo' => [
        'rotulo' => 'Label',
        'rotulo_ayuda' => 'What the visitor reads. Write it in Spanish: the English version is handled '
            .'separately.',
        'ruta' => 'Path',
        'direccion' => 'Address',
        'estrecho' => 'Narrow the width in the bar',
        'estrecho_ayuda' => 'For long labels: wraps them over several lines instead of letting them push '
            .'the other sections aside.',
        'columnas' => 'Panel columns',
        'columnas_ayuda' => 'With many entries, three columns avoid an endless list.',
    ],

    'destino' => [
        'titulo' => 'Where it leads',
        'interno' => 'To a section of this portal',
        'interno_ayuda' => 'Start with “/”. For example, “/tema/noticias” or “/transparencia”. If the '
            .'section has not been migrated yet, the link keeps being served from the previous portal '
            .'until it is imported, and then it moves across on its own.',
        'externo' => 'To another website',
        'externo_ayuda' => 'The full address. It will open in a new tab.',
        'ninguno' => 'Nowhere: it only groups the entries inside it',
        'ninguno_ayuda' => 'Like “Documentos” or “Participa”: the label opens the dropdown and is not a '
            .'link.',
    ],

    'error' => [
        'ruta_barra' => 'The path must start with “/”.',
    ],

    'mensaje' => [
        'copiado' => 'The menu is now in the database and can be edited.',
        'creada' => '“:rotulo” was added to the menu.',
        'guardada' => '“:rotulo” was saved.',
        'borrada' => '“:rotulo” was removed from the menu.',
        'visible' => '“:rotulo” shows in the menu again.',
        'oculta' => '“:rotulo” no longer shows in the menu, but it is still stored.',
    ],

];
