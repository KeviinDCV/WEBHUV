<?php

/*
|--------------------------------------------------------------------------
| Contenido institucional — WEB Huv
|--------------------------------------------------------------------------
|
| Datos fijos del Hospital Universitario del Valle «Evaristo García» E.S.E.
| Se centralizan aquí para que las vistas queden libres de texto quemado y
| para poder migrar cualquier bloque a base de datos sin tocar el Blade.
|
| Los enlaces aún no implementados apuntan a '#'. Al crear cada página basta
| con reemplazar el valor por route('...') o url('...').
|
*/

return [

    'institution' => [
        'name' => 'Hospital Universitario del Valle «Evaristo García» E.S.E.',
        // Variante sin comillas angulares, tal como firma el pie institucional.
        'name_plain' => 'Hospital Universitario del Valle Evaristo García E.S.E.',
        'short_name' => 'HUV',
        'legal_form' => 'Empresa Social del Estado',
        'oversight' => 'Secretaría de Salud del Valle del Cauca',
        'nit' => '890.303.461-0',
        'founded_year' => 1956,
        'address' => 'Calle 5 # 36-08',
        'city' => 'Santiago de Cali',
        'state' => 'Valle del Cauca',
        'country' => 'Colombia',
        'postal_code' => '760043',
        'switchboard' => '(602) 620 6000',
        'switchboard_tel' => '+576026206000',
        'user_service' => '(602) 620 6275',
        'user_service_tel' => '+576026206275',
        'email' => 'pqrsf@correohuv.gov.co',
        'legal_email' => 'notificacionesjudiciales@correohuv.gov.co',
        'schedule' => 'Lunes a Viernes de 7:00 A.M. a 12:00 M y 1:00 P.M. a 5:30 P.M.',
        'closed_days' => 'Días no laborales: sábados, domingos y festivos. Urgencias: 24 horas.',
    ],

    'seo' => [
        'title' => 'Hospital Universitario del Valle «Evaristo García» E.S.E.',
        'description' => 'Institución de salud pública de alta complejidad del suroccidente colombiano. '
            .'Trámites, servicios, noticias y transparencia del Hospital Universitario del Valle E.S.E.',
        'keywords' => 'HUV, Hospital Universitario del Valle, Evaristo García, salud, Cali, Valle del Cauca, alta complejidad',
    ],

    /*
    |--------------------------------------------------------------------------
    | Menú principal
    |--------------------------------------------------------------------------
    | Cada ítem admite: label, url, key (para desplegables), children.
    */
    'nav' => [
        [
            'label' => 'Inicio',
            'url' => '/',
            'active' => true,
        ],
        [
            'label' => 'Transparencia y acceso a la información pública',
            'url' => '#transparencia',
            'narrow' => true,
        ],
        [
            'label' => 'Atención y Servicios a la ciudadanía',
            'key' => 'atencion',
            'narrow' => true,
            'children' => [
                ['label' => 'Política y protección de datos', 'url' => '#'],
                ['label' => 'PQRDS Recepción de Solicitudes', 'url' => '#'],
                ['label' => 'Mecanismos de contacto', 'url' => '#'],
                ['label' => 'Trámites y servicios', 'url' => '#'],
                ['label' => 'Consulta resultados laboratorio', 'url' => '#'],
                ['label' => 'Citas', 'url' => '#'],
                ['label' => 'Servicios', 'url' => '#'],
                ['label' => 'Programas', 'url' => '#'],
                ['label' => 'Pagos en línea', 'url' => '#'],
                ['label' => 'Denuncias por posibles actos de corrupción', 'url' => '#'],
                ['label' => 'Centro Integral de Atención al Usuario - CIAU', 'url' => '#'],
                ['label' => 'Voluntariados', 'url' => '#'],
                ['label' => 'Encuestas de satisfacción', 'url' => '#'],
                ['label' => 'Herramienta Tic PCD', 'url' => '#'],
                ['label' => 'Oficina Coordinadora Académica', 'url' => '#'],
                ['label' => 'Comité de Ética en Investigaciones Hospitalarias', 'url' => '#'],
            ],
        ],
        [
            'label' => 'Participa',
            'key' => 'participa',
            'children' => [
                ['label' => 'Diagnóstico e Identificación de problemas', 'url' => '#'],
                ['label' => 'Planeación y presupuesto participativo', 'url' => '#'],
                ['label' => 'Consulta ciudadana', 'url' => '#'],
                ['label' => 'Colaboración e innovación', 'url' => '#'],
                ['label' => 'Rendición de cuentas', 'url' => '#'],
                ['label' => 'Control ciudadano', 'url' => '#'],
                ['label' => 'Descripción Participa', 'url' => '#'],
            ],
        ],
        [
            'label' => 'Noticias',
            'url' => '#noticias',
        ],
        [
            'label' => 'Normatividad',
            'url' => '#',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Megamenú (botón ☰)
    |--------------------------------------------------------------------------
    */
    'mega_menu' => [
        [
            'title' => 'La entidad',
            'links' => [
                ['label' => 'Misión y visión', 'url' => '#'],
                ['label' => 'Quiénes somos', 'url' => '#'],
                ['label' => 'Reseña histórica', 'url' => '#'],
                ['label' => 'Organigrama', 'url' => '#'],
                ['label' => 'Directorio institucional', 'url' => '#'],
                ['label' => 'Junta Directiva', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Servicios',
            'links' => [
                ['label' => 'Portafolio de servicios', 'url' => '#'],
                ['label' => 'Urgencias', 'url' => '#'],
                ['label' => 'Consulta externa', 'url' => '#'],
                ['label' => 'Banco de sangre', 'url' => '#'],
                ['label' => 'Banco de leche humana', 'url' => '#'],
                ['label' => 'HUV Internacional', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Transparencia',
            'links' => [
                ['label' => 'Normativa', 'url' => '#'],
                ['label' => 'Contratación', 'url' => '#'],
                ['label' => 'Planeación, presupuesto e informes', 'url' => '#'],
                ['label' => 'Datos abiertos', 'url' => '#'],
                ['label' => 'Talento humano', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Docencia e investigación',
            'links' => [
                ['label' => 'Convenios docencia–servicio', 'url' => '#'],
                ['label' => 'Comité de ética en investigación', 'url' => '#'],
                ['label' => 'Rotaciones y prácticas', 'url' => '#'],
                ['label' => 'Biblioteca', 'url' => '#'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Banner principal
    |--------------------------------------------------------------------------
    | 'image' => ruta dentro de public/ (o null para mostrar el marcador).
    */
    'hero' => [
        'autoplay' => true,
        'seconds' => 7,
        'slides' => [
            [
                'type' => 'award',
                'image' => null,
                'image_hint' => 'Banner 1 — 500 empresas más exitosas del Valle (1900×470)',
                'overline' => 'Orgullosos de estar entre:',
                'headline' => 'LAS 34 EMPRESAS MÁS EXITOSAS',
                'subline' => 'del Valle del Cauca',
                'rank_number' => '500',
                'rank_title' => 'EMPRESAS+',
                'rank_subtitle' => 'EXITOSAS DEL VALLE',
                'rank_badge' => 'Y LAS 200 SIGUIENTES',
                'intro' => 'Nos complace anunciar que estamos en el',
                'highlight' => 'PUESTO 8 ENTRE LAS EMPRESAS CON MÁS GANANCIAS',
                'year' => 'EN EL 2025',
                'tags' => [
                    'Acreditación en Salud',
                    'ACHC',
                    'Hospitales Verdes y Saludables',
                    'OES',
                    'Hospital Universitario',
                    'Buenas Prácticas',
                ],
                'source' => 'Fuente: El País',
            ],
            [
                'type' => 'standard',
                'theme' => 'dark',
                'image' => null,
                'image_hint' => 'Banner 2 — 70 años HUV (1900×470)',
                'eyebrow' => '70 años',
                'title' => '70 años de liderazgo en salud pública',
                'text' => 'Integrando servicios de alta complejidad, tecnología de vanguardia y el rigor '
                    .'científico de nuestros especialistas al servicio del Valle del Cauca.',
            ],
            [
                'type' => 'standard',
                'theme' => 'light',
                'image' => null,
                'image_hint' => 'Banner 3 — banner institucional (1900×470)',
                'eyebrow' => 'Alta complejidad',
                'title' => 'La institución de salud pública más grande del suroccidente colombiano',
                'text' => 'Entidad pública descentralizada del orden departamental, adscrita a la '
                    .'Secretaría de Salud del Valle del Cauca.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Accesos rápidos
    |--------------------------------------------------------------------------
    */
    'quick_access' => [
        'title' => 'Atención y Servicios a la ciudadanía',
        'subtitle' => 'Trámites y canales de atención más consultados.',
        'items' => [
            [
                'title' => 'Asignación de citas',
                'text' => 'Solicita, consulta o cancela tu cita de consulta externa.',
                'cta' => 'Ir al trámite',
                'url' => '#',
            ],
            [
                'title' => 'PQRSD',
                'text' => 'Peticiones, quejas, reclamos, sugerencias y denuncias.',
                'cta' => 'Ir al trámite',
                'url' => '#',
            ],
            [
                'title' => 'Copia de historia clínica',
                'text' => 'Solicitud de copia para el paciente o su representante legal.',
                'cta' => 'Ir al trámite',
                'url' => '#',
            ],
            [
                'title' => 'Portafolio de servicios',
                'text' => 'Servicios habilitados de mediana y alta complejidad.',
                'cta' => 'Consultar',
                'url' => '#',
            ],
            [
                'title' => 'Banco de Sangre',
                'text' => 'Requisitos, horarios y puntos de donación.',
                'cta' => 'Consultar',
                'url' => '#',
            ],
            [
                'title' => 'Notificaciones judiciales',
                'text' => 'Buzón oficial para notificaciones y comunicaciones judiciales.',
                'cta' => 'Ir al buzón',
                'url' => '#',
            ],
            [
                'title' => 'Contratación',
                'text' => 'Procesos, invitaciones públicas y estudios previos.',
                'cta' => 'Consultar',
                'url' => '#',
            ],
            [
                'title' => 'Convocatorias de empleo',
                'text' => 'Ofertas laborales y procesos de selección vigentes.',
                'cta' => 'Consultar',
                'url' => '#',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Franja de líneas de atención
    |--------------------------------------------------------------------------
    */
    'contact_strip' => [
        [
            'label' => 'Conmutador',
            'value' => '(602) 620 6000',
            'tel' => '+576026206000',
        ],
        [
            'label' => 'Atención al usuario',
            'value' => '(602) 620 6275',
            'tel' => '+576026206275',
        ],
        [
            'label' => 'Urgencias',
            'value' => 'Atención 24 horas',
        ],
        [
            'label' => 'Sede principal',
            'value' => 'Calle 5 # 36-08',
            'value_extra' => 'Santiago de Cali, Valle del Cauca',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bloque «Nuestra entidad»
    |--------------------------------------------------------------------------
    */
    'entity' => [
        'eyebrow' => 'Nuestra entidad',
        'title' => 'Hospital Universitario del Valle «Evaristo García» E.S.E.',
        'paragraphs' => [
            'El Hospital es una entidad pública (Empresa Social del Estado) descentralizada del orden '
                .'Departamental adscrita a la Secretaría de Salud del Valle del Cauca, presta servicios de '
                .'salud con énfasis en la atención del paciente de alta complejidad, y es una de las '
                .'instituciones de salud más grandes e importantes del suroccidente colombiano.',
            'Como Hospital Universitario, participa en la formación, desarrollo y actualización del Talento '
                .'Humano en la modalidad formal y no formal en el marco de los convenios docencia-servicio, '
                .'con instituciones educativas nacionales e internacionales.',
        ],
        'cards' => [
            [
                'title' => 'Misión',
                'text' => 'El Hospital Universitario del Valle «Evaristo García» E.S.E. tiene como objetivo '
                    .'brindar servicios de salud de mediana y alta complejidad a la población que lo requiera '
                    .'a través de un talento humano competente y comprometido.',
            ],
            [
                'title' => 'Naturaleza jurídica',
                'text' => 'Mediante el Decreto Departamental N.° 1807 del 7 de noviembre de 1995 el Hospital '
                    .'se transforma en Empresa Social del Estado, en cumplimiento de los artículos 194 y 197 '
                    .'de la Ley 100 de 1993.',
            ],
        ],
        'actions' => [
            ['label' => 'Conoce la entidad', 'url' => '#', 'variant' => 'primary'],
            ['label' => 'Reseña histórica', 'url' => '#', 'variant' => 'ghost'],
        ],
        'image' => null,
        'image_hint' => 'Fachada del HUV (800×600)',
        'stats' => [
            ['value' => '70', 'label' => 'años de servicio', 'label_extra' => 'desde 1956'],
            ['value' => '24/7', 'label' => 'urgencias y', 'label_extra' => 'alta complejidad'],
            ['value' => 'ESE', 'label' => 'Empresa Social', 'label_extra' => 'del Estado'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Noticias destacadas
    |--------------------------------------------------------------------------
    */
    'news' => [
        'title' => 'Noticias',
        'subtitle' => 'Comunicados y actualidad institucional.',
        'all_url' => '#',
        'items' => [
            [
                'category' => 'Institucional',
                'date' => 'Noviembre de 2015',
                'datetime' => '2015-11-01',
                'title' => 'El Hospital Universitario del Valle «Evaristo García» E.S.E. contará con una '
                    .'nueva sede principal en el municipio de Cartago, Valle del Cauca',
                'excerpt' => 'De acuerdo al Programa de Gobierno de la Gobernación del Valle del Cauca, que '
                    .'consiste en descentralizar los servicios de salud en el Departamento.',
                'url' => '#',
                'image' => null,
                'image_hint' => 'Foto noticia 1 (720×405)',
            ],
            [
                'category' => 'Reconocimientos',
                'date' => '2025',
                'datetime' => '2025-01-01',
                'title' => 'El HUV entre las 34 empresas más exitosas del Valle del Cauca y en el puesto 8 '
                    .'entre las empresas con más ganancias en el 2025',
                'excerpt' => 'Ranking «500 Empresas + Exitosas del Valle y las 200 siguientes». Fuente: El País.',
                'url' => '#',
                'image' => null,
                'image_hint' => 'Foto noticia 2 (720×405)',
            ],
            [
                'category' => 'Banco de Sangre',
                'date' => 'Permanente',
                'datetime' => null,
                'title' => 'Donar sangre en el HUV: requisitos, horarios y puntos de atención',
                'excerpt' => 'El Banco de Sangre del Hospital Universitario del Valle atiende la demanda de '
                    .'componentes sanguíneos del suroccidente del país.',
                'url' => '#',
                'image' => null,
                'image_hint' => 'Foto noticia 3 (720×405)',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Servicios y especialidades
    |--------------------------------------------------------------------------
    */
    'services' => [
        'title' => 'Servicios y especialidades',
        'subtitle' => 'Servicios de mediana y alta complejidad habilitados en la institución.',
        'items' => [
            'Pediatría',
            'Medicina Interna',
            'UCI',
            'Alta Complejidad',
            'Ortopedia',
            'Oncología',
            'Obstetricia',
            'Neurología',
            'Cardiología',
            'Medicina Física',
            'Salud Mental',
            'Clínica del Dolor',
            'Medicina Familiar',
            'Hemato-oncología',
            'Banco de Sangre',
            'Radiología',
            'Banco de Leche',
            'Psiquiatría',
            'Dermatología',
            'Oftalmología',
            'Otorrinolaringología',
            'Quimioterapia',
            'Laboratorio Clínico',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transparencia (Ley 1712 de 2014 / Resolución 1519 de 2020)
    |--------------------------------------------------------------------------
    */
    'transparency' => [
        'title' => 'Transparencia y acceso a la información pública',
        'subtitle' => 'Ley 1712 de 2014 y Resolución 1519 de 2020 del MinTIC.',
        'items' => [
            'Información de la entidad',
            'Normativa',
            'Contratación',
            'Planeación, presupuesto e informes',
            'Trámites',
            'Participa',
            'Datos abiertos',
            'Información específica para grupos de interés',
            'Obligación de reporte de información específica',
            'Atención y servicios a la ciudadanía',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pie de página
    |--------------------------------------------------------------------------
    */
    'footer' => [

        // Datos de contacto tal como aparecen en el pie institucional.
        'contact' => [
            [
                'label' => 'Dirección',
                'value' => 'Cl. 5 # 36-08 Santiago de Cali. Valle del Cauca, Colombia',
            ],
            [
                'label' => 'Horario de atención',
                'value' => 'Lunes a Viernes de 7:00 A.M. a 12:00 M y 1:00 P.M. a 5:30 P.M.',
            ],
            [
                'label' => 'Teléfono Conmutador',
                'value' => '(57+2) 6206000 Ext. 1001',
                'tel' => '+5726206000,1001',
            ],
            [
                'label' => 'Línea de atención gratuita',
                'value' => '(57+2) 6206000 Ext: 1218 / 1216',
                'tel' => '+5726206000,1218',
            ],
            [
                'label' => 'Línea anticorrupción',
                'value' => '(57+2) 6206000 Ext: 1043',
                'tel' => '+5726206000,1043',
            ],
            [
                'label' => 'Correo institucional',
                'value' => 'pqrsf@correohuv.gov.co',
                'mailto' => 'pqrsf@correohuv.gov.co',
            ],
            [
                'label' => 'Correo de notificaciones judiciales',
                'value' => 'notificacionesjudiciales@correohuv.gov.co',
                'mailto' => 'notificacionesjudiciales@correohuv.gov.co',
            ],
        ],

        /*
         | Reloj de la hora legal de la República de Colombia.
         |
         | El widget original del hospital incrusta el servicio del Instituto
         | Nacional de Metrología. Aquí se renderiza en local: el servidor
         | entrega la hora y el navegador solo la hace avanzar, de modo que un
         | reloj mal ajustado en el equipo del visitante no la altera.
         |
         | Para que la hora mostrada sea legalmente exacta, el servidor debe
         | sincronizarse por NTP contra el INM (hora.inm.gov.co). Es una tarea
         | de administración del servidor, no del frontend.
        */
        'legal_time' => [
            'enabled' => true,
            'timezone' => 'America/Bogota',
            'source_url' => 'https://horalegal.inm.gov.co/',
        ],

        // El orden llena la rejilla por filas: X e Instagram quedan a la
        // izquierda y YouTube a la derecha, como en el pie institucional.
        'social' => [
            [
                'network' => 'x',
                'name' => 'X (antes Twitter)',
                'handle' => '@huvoficial',
                'url' => 'https://x.com/huvoficial',
            ],
            [
                'network' => 'youtube',
                'name' => 'YouTube',
                'handle' => '@hospitaluniversitariodelva8588',
                'url' => 'https://www.youtube.com/@hospitaluniversitariodelva8588',
            ],
            [
                'network' => 'instagram',
                'name' => 'Instagram',
                'handle' => '@huvoficial',
                'url' => 'https://www.instagram.com/huvoficial',
            ],
        ],

        'legal_links' => [
            ['label' => 'Políticas', 'url' => '#'],
            ['label' => 'Transparencia', 'url' => '#transparencia'],
            ['label' => 'Mapa del sitio', 'url' => '#'],
            ['label' => 'Estadísticas', 'url' => '#'],
        ],
    ],

];
