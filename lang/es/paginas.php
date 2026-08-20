<?php

return [

    // ---------------- Común a todas las páginas ----------------

    'ruta' => [
        'etiqueta' => 'Ruta de navegación',
        'inicio' => 'Inicio',
    ],

    'enlace' => [
        'pestana_nueva' => '(se abre en una pestaña nueva)',
    ],

    // ---------------- Listados de tema ----------------

    'listado' => [
        'categorias' => [
            'filtro' => 'Filtrar por categoría',
            'todas' => 'Todas las categorías',
            'ver_mas' => 'Ver más',
            'ver_menos' => 'Ver menos',
        ],

        'busqueda' => [
            'etiqueta' => 'Busca en :tema',
            'boton' => 'Buscar',
        ],

        'orden' => [
            'etiqueta' => 'Ordenar por:',
            'recientes' => 'Recientes',
            'az' => 'A-Z',
        ],

        'tipo' => [
            'etiqueta' => 'Filtrar por tipo de contenido',
            'todos' => 'Todos los contenidos',
        ],

        'periodo' => [
            'etiqueta' => 'Filtrar por fecha',
            'mes' => 'Último mes',
            'semestre' => 'Últimos seis meses',
            'ano' => 'Último año',
            'trienio' => 'Últimos tres años',
        ],

        'vista' => [
            'etiqueta' => 'Forma de ver el listado',
            'cuadricula' => 'Ver en cuadrícula',
            'lista' => 'Ver en lista',
        ],

        'nuevo' => 'Nuevo contenido',
        'ocultar' => 'Ocultar',
        'carga_masiva' => 'Carga masiva',
        'vacio' => 'Todavía no hay contenidos publicados en :tema.',
        'sin_resultados' => 'No hay contenidos que coincidan con la búsqueda.',
        'quitar_filtros' => 'Quitar los filtros',
        'cargar_mas' => 'Cargar más contenidos',

        // :contenidos es el sustantivo del tema («contenidos», «documentos»),
        // que decide el modelo y no se traduce aquí.
        'mostrando' => 'Mostrando :visibles de :total :contenidos',
        'mostrando_pagina' => 'Mostrando :desde–:hasta de :total contenidos',
    ],

    'tema' => [
        'descripcion' => 'Contenidos de :tema del :entidad.',
        'descripcion_noticias' => 'Noticias del :entidad.',
        'descripcion_enlaces' => ':tema del :entidad.',
    ],

    // ---------------- Fichas ----------------

    'ficha' => [
        'modificacion' => 'Modificación:',
        'creacion' => 'Creación:',
        'editar' => 'Editar',
        'expedicion' => 'Fecha de expedición:',
        'archivos' => 'Archivos para descargar',
        'expediente' => 'Consultar el expediente completo',
        'relacionados' => 'También en :contexto',

        'evento' => [
            'cuando' => 'Cuándo',
            'donde' => 'Dónde',
            'organiza' => 'Organiza',

            // Formato de Carbon, no texto: en español el día y el mes van unidos
            // por «de», y esa palabra no puede quedarse en la plantilla inglesa.
            'formato' => 'l j \d\e F \d\e Y, H:i',
        ],
    ],

    // ---------------- Mecanismos de contacto ----------------

    'contacto' => [
        'titulo' => 'Mecanismos de contacto',
        'descripcion' => 'Dirección, teléfonos, correos y horario de atención del :entidad.',

        'mecanismos' => [
            'direccion' => 'Dirección',
            'conmutador' => 'Teléfono',
            'linea_gratuita' => 'Línea de atención gratuita',
            'correo' => 'Email',
            'correo_judicial' => 'Notificaciones Judiciales',
            'horario' => 'Horario de atención',
        ],

        'formulario' => 'Formulario electrónico de solicitudes, peticiones, quejas, reclamos y denuncias',
    ],

    // ---------------- Sucursales ----------------

    'sucursales' => [
        'titulo' => 'Sucursales',
        'descripcion' => 'Sedes del :entidad.',
    ],

    // ---------------- Políticas ----------------

    'politicas' => [
        'titulo' => 'Políticas',
        'descripcion' => 'Política de derechos de autor y autorización de uso de contenidos del :entidad.',

        'derechos' => [
            'titulo' => 'Política de derechos de autor y autorización de uso de contenidos',
            'texto' => 'El :entidad establece que todos los contenidos producidos o administrados en el '
                .'marco de su actividad institucional constituyen activos estratégicos cuya creación, uso, '
                .'divulgación y explotación deben someterse a principios de responsabilidad, legalidad, '
                .'seguridad y protección institucional. En consecuencia, los derechos patrimoniales '
                .'derivados de los contenidos generados por servidores públicos, contratistas, docentes, '
                .'residentes o estudiantes en el ejercicio de sus funciones pertenecen al hospital, sin '
                .'perjuicio del reconocimiento de los derechos morales de autor. Ningún contenido '
                .'institucional podrá ser reproducido, publicado, transformado, licenciado, divulgado por '
                .'cualquier medio o puesto a disposición del público sin la autorización expresa, previa y '
                .'escrita de las dependencias facultadas para ello. El manejo del contenido deberá '
                .'responder a estándares de seguridad de la información, calidad del dato, gestión '
                .'documental y protección del dato personal y clínico, asegurando la trazabilidad, '
                .'conservación y uso ético del conocimiento generado dentro de la institución.',
        ],

        'plataforma' => [
            'intro' => 'A continuación podrás consultar los términos y condiciones y las políticas de '
                .'privacidad de información y el tratamiento de datos personales de la solución que debes '
                .'tener en cuenta para el uso correcto del servicio de portales territoriales ofrecidos '
                .'por el Gobierno Digital; recuerda dar clic en los títulos para conocer más:',
        ],
    ],

    // ---------------- Transparencia ----------------

    'transparencia' => [
        'titulo' => 'Transparencia',
        'descripcion' => 'Índice de transparencia y acceso a la información pública del :entidad, '
            .'según la Resolución 1519 de 2020.',
    ],

    // ---------------- PQRDS ----------------

    'pqrds' => [
        'titulo' => 'PQRDS Recepción de Solicitudes',
        'descripcion' => 'Presente peticiones, quejas, reclamos, sugerencias, denuncias y solicitudes de '
            .'información ante el :entidad.',
        'encabezado' => 'Realizar peticiones, quejas, reclamos, sugerencias y denuncias (PQRDS)',
        'entradilla' => 'Por favor tenga en cuenta las siguientes definiciones para establecer el tipo de '
            .'solicitud a presentar y los términos de respuesta.',
        'seleccion' => 'Seleccione el tipo de solicitud que desea registrar',

        // El texto en español es el del portal palabra por palabra, erratas
        // incluidas: son definiciones legales que la entidad publica así.
        'tramites' => [
            'peticion' => [
                'titulo' => 'Petición',
                'definicion' => 'Es el derecho fundamental que tiene toda persona a presentar solicitudes '
                    .'respetuosas a las autoridades por motivos de interés general o particular y a obtener '
                    .'su pronta resolución.',
                'boton' => 'Envía una petición o un derecho de petición',
            ],
            'queja' => [
                'titulo' => 'Queja',
                'definicion' => 'Es la manifestación de protesta, censura, descontento o inconformidad que '
                    .'formula una persona en relación con una conducta que considera irregular de uno o '
                    .'varios servidores públicos en desarrollo de sus funciones.',
                'boton' => 'Envía una queja',
            ],
            'reclamo' => [
                'titulo' => 'Reclamo',
                'definicion' => 'Es el derecho que tiene toda persona de exigir, reivindicar o demandar una '
                    .'solución, ya sea por motivo general o particular, referente a la presentación indebida '
                    .'de un servicio o a la falta de atención de una solicitud.',
                'boton' => 'Envía un reclamo',
            ],
            'sugerencia' => [
                'titulo' => 'Sugerencia',
                'definicion' => 'Es la manifestación de una idea o propuesta para mejorar el servicio o la '
                    .'gestión de la entidad.',
                'boton' => 'Envía una sugerencia',
            ],
            'felicitacion' => [
                'titulo' => 'Felicitación',
                'definicion' => 'Es la manifestación de la alegría y satisfacción de un servicio brindado o '
                    .'la gestión de la entidad.',
                'boton' => 'Envía una felicitación',
            ],
            'denuncia' => [
                'titulo' => 'Denuncia',
                'definicion' => 'Es la puesta en conocimiento ante una autoridad competente de una conducta '
                    .'posiblemente irregular, para que se adelante la correspondiente investigación penal, '
                    .'disciplinaria, fiscal, administrativa - sancionatoria o ético profesional.',
                'boton' => 'Envía una denuncia',
            ],
            'solicitud_informacion' => [
                'titulo' => 'Solicitud de información',
                'definicion' => 'Petición formulada para acceder a información pública, sin necesidad de que '
                    .'los solicitantes acrediten su personalidad, el tipo de interés, las causas por las '
                    .'cuales presentan su solicitud o los fines a los cuales habrán de destinar los datos '
                    .'solicitados.',
                'boton' => 'Solicita información',
            ],
            'solicitud_datos' => [
                'titulo' => 'Solicitud de datos personales',
                'definicion' => 'Es la solicitud de cambio y/o eliminación de información correspondiente a '
                    .'los datos personales del usuario que requieran correcciones o actualizaciones.',
                'boton' => 'Envía una solicitud',
            ],
            'cita' => [
                'titulo' => 'Agenda tu cita',
                'definicion' => 'Reunión de tipo presencial o virtual en caso de tener necesidad de realizar '
                    .'un trámite.',
                'boton' => 'Agendar cita',
            ],
        ],

        'seguimiento' => [
            'texto' => 'Hazle seguimiento a tu solicitud a través del código generado por el portal cuando '
                .'llenas el respectivo formulario y envías tu solicitud.',
            'boton' => 'Hacer seguimiento',
        ],
    ],

    // ---------------- Acceso ----------------

    'acceso' => [
        'titulo' => 'Iniciar sesión',
        'descripcion' => 'Acceso al portal para el personal del Hospital Universitario del Valle.',
        'entradilla' => 'Acceso para el personal del hospital. Desde aquí se administra el contenido '
            .'publicado en el portal.',
        'error' => 'No fue posible iniciar sesión',
        'correo' => 'Correo institucional',
        'contrasena' => 'Contraseña',
        'recordar' => 'Mantener la sesión iniciada',
        'entrar' => 'Entrar',
        'sin_cuenta' => '¿No tiene cuenta? El portal no permite registro público. Solicite el acceso a la '
            .'Oficina de Sistemas del hospital.',
    ],

];
