<?php

return [

    'titulo' => 'Portal accounts',
    'subtitulo' => 'Who can sign in to edit, and with which permission.',

    'rol' => [
        'operador' => 'Operator',
        'administrador' => 'Administrator',
    ],

    'rol_explicado' => [
        'operador' => 'Edits the portal: news, documents, topics, banners and the menu.',
        'administrador' => 'Everything an operator can do, plus the tool itself: creating accounts and '
            .'consulting the usage statistics.',
    ],

    'listado' => [
        'nueva' => 'Create an account',
        'columna_nombre' => 'Name',
        'columna_correo' => 'Email',
        'columna_rol' => 'Permission',
        'usted' => 'you',
        'sin_edicion' => 'Accounts cannot yet be edited or removed from here; for that, talk to the '
            .'hospital IT office.',
    ],

    'form' => [
        'titulo' => 'Create an account',
        'guardar' => 'Create the account',
        'cancelar' => 'Cancel',
    ],

    'campo' => [
        'nombre' => 'Full name',
        'correo' => 'Institutional email',
        'contrasena' => 'Password',
        'contrasena_ayuda' => 'At least twelve characters, with letters, numbers and symbols.',
        'contrasena_repetir' => 'Repeat the password',
        'rol' => 'Permission',
    ],

    'aviso' => [
        'contrasena' => 'Type it yourself and hand it over by some means other than email; afterwards it '
            .'cannot be read again, only changed.',
    ],

    'error' => [
        'correo_repetido' => 'An account with that email already exists.',
    ],

    'mensaje' => [
        'creada' => ':nombre’s account was created.',
    ],

];
