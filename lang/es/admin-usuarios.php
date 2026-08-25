<?php

return [

    'titulo' => 'Cuentas del portal',
    'subtitulo' => 'Quién puede entrar a editar y con qué permiso.',

    // ---------------- Los dos roles ----------------
    //
    // Las claves son los valores guardados en la columna `role`, así que
    // App\Models\User::roleLabel() compone la clave con el rol y sale sola.

    'rol' => [
        'operador' => 'Operador',
        'administrador' => 'Administrador',
    ],

    'rol_explicado' => [
        'operador' => 'Edita el portal: noticias, documentos, temas, banners y el menú.',
        'administrador' => 'Todo lo del operador y además la herramienta: dar de alta cuentas y '
            .'consultar las estadísticas de uso.',
    ],

    // ---------------- Listado ----------------

    'listado' => [
        'nueva' => 'Crear una cuenta',
        'columna_nombre' => 'Nombre',
        'columna_correo' => 'Correo',
        'columna_rol' => 'Permiso',
        'usted' => 'usted',
        'sin_edicion' => 'Las cuentas todavía no se editan ni se dan de baja desde aquí; para eso, '
            .'hable con la Oficina de Sistemas.',
    ],

    // ---------------- Formulario ----------------

    'form' => [
        'titulo' => 'Crear una cuenta',
        'guardar' => 'Crear la cuenta',
        'cancelar' => 'Cancelar',
    ],

    'campo' => [
        'nombre' => 'Nombre completo',
        'correo' => 'Correo institucional',
        'contrasena' => 'Contraseña',
        'contrasena_ayuda' => 'Mínimo doce caracteres, con letras, números y símbolos.',
        'contrasena_repetir' => 'Repita la contraseña',
        'rol' => 'Permiso',
    ],

    'aviso' => [
        'contrasena' => 'Escríbala usted y entréguesela a la persona por un medio que no sea el correo; '
            .'después no se puede volver a ver, solo cambiar.',
    ],

    'error' => [
        'correo_repetido' => 'Ya existe una cuenta con ese correo.',
    ],

    'mensaje' => [
        'creada' => 'Se creó la cuenta de :nombre.',
    ],

];
