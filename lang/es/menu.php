<?php

/*
| Rótulos que viven en config/huv.php.
|
| La configuración se lee antes de que esté fijado el idioma —y se puede
| cachear—, así que allí cada entrada solo declara su clave en 'i18n' y el texto
| se pide aquí desde la vista, con App\Support\ConfigLabel.
|
| El orden es el de config/huv.php, para poder leer los dos ficheros a la vez.
| Si una clave falta, la vista se queda con el rótulo escrito en la
| configuración: nunca hay un hueco en blanco.
*/

return [

    // ---------------- Mapa de ubicación ----------------

    'mapa' => [
        'titulo' => 'Ubicación física',
    ],

    // ---------------- Metadatos del sitio ----------------

    // El título es la razón social del hospital y no se traduce.
    'seo' => [
        'descripcion' => 'Institución de salud pública de alta complejidad del suroccidente colombiano. '
            .'Trámites, servicios, noticias y transparencia del Hospital Universitario del Valle E.S.E.',
        'claves' => 'HUV, Hospital Universitario del Valle, Evaristo García, salud, Cali, Valle del Cauca, alta complejidad',
    ],

    // ---------------- Menú principal ----------------

    'nav' => [
        'inicio' => 'Inicio',
        'transparencia' => 'Transparencia y acceso a la información pública',

        'atencion' => [
            'rotulo' => 'Atención y Servicios a la ciudadanía',
            'politicas' => 'Política y protección de datos',
            'pqrds' => 'PQRDS Recepción de Solicitudes',
            'contacto' => 'Mecanismos de contacto',
            'tramites' => 'Trámites y servicios',
            'laboratorio' => 'Consulta resultados laboratorio',
            'citas' => 'Citas',
            'servicios' => 'Servicios',
            'programas' => 'Programas',
            'pagos' => 'Pagos en línea',
            'denuncias' => 'Denuncias por posibles actos de corrupción',
            'ciau' => 'Centro Integral de Atención al Usuario - CIAU',
            'voluntariados' => 'Voluntariados',
            'encuestas' => 'Encuestas de satisfacción',
            'tic' => 'Herramienta Tic PCD',
            'academica' => 'Oficina Coordinadora Académica',
            'etica' => 'Comité de Ética en Investigaciones Hospitalarias',
        ],

        'participa' => [
            'rotulo' => 'Participa',
            'diagnostico' => 'Diagnóstico e Identificación de problemas',
            'presupuesto' => 'Planeación y presupuesto participativo',
            'consulta' => 'Consulta ciudadana',
            'colaboracion' => 'Colaboración e innovación',
            'rendicion' => 'Rendición de cuentas',
            'control' => 'Control ciudadano',
            'descripcion' => 'Descripción Participa',
        ],

        'noticias' => 'Noticias',
        'normatividad' => 'Normatividad',
    ],

    // ---------------- Menú completo (botón ☰) ----------------

    // Los 65 enlaces de «Entidades relacionadas» son nombres propios de otras
    // entidades: solo se traduce el título de la columna.
    'completo' => [

        'documentos' => [
            'rotulo' => 'Documentos',
            'presupuesto' => 'Presupuesto',
            'programas' => 'Programas',
            'planes' => 'Planes',
            'politicas' => 'Políticas y lineamientos',
            'proyectos' => 'Proyectos en ejecución',
            'informes_pqrds' => 'Informes de PQRDS',
            'control_interno' => 'Control Interno',
            'otros' => 'Otros',
        ],

        'informate' => [
            'rotulo' => 'Infórmate',
            'contrataciones' => 'Contrataciones',
            'poblacion' => 'Población vulnerable',
            'rendicion' => 'Rendición de cuentas',
            'empleo' => 'Ofertas de empleo',
            'metas' => 'Metas, objetivos e indicadores',
            'preguntas' => 'Preguntas y respuestas',
            'convocatorias' => 'Convocatorias',
            'datos' => 'Datos abiertos',
            'sucursales' => 'Sucursales',
            'notificaciones' => 'Notificaciones Judiciales',
            'restructuracion' => 'Restructuración',
            'retencion' => 'Tablas de retención documental',
            'actos' => 'Actos administrativos de nombramientos y encargos',
            'inventario' => 'Inventario único documental',
        ],

        'nosotros' => [
            'rotulo' => 'Nosotros',
            'correo' => 'Correo interno',
            'procesos' => 'Procesos y procedimientos',
            'funcionarios' => 'Directorio de funcionarios',
            'institucional' => 'Directorio institucional',
            'entidad' => 'Entidad',
            'entidades' => 'Directorio de entidades',
            'agremiaciones' => 'Directorio de agremiaciones, asociaciones y otros grupos de interés',
            'servicio' => 'Servicio al público, normas, formularios',
        ],

        'entidades' => [
            'rotulo' => 'Entidades relacionadas',
        ],
    ],

    // ---------------- Accesos rápidos ----------------

    'accesos' => [
        'titulo' => 'Atención y Servicios a la ciudadanía',
        'subtitulo' => 'Trámites y canales de atención más consultados.',

        'citas' => [
            'titulo' => 'Asignación de citas',
            'texto' => 'Solicita, consulta o cancela tu cita de consulta externa.',
            'accion' => 'Ir al trámite',
        ],
        'pqrsd' => [
            'titulo' => 'PQRSD',
            'texto' => 'Peticiones, quejas, reclamos, sugerencias y denuncias.',
            'accion' => 'Ir al trámite',
        ],
        'historia' => [
            'titulo' => 'Copia de historia clínica',
            'texto' => 'Solicitud de copia para el paciente o su representante legal.',
            'accion' => 'Ir al trámite',
        ],
        'portafolio' => [
            'titulo' => 'Portafolio de servicios',
            'texto' => 'Servicios habilitados de mediana y alta complejidad.',
            'accion' => 'Consultar',
        ],
        'sangre' => [
            'titulo' => 'Banco de Sangre',
            'texto' => 'Requisitos, horarios y puntos de donación.',
            'accion' => 'Consultar',
        ],
        'judiciales' => [
            'titulo' => 'Notificaciones judiciales',
            'texto' => 'Buzón oficial para notificaciones y comunicaciones judiciales.',
            'accion' => 'Ir al buzón',
        ],
        'contratacion' => [
            'titulo' => 'Contratación',
            'texto' => 'Procesos, invitaciones públicas y estudios previos.',
            'accion' => 'Consultar',
        ],
        'empleo' => [
            'titulo' => 'Convocatorias de empleo',
            'texto' => 'Ofertas laborales y procesos de selección vigentes.',
            'accion' => 'Consultar',
        ],
    ],

    // ---------------- Franja de líneas de atención ----------------

    // Los teléfonos y la dirección no llevan clave: se leen igual en los dos
    // idiomas y se escriben una sola vez, en la configuración.
    'lineas' => [
        'conmutador' => ['rotulo' => 'Conmutador'],
        'atencion' => ['rotulo' => 'Atención al usuario'],
        'urgencias' => ['rotulo' => 'Urgencias', 'valor' => 'Atención 24 horas'],
        'sede' => ['rotulo' => 'Sede principal'],
    ],

    // ---------------- Bloque «Nuestra entidad» ----------------

    // El título del bloque es la razón social del hospital y no se traduce.
    'entidad' => [
        'antetitulo' => 'Nuestra entidad',
        'marcador' => 'Fachada del HUV (800×600)',

        'parrafos' => [
            'El Hospital es una entidad pública (Empresa Social del Estado) descentralizada del orden '
                .'Departamental adscrita a la Secretaría de Salud del Valle del Cauca, presta servicios de '
                .'salud con énfasis en la atención del paciente de alta complejidad, y es una de las '
                .'instituciones de salud más grandes e importantes del suroccidente colombiano.',
            'Como Hospital Universitario, participa en la formación, desarrollo y actualización del Talento '
                .'Humano en la modalidad formal y no formal en el marco de los convenios docencia-servicio, '
                .'con instituciones educativas nacionales e internacionales.',
        ],

        'mision' => [
            'titulo' => 'Misión',
            'texto' => 'El Hospital Universitario del Valle «Evaristo García» E.S.E. tiene como objetivo '
                .'brindar servicios de salud de mediana y alta complejidad a la población que lo requiera '
                .'a través de un talento humano competente y comprometido.',
        ],
        'naturaleza' => [
            'titulo' => 'Naturaleza jurídica',
            'texto' => 'Mediante el Decreto Departamental N.° 1807 del 7 de noviembre de 1995 el Hospital '
                .'se transforma en Empresa Social del Estado, en cumplimiento de los artículos 194 y 197 '
                .'de la Ley 100 de 1993.',
        ],

        'accion_entidad' => 'Conoce la entidad',
        'accion_historia' => 'Reseña histórica',

        // Las cifras —70, 24/7, ESE— se quedan en la configuración: son las
        // mismas en los dos idiomas.
        'anos' => ['rotulo' => 'años de servicio', 'extra' => 'desde 1956'],
        'urgencias' => ['rotulo' => 'urgencias y', 'extra' => 'alta complejidad'],
        'ese' => ['rotulo' => 'Empresa Social', 'extra' => 'del Estado'],
    ],

    // ---------------- Boletines y comunicados ----------------

    'boletines' => [
        'titulo' => 'Boletines y comunicados',

        'destacado' => [
            'titulo' => 'Comunicado de prensa',
            'resumen' => 'Garantizamos el funcionamiento estable de los canales de atención electrónicos.',
            'marcador' => 'Vista previa del comunicado en PDF (600×780)',
        ],
        'institucional' => [
            'titulo' => 'Boletín informativo institucional',
            'resumen' => 'Resumen mensual de la gestión asistencial, docente e investigativa del hospital.',
            'marcador' => 'Miniatura del boletín (240×160)',
        ],
        'sede_electronica' => [
            'titulo' => 'Contenidos de la sede electrónica',
            'resumen' => 'Guía de los trámites y servicios disponibles en línea y sus tiempos de respuesta.',
            'marcador' => 'Miniatura del documento (240×160)',
        ],
    ],

    // ---------------- Entidades y enlaces de interés ----------------

    // Solo el título de la franja: los nombres de las entidades son nombres
    // propios y se quedan como los firma cada una.
    'entidades_interes' => 'Entidades y enlaces de interés',

    // ---------------- Servicios y especialidades ----------------

    'servicios' => [
        'titulo' => 'Servicios y especialidades',
        'subtitulo' => 'Servicios de mediana y alta complejidad habilitados en la institución.',

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

    // ---------------- Mosaico de Transparencia de la portada ----------------

    'transparencia_mosaico' => [
        'titulo' => 'Transparencia y acceso a la información pública',
        'subtitulo' => 'Ley 1712 de 2014 y Resolución 1519 de 2020 del MinTIC.',

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

    // ---------------- Índice de Transparencia ----------------

    /*
     | Las claves se numeran por la posición legal de cada apartado —g1, g1_1,
     | g1_1_1—, que es como se cita en una auditoría de la Resolución 1519 de
     | 2020 y como sale impreso en la página. Así este fichero se lee al lado de
     | config/huv.php sin tener que ir contando entradas.
    */
    'transparencia' => [
        'titulo' => 'Transparencia',

        'g1' => 'Información de la entidad',
        'g1_1' => 'Misión, visión, funciones y deberes',
        'g1_1_1' => 'Misión y Visión',
        'g1_1_2' => 'Funciones y deberes',
        'g1_2' => 'Organigrama',
        'g1_3' => 'Mapas y cartas descriptivas de los procesos',
        'g1_4' => 'Directorio Institucional incluyendo sedes, oficinas, sucursales, o regionales, y dependencias',
        'g1_5' => 'Directorio de servidores públicos, empleados o contratistas',
        'g1_6' => 'Directorio de entidades',
        'g1_7' => 'Directorio de asociaciones, agremiaciones y otros grupos de interés',
        'g1_8' => 'Servicio al público, normas, formularios y protocolos de atención',
        'g1_9' => 'Procedimientos que se siguen para tomar decisiones en las diferentes áreas',
        'g1_10' => 'Mecanismo de presentación directa de solicitudes, quejas y reclamos',
        'g1_11' => 'Calendario de actividades',
        'g1_12' => 'Información sobre decisiones que pueden afectar al público',
        'g1_13' => 'Entes y autoridades que lo vigilan',
        'g1_14' => 'Publicación hojas de vida',
        'g1_15' => 'Actos administrativos de nombramientos y encargos',

        'g2' => 'Normatividad',
        'g2_1' => 'Normatividad',
        'g2_1_1' => 'Leyes',
        'g2_1_2' => 'Decreto Único Reglamentario',
        'g2_1_3' => 'Normativa aplicable',
        'g2_1_4' => 'Gaceta Oficial',
        'g2_1_5' => 'Políticas, lineamientos y manuales',
        'g2_2' => 'Busqueda de normas',

        'g3' => 'Contratación',
        'g3_1' => 'Publicación del plan anual de adquisiciones',
        'g3_2' => 'Publicación de la información contractual',
        'g3_3' => 'Publicación de la ejecución de contratos',
        'g3_4' => 'Manual de contratación, adquisición y/o compras',

        'g4' => 'Planeación',
        'g4_1' => 'Presupuesto general de ingresos, gastos e inversión',
        'g4_2' => 'Ejecución presupuestal',
        'g4_3' => 'Planes de Acción',
        'g4_4' => 'Proyectos de Inversión',
        'g4_5' => 'Informes de empalme',
        'g4_6' => 'Información pública y/o relevante',
        'g4_7' => 'Informes de gestión, evaluación y auditoría',
        'g4_7_1' => 'Informe de Gestión',
        'g4_7_2' => 'Informe de rendición de cuentas ante la Contraloría General de la República',
        'g4_7_3' => 'Informe de rendición de cuentas a la ciudadanía',
        'g4_7_4' => 'Informes a organismos de inspección, vigilancia y control',
        'g4_7_5' => 'Planes de mejoramiento',
        'g4_7_6' => 'Enlace al organismo de control',
        'g4_8' => 'Informes de la Oficina de Control Interno',
        'g4_9' => 'Informe sobre Defensa Pública y Prevención del Daño Antijurídico '
            .'-https://ekogui.defensajuridica.gov.co/Pages/NEW/index.aspx',
        'g4_10' => 'Informes trimestrales sobre acceso a información, quejas y reclamos',

        'g5' => 'Trámites',
        'g5_1' => 'Trámites',

        'g6' => 'Participa',
        'g6_1' => 'Descripción Menu Participa',
        'g6_2' => 'Diagnóstico e identificación de problemas',
        'g6_3' => 'Planeación y presupuesto participativo',
        'g6_4' => 'Consulta Ciudadana',
        'g6_5' => 'Colaboración e innovación',
        'g6_6' => 'Rendición de cuentas - control',
        'g6_7' => 'Control social',

        'g7' => 'Datos abiertos',
        'g7_1' => 'Instrumentos de gestión de la información',
        'g7_1_1' => 'Registros de activos de información',
        'g7_1_2' => 'Índice de información clasificada y reservada',
        'g7_1_3' => 'Esquema de publicación de la información',
        'g7_1_4' => 'Programa de gestión documental',
        'g7_1_5' => 'Tablas de retención documental',
        'g7_2' => 'Sección de Datos Abiertos',
        'g7_3' => 'Inventario único documental',

        'g8' => 'Información específica para grupos de interés',
        'g8_1' => 'Información para niños, niñas y adolescentes',
        'g8_2' => 'Información para Mujeres',
        'g8_3' => 'Otros de grupos de interés',

        'g9' => 'Obligación de reporte de información específica',
        'g9_1' => 'Normatividad especial',

        'g10' => 'Atención y servicio a la ciudadanía',
        'g10_1' => 'Trámites, Otros Procedimientos Administrativos y consultas de acceso a información pública',
        'g10_2' => 'Canales de atención y pida una cita',
        'g10_3' => 'PQRSFD',

        'g11' => 'Noticias',
        'g11_1' => 'Sección de Noticias',

        'g12' => 'Condiciones técnicas y de seguridad digital',
        'g12_1' => 'Condiciones técnicas y de seguridad digital',
    ],

    // ---------------- Pie de página ----------------

    'pie' => [

        // Solo los rótulos y el horario: los teléfonos, la dirección y los
        // correos se escriben una sola vez, en la configuración.
        'contacto' => [
            'direccion' => ['rotulo' => 'Dirección'],
            'horario' => [
                'rotulo' => 'Horario de atención',
                'valor' => 'Lunes a Viernes de 7:00 A.M. a 12:00 M y 1:00 P.M. a 5:30 P.M.',
            ],
            'conmutador' => ['rotulo' => 'Teléfono Conmutador'],
            'linea_gratuita' => ['rotulo' => 'Línea de atención gratuita'],
            'anticorrupcion' => ['rotulo' => 'Línea anticorrupción'],
            'correo' => ['rotulo' => 'Correo institucional'],
            'correo_judicial' => ['rotulo' => 'Correo de notificaciones judiciales'],
        ],

        // «YouTube» e «Instagram» son nombres propios y no llevan clave.
        'redes' => [
            'x' => 'X (antes Twitter)',
        ],

        'legal' => [
            'politicas' => 'Políticas',
            'transparencia' => 'Transparencia',
            'mapa' => 'Mapa del sitio',
            'estadisticas' => 'Estadísticas',
        ],
    ],

];
