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

    /*
    |--------------------------------------------------------------------------
    | Mapas de ubicación
    |--------------------------------------------------------------------------
    |
    | Indexados por el identificador del tema en cuya página se pintan. Las
    | coordenadas son las que publica el portal en su bloque «Ubicación fisica»
    | —así, sin tilde—: 3.430215008, -76.545449495, a zoom 16.
    |
    | El portal centra el mapa unos noventa metros al este de la chincheta,
    | porque guarda el centro y la chincheta por separado y quien lo editó
    | arrastró uno de los dos. Aquí se centra en la chincheta.
    |
    */
    'maps' => [
        'directorio-institucional' => [
            'title' => 'Ubicación física',
            'label' => 'Hospital Universitario del Valle «Evaristo García» E.S.E.',
            'address' => 'Calle 5 # 36-08, Santiago de Cali, Valle del Cauca',
            'latitude' => 3.430215,
            'longitude' => -76.545449,
            'zoom' => 16,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mecanismos de contacto
    |--------------------------------------------------------------------------
    |
    | El portal enlaza el formulario a través de «acortar.link/OUtyCS». Aquí se
    | guarda el destino final: un acortador esconde a dónde se va, deja el
    | enlace a merced de un servicio de terceros y no se puede revisar.
    |
    */
    'contact' => [
        'request_form' => 'http://cross.huv.gov.co/cross/apps/CROSSHUV/ASAP/applications/cross300/'
            .'index.php?action=FeCrCmdDefaultWebUser&username=webuser&context=2&lang=es',
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
        ],
        [
            'label' => 'Transparencia y acceso a la información pública',
            'path' => '/transparencia',
            'narrow' => true,
        ],
        [
            'label' => 'Atención y Servicios a la ciudadanía',
            'key' => 'atencion',
            'narrow' => true,
            'children' => [
                ['label' => 'Política y protección de datos', 'path' => '/politicas'],
                ['label' => 'PQRDS Recepción de Solicitudes', 'path' => '/peticiones-quejas-reclamos'],
                ['label' => 'Mecanismos de contacto', 'path' => '/contactenos'],
                ['label' => 'Trámites y servicios', 'path' => '/tema/tramites-y-servicios'],
                ['label' => 'Consulta resultados laboratorio', 'url' => 'https://laboratorio.huv.gov.co/ConsultaWeb/'],
                ['label' => 'Citas', 'url' => 'https://citas.huv.gov.co/login'],
                ['label' => 'Servicios', 'path' => '/tema/servicios'],
                ['label' => 'Programas', 'path' => '/tema/programas-342077'],
                ['label' => 'Pagos en línea', 'path' => '/tema/pagos-en-linea'],
                ['label' => 'Denuncias por posibles actos de corrupción', 'path' => '/tema/denuncias-por-actos-de-corrupcion'],
                ['label' => 'Centro Integral de Atención al Usuario - CIAU', 'path' => '/tema/ciau'],
                ['label' => 'Voluntariados', 'path' => '/tema/voluntariados'],
                ['label' => 'Encuestas de satisfacción', 'path' => '/tema/encuestas-de-satisfaccion'],
                ['label' => 'Herramienta Tic PCD', 'path' => '/tema/herramienta-tic-discapacitados'],
                ['label' => 'Oficina Coordinadora Académica', 'path' => '/tema/diplomados-y-cursos'],
                ['label' => 'Comité de Ética en Investigaciones Hospitalarias', 'url' => 'https://sites.google.com/correohuv.gov.co/comiteeticahospitauniversitari/p%C3%A1gina-principal'],
            ],
        ],
        [
            'label' => 'Participa',
            'key' => 'participa',
            'children' => [
                // Siete temas del portal, no siete enlaces muertos. Con 'path'
                // se resuelven solos: al portal anterior mientras no estén
                // migrados y a este aplicativo en cuanto lo estén.
                //
                // «Rendición de cuentas» de aquí NO es /tema/control, que se
                // llama igual: son dos temas distintos del portal.
                ['label' => 'Diagnóstico e Identificación de problemas', 'path' => '/tema/diagnostico-e-identificacion-de-problemas'],
                ['label' => 'Planeación y presupuesto participativo', 'path' => '/tema/planeacion-presupuesto-participativo'],
                ['label' => 'Consulta ciudadana', 'path' => '/tema/consulta-ciudadana'],
                ['label' => 'Colaboración e innovación', 'path' => '/tema/colaboracion-e-innovacion'],
                ['label' => 'Rendición de cuentas', 'path' => '/tema/rendicion-de-cuentas'],
                ['label' => 'Control ciudadano', 'path' => '/tema/control-ciudadano'],
                ['label' => 'Descripción Participa', 'path' => '/tema/descripcion-participa'],
            ],
        ],
        [
            'label' => 'Noticias',
            'path' => '/tema/noticias',
        ],
        [
            'label' => 'Normatividad',
            'path' => '/tema/normatividad',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal heredado
    |--------------------------------------------------------------------------
    | Mientras no existan las páginas equivalentes en este aplicativo, las
    | secciones que solo declaran 'path' se sirven desde el portal actual, de
    | modo que ningún enlace del menú queda roto.
    |
    | Al migrar cada sección basta con crear su ruta aquí y, cuando estén todas,
    | poner 'legacy_base' => null: los mismos 'path' pasan a resolverse contra
    | este dominio sin tocar una sola vista.
    */
    'legacy_base' => 'https://hospital-universitario-del-valle-evaristo-garcia-ese.micolombiadigital.gov.co',

    /*
    |--------------------------------------------------------------------------
    | Menú completo (botón ☰)
    |--------------------------------------------------------------------------
    | Dos niveles: las categorías a la izquierda y sus enlaces al lado.
    | Cada enlace declara 'path' (interno, resuelto contra legacy_base) o
    | 'url' (externo, se abre en una pestaña nueva).
    */
    'mega_menu' => [

        [
            'key' => 'documentos',
            'title' => 'Documentos',
            'links' => [
                ['label' => 'Presupuesto', 'path' => '/tema/presupuesto'],
                ['label' => 'Programas', 'path' => '/tema/programas'],
                ['label' => 'Planes', 'path' => '/tema/planes'],
                ['label' => 'Políticas y lineamientos', 'path' => '/tema/politicas-y-lineamientos'],
                ['label' => 'Proyectos en ejecución', 'path' => '/tema/proyectos-en-ejecucion'],
                ['label' => 'Informes de PQRDS', 'path' => '/tema/informe-de-pqr'],
                ['label' => 'Control Interno', 'path' => '/tema/control-interno'],
                ['label' => 'Otros', 'path' => '/tema/otros'],
            ],
        ],

        [
            'key' => 'informate',
            'title' => 'Infórmate',
            'links' => [
                ['label' => 'Contrataciones', 'path' => '/tema/contrataciones'],
                ['label' => 'Población vulnerable', 'path' => '/tema/poblacion-vulnerable'],
                ['label' => 'Rendición de cuentas', 'path' => '/tema/control'],
                ['label' => 'Ofertas de empleo', 'path' => '/tema/ofertas-de-empleo'],
                ['label' => 'Metas, objetivos e indicadores', 'path' => '/tema/metas-objetivos-e-indicadores'],
                ['label' => 'Preguntas y respuestas', 'path' => '/tema/preguntas-y-respuestas'],
                ['label' => 'Convocatorias', 'path' => '/tema/convocatorias'],
                ['label' => 'Datos abiertos', 'path' => '/tema/datos-abiertos'],
                ['label' => 'Sucursales', 'path' => '/sucursales'],
                ['label' => 'Notificaciones Judiciales', 'path' => '/tema/notificaciones-judiciales'],
                ['label' => 'Restructuración', 'path' => '/tema/restructuracion'],
                ['label' => 'Tablas de retención documental', 'path' => '/tema/tablas-de-retencion-documental'],
                ['label' => 'Actos administrativos de nombramientos y encargos', 'path' => '/tema/actos-administrativos-de-nombramientos-y-encargos'],
                ['label' => 'Inventario único documental', 'path' => '/tema/inventario-unico-documental'],
            ],
        ],

        [
            'key' => 'nosotros',
            'title' => 'Nosotros',
            'links' => [
                ['label' => 'Correo interno', 'url' => 'https://mail.huv.gov.co/'],
                ['label' => 'Procesos y procedimientos', 'path' => '/tema/procesos-y-procedimientos'],
                ['label' => 'Directorio de funcionarios', 'path' => '/tema/directorio-de-funcionarios'],
                ['label' => 'Directorio institucional', 'path' => '/tema/directorio-institucional'],
                ['label' => 'Entidad', 'path' => '/tema/entidad'],
                ['label' => 'Directorio de entidades', 'path' => '/tema/directorio-de-entidades'],
                // El portal actual corta este rótulo en «…y otros grupos de».
                ['label' => 'Directorio de agremiaciones, asociaciones y otros grupos de interés', 'path' => '/tema/directorio-de-agremiaciones-asociaciones-y-otros-grupos'],
                ['label' => 'Servicio al público, normas, formularios', 'path' => '/tema/servicio-al-publico-normas-formularios-y-protocolos'],
            ],
        ],

        [
            'key' => 'entidades',
            'title' => 'Entidades relacionadas',
            'columns' => 3,
            'links' => [
                ['label' => 'ABSALON TORRES CAMACHO', 'url' => 'https://www.absalontorrescamacho.ie.edu.co/'],
                ['label' => 'Autoridad Regional de Transporte - ART Movamos Región', 'url' => 'https://www.movamosregion.gov.co/'],
                ['label' => 'Benemérito Cuerpo de Bomberos de El Cairo en Valle Del Cauca', 'url' => 'https://www.bomberos-cairo.gov.co/'],
                ['label' => 'Comisión Especial de Carrera Administrativa de las Contralorías Territoriales en Cali - Valle del Cauca', 'url' => 'https://www.cecact.gov.co/'],
                ['label' => 'CONCEJO MUNICIPAL LA VICTORIA VALLE DEL CAUCA', 'url' => 'http://www.concejo-lavictoria-valle.gov.co/'],
                ['label' => 'E.S.E Hospital San Agustín Puerto Merizalde de Buenaventura en Valle del Cauca', 'url' => 'https://www.hospitalsanagustinpm.gov.co/'],
                ['label' => 'ESCUELA NORMAL SUPERIOR MARIA INMACULADA', 'url' => 'https://www.normalsuperiorcaicedonia.edu.co/'],
                ['label' => 'Escuela Normal Superior Nuestra Señora de Las Mercedes', 'url' => 'https://www.normalzarzal.ie.edu.co/'],
                ['label' => 'I E Simón Bolívar', 'url' => 'https://www.sibo-lacumbre.ie.edu.co/'],
                ['label' => 'I.E Borrero Ayerbe', 'url' => 'https://www.ieborreroayerbe.ie.edu.co/'],
                ['label' => 'I.E General Santander', 'url' => 'https://www.ie-generalsantander-sed-vac.ie.edu.co/'],
                ['label' => 'I.E Primitivo Crespo', 'url' => 'https://www.primitivocrespo.ie.edu.co/'],
                ['label' => 'I.E Santa Rita de Cassia', 'url' => 'https://www.santaritadecassia.ie.edu.co/'],
                ['label' => 'I.E Simón Bolívar', 'url' => 'https://www.iesimonbolivarzarzal.ie.edu.co/'],
                ['label' => 'I.E. Del Dagua', 'url' => 'https://www.iedagua.ie.edu.co/'],
                ['label' => 'I.E. El Placer', 'url' => 'https://www.institucioneducativaelplacer-ansermanuevo.ie.edu.co/'],
                ['label' => 'I.E. Fray José Joaquín Escobar', 'url' => 'https://www.frayjosejoaquinescobar.edu.co/'],
                ['label' => 'I.E. Gilberto Álzate Avendaño', 'url' => 'https://www.iegilbertoalzateavendanoargeliavalle.edu.co/'],
                ['label' => 'I.E. Hernando Llorente Arroyo', 'url' => 'https://www.iehernandollorente-riofrio-valle.edu.co/'],
                ['label' => 'I.E. Jorge Eliécer Gaitán', 'url' => 'https://www.jorgeeliecergaitan.ie.edu.co/'],
                ['label' => 'I.E. Jorge Robledo', 'url' => 'https://www.jorgerobledovijes.ie.edu.co/'],
                ['label' => 'I.E. José Antonio Aguilera', 'url' => 'https://www.iejoseantonioaguilera-sanpedro-valle.edu.co/'],
                ['label' => 'I.E. José Celestino Mutis', 'url' => 'https://www.josecelestinomutisguabas.ie.edu.co/'],
                ['label' => 'I.E. Manuel Antonio Sanclemente', 'url' => 'https://www.iemasbuga-valle.edu.co/'],
                ['label' => 'I.E. Normal Superior Jorge Isaacs', 'url' => 'https://www.nsji.edu.co/'],
                ['label' => 'I.E. San Isidro en Valle del Cauca', 'url' => 'https://www.institucioneducativasanisidro.ie.edu.co/'],
                ['label' => 'IE MAGDALENA ORTEGA', 'url' => 'https://www.magdalenaortega.ie.edu.co/'],
                ['label' => 'IE MIGUEL ANTONIO CARO', 'url' => 'https://www.iemac-sanpedro-valle.ie.edu.co/'],
                ['label' => 'Institución Educativa Antonio José de Sucre', 'url' => 'https://www.ieantoniojosedesucre-trujillovalle.edu.co/'],
                ['label' => 'Institución Educativa Argemiro Escobar Cardona', 'url' => 'https://www.ieargemiroescobarlaunionvalle.edu.co/'],
                ['label' => 'Institución Educativa Ateneo', 'url' => 'https://www.ateneo.ie.edu.co/'],
                ['label' => 'Institución Educativa Belisario Peña Piñeiro', 'url' => 'https://www.belisariopproldanillo.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA BENJAMÍN HERRERA', 'url' => 'https://www.benjaminherrera.ie.edu.co/'],
                ['label' => 'Institución Educativa Camilo Torres', 'url' => 'https://www.camilotorresriofrio.ie.edu.co/'],
                ['label' => 'Institución Educativa Ciudad Florida', 'url' => 'https://www.ieciudadflorida.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA DE LA NACIÓN EMBERA DEL VALLE DEL CAUCA IENEV', 'url' => 'https://www.ienev.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA EL PALMAR', 'url' => 'https://www.elpalmardagua.ie.edu.co/'],
                ['label' => 'Institución Educativa El Queremal', 'url' => 'https://www.queremal.edu.co/'],
                ['label' => 'Institución Educativa Francisco Antonio Zea', 'url' => 'https://www.franciscoantoniozea.ie.edu.co/'],
                ['label' => 'Institución Educativa Jorge Isaacs', 'url' => 'https://www.jorgeisaacs.ie.edu.co/'],
                ['label' => 'Institución Educativa José Acevedo y Gómez', 'url' => 'https://www.joseacevedoygomez-restrepo.ie.edu.co/'],
                ['label' => 'Institución Educativa José Ignacio Ospina', 'url' => 'https://www.joseignacioospina.ie.edu.co/'],
                ['label' => 'Institución Educativa José María Córdoba', 'url' => 'https://www.iejosemariacordobaflorida.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA KWE´SX NASA KSXA´WNXI IDEBIC', 'url' => 'https://www.idebic.ie.edu.co/'],
                ['label' => 'Institución Educativa La Inmaculada', 'url' => 'https://www.lainmaculada.ie.edu.co/'],
                ['label' => 'Institución Educativa La Tulia', 'url' => 'http://www.ielatulia.edu.co/'],
                ['label' => 'Institución Educativa Las Américas', 'url' => 'https://www.lasamericas.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA MANUEL ANTONIO BONILLA', 'url' => 'https://www.iemanuelantoniobonilla.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA MARÍA AUXILIADORA', 'url' => 'https://www.mariaauxiliadorasevilla.ie.edu.co/'],
                ['label' => 'INSTITUCIÓN EDUCATIVA MARINO RENJIFO SALCEDO', 'url' => 'https://www.marinorenjifosalcedo.ie.edu.co/'],
                ['label' => 'Institución Educativa Regional Simón Bolívar', 'url' => 'https://www.regionalsimonbolivar.ie.edu.co/'],
                ['label' => 'Institución Educativa Rosendo Mondragón Feijoo', 'url' => 'https://www.rosendomondragonfeijoo.ie.edu.co/'],
                ['label' => 'Institución Educativa San José', 'url' => 'https://www.sanjoselavictoria.edu.co/'],
                ['label' => 'Instituto Colombiano de Ballet Clásico', 'url' => 'https://www.incolballet.gov.co/'],
                ['label' => 'JULIO CAICEDO TÉLLEZ', 'url' => 'https://www.iejctellezsanpedro.edu.co/'],
                ['label' => 'Panebianco Americano', 'url' => 'https://www.panebiancoamericano.ie.edu.co/'],
                ['label' => 'Personería Municipal de Alcalá Valle del Cauca', 'url' => 'https://www.personeria-alcalavalle.gov.co/'],
                ['label' => 'Personería Municipal de Candelaria en Valle del Cauca', 'url' => 'https://www.personeriacandelaria.gov.co/'],
                ['label' => 'Personería Municipal de Dagua en Valle del Cauca', 'url' => 'http://www.personeriadagua.gov.co/'],
                ['label' => 'Personería Municipal de Restrepo en Valle del Cauca', 'url' => 'https://www.personeria-restrepovalle.gov.co/'],
                ['label' => 'Personería Municipal de Tuluá en Valle del Cauca', 'url' => 'https://www.personeriatulua.gov.co/'],
                ['label' => 'Personería Municipal San Pedro en Valle del Cauca', 'url' => 'https://www.personeria-sanpedrovalle.gov.co/'],
                ['label' => 'Región de Planeación y Gestión del Centro del Valle', 'url' => 'https://www.cenvallerpg.gov.co/'],
                ['label' => 'Santa Ana de los Caballeros', 'url' => 'https://www.iesantaanadeloscaballeros-ansermanuevo-valledelcauca.edu.co/'],
                ['label' => 'Sistema Integrado de Transporte', 'url' => 'https://www.sitren.gov.co/'],
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
                'image_hint' => 'Banner 1 (3750×968)',
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
                'image_hint' => 'Banner 2 (3750×968)',
                'eyebrow' => '70 años',
                'title' => '70 años de liderazgo en salud pública',
                'text' => 'Integrando servicios de alta complejidad, tecnología de vanguardia y el rigor '
                    .'científico de nuestros especialistas al servicio del Valle del Cauca.',
            ],
            [
                'type' => 'standard',
                'theme' => 'light',
                'image' => null,
                'image_hint' => 'Banner 3 (3750×968)',
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
                'url' => 'https://citas.huv.gov.co/login',
            ],
            [
                'title' => 'PQRSD',
                'text' => 'Peticiones, quejas, reclamos, sugerencias y denuncias.',
                'cta' => 'Ir al trámite',
                'path' => '/peticiones-quejas-reclamos',
            ],
            [
                'title' => 'Copia de historia clínica',
                'text' => 'Solicitud de copia para el paciente o su representante legal.',
                'cta' => 'Ir al trámite',
                'path' => '/tramites-y-servicios/historia-clinica',
            ],
            [
                'title' => 'Portafolio de servicios',
                'text' => 'Servicios habilitados de mediana y alta complejidad.',
                'cta' => 'Consultar',
                'path' => '/tema/servicios',
            ],
            [
                'title' => 'Banco de Sangre',
                'text' => 'Requisitos, horarios y puntos de donación.',
                'cta' => 'Consultar',
                'path' => '/servicios/banco-de-sangre',
            ],
            [
                'title' => 'Notificaciones judiciales',
                'text' => 'Buzón oficial para notificaciones y comunicaciones judiciales.',
                'cta' => 'Ir al buzón',
                'path' => '/tema/notificaciones-judiciales',
            ],
            [
                'title' => 'Contratación',
                'text' => 'Procesos, invitaciones públicas y estudios previos.',
                'cta' => 'Consultar',
                'path' => '/tema/contrataciones',
            ],
            [
                'title' => 'Convocatorias de empleo',
                'text' => 'Ofertas laborales y procesos de selección vigentes.',
                'cta' => 'Consultar',
                'path' => '/tema/ofertas-de-empleo',
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
            ['label' => 'Conoce la entidad', 'path' => '/tema/entidad', 'variant' => 'primary'],
            ['label' => 'Reseña histórica', 'path' => '/entidad/historia', 'variant' => 'ghost'],
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
    | Noticias — franja destacada bajo el banner
    |--------------------------------------------------------------------------
    | Las noticias viven en la tabla `contents` y se administran desde el propio
    | portal. Aquí solo queda el rótulo de la sección.
    */
    'news' => [
        'title' => 'Noticias',
        // El listado completo: la misma tabla que este bloque, vista entera.
        'all_url' => '/tema/noticias',
    ],

    /*
    |--------------------------------------------------------------------------
    | Listado general de contenidos
    |--------------------------------------------------------------------------
    | Se publica entero en el HTML —bien para buscadores y para quien navega sin
    | JavaScript— y los controles de orden, filtro y «cargar más» actúan sobre
    | lo ya renderizado.
    */
    'content_feed' => [
        'per_page' => 6,

        // Cuántos contenidos llegan al HTML de la portada. El muro filtra en el
        // navegador, así que todo lo que se imprime pesa: con las 425
        // notificaciones judiciales sin tope, la portada ocupaba 1,17 MB.
        'max_items' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Boletines y comunicados
    |--------------------------------------------------------------------------
    */
    'bulletins' => [
        'title' => 'Boletines y comunicados',
        'all_url' => null,
        'featured' => [
            'title' => 'Comunicado de prensa',
            'excerpt' => 'Garantizamos el funcionamiento estable de los canales de atención electrónicos.',
            'published_at' => '-4 days',
            'url' => null,
            'document' => null,
            'document_hint' => 'Vista previa del comunicado en PDF (600×780)',
        ],
        'items' => [
            [
                'title' => 'Boletín informativo institucional',
                'excerpt' => 'Resumen mensual de la gestión asistencial, docente e investigativa del hospital.',
                'published_at' => '2026-07-01 09:00',
                'url' => null,
                'document' => null,
                'document_hint' => 'Miniatura del boletín (240×160)',
            ],
            [
                'title' => 'Contenidos de la sede electrónica',
                'excerpt' => 'Guía de los trámites y servicios disponibles en línea y sus tiempos de respuesta.',
                'published_at' => '2026-06-16 11:30',
                'url' => null,
                'document' => null,
                'document_hint' => 'Miniatura del documento (240×160)',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entidades y enlaces de interés
    |--------------------------------------------------------------------------
    | Logotipos oficiales: colocar cada archivo en public/img/entidades/ y
    | apuntar 'logo' a su ruta.
    */
    'partners' => [
        'title' => 'Entidades y enlaces de interés',
        'items' => [
            ['name' => 'Instituto Nacional de Metrología de Colombia', 'url' => 'https://www.inm.gov.co/', 'logo' => null],
            ['name' => 'Departamento Nacional de Planeación', 'url' => 'https://www.dnp.gov.co/', 'logo' => null],
            ['name' => 'Presidencia de la República', 'url' => 'https://www.presidencia.gov.co/', 'logo' => null],
            ['name' => 'Ministerio TIC', 'url' => 'https://www.mintic.gov.co/', 'logo' => null],
            ['name' => 'Ministerio de Salud y Protección Social', 'url' => 'https://www.minsalud.gov.co/', 'logo' => null],
            ['name' => 'Gobernación del Valle del Cauca', 'url' => 'https://www.valledelcauca.gov.co/', 'logo' => null],
            ['name' => 'Secretaría de Salud del Valle del Cauca', 'url' => 'https://www.valledelcauca.gov.co/salud', 'logo' => null],
            ['name' => 'Portal del Estado Colombiano GOV.CO', 'url' => 'https://www.gov.co/', 'logo' => null],
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
    | Índice de Transparencia
    |--------------------------------------------------------------------------
    |
    | El árbol que publica «/transparencia»: doce grupos numerados con sus
    | entradas, tal como los exige la Resolución 1519 de 2020. En el portal
    | anterior sale de «menuoptions?section=Transparency» y se edita desde el
    | panel; aquí vive en configuración, como el menú principal, porque es lo
    | mismo que el menú: rótulos y destinos, sin contenido propio.
    |
    | Los destinos NO se escriben resueltos. Van como los publica el portal
    | —«/tema/entidad», «/entidad/mision-y-vision»— y LegacyLink decide en cada
    | petición si el tema ya está migrado, y entonces enlaza aquí dentro, o
    | todavía no, y entonces enlaza al portal anterior. Así el índice se muda
    | solo según avanza la migración, sin tocar este fichero.
    |
    | Están los doce, con sus 69 enlaces. El orden y la numeración son los del
    | origen: así es como se cita cada apartado en una auditoría.
    |
    | El `active` del origen no se mira: allí es falso en cuatro entradas de
    | este primer grupo —Organigrama, Directorio de entidades…— y aun así el
    | portal las lista. Es una marca del menú de cabecera, no de este índice.
    */
    'transparency_index' => [
        'title' => 'Transparencia',

        'groups' => [
            [
                'label' => 'Información de la entidad',
                'items' => [
                [
                    'label' => 'Misión, visión, funciones y deberes',
                    'path' => '/tema/entidad',
                    'children' => [
                        ['label' => 'Misión y Visión', 'path' => '/entidad/mision-y-vision'],
                        ['label' => 'Funciones y deberes', 'path' => '/entidad/funciones-y-deberes'],
                    ],
                ],
                ['label' => 'Organigrama', 'path' => '/entidad/organigrama'],
                ['label' => 'Mapas y cartas descriptivas de los procesos', 'path' => '/entidad/nuestra-entidad'],
                ['label' => 'Directorio Institucional incluyendo sedes, oficinas, sucursales, o regionales, y dependencias', 'path' => '/tema/directorio-institucional'],
                ['label' => 'Directorio de servidores públicos, empleados o contratistas', 'path' => '/directorio-de-funcionarios/conoce-a-nuestros-funcionarios'],
                ['label' => 'Directorio de entidades', 'path' => '/tema/directorio-de-entidades'],
                ['label' => 'Directorio de asociaciones, agremiaciones y otros grupos de interés', 'path' => '/tema/directorio-de-agremiaciones-asociaciones-y-otros-grupos'],
                ['label' => 'Servicio al público, normas, formularios y protocolos de atención', 'path' => '/tema/servicio-al-publico-normas-formularios-y-protocolos'],
                ['label' => 'Procedimientos que se siguen para tomar decisiones en las diferentes áreas', 'path' => '/tema/procesos-y-procedimientos'],
                ['label' => 'Mecanismo de presentación directa de solicitudes, quejas y reclamos', 'url' => 'https://acortar.link/OUtyCS'],
                ['label' => 'Calendario de actividades', 'path' => '/tema/calendario-de-actividades'],
                ['label' => 'Información sobre decisiones que pueden afectar al público', 'path' => '/tema/noticias'],
                ['label' => 'Entes y autoridades que lo vigilan', 'path' => '/tema/directorio-de-entidades'],
                ['label' => 'Publicación hojas de vida', 'path' => '/tema/ofertas-de-empleo'],
                ['label' => 'Actos administrativos de nombramientos y encargos', 'path' => '/tema/actos-administrativos-de-nombramientos-y-encargos'],
                ],
            ],
            [
                'label' => 'Normatividad',
                'items' => [
                    [
                        'label' => 'Normatividad',
                        'path' => '/tema/normatividad',
                        'children' => [
                            ['label' => 'Leyes', 'path' => '/normatividad/normograma-institucional'],
                            ['label' => 'Decreto Único Reglamentario', 'path' => '/normatividad/decreto-unico-reglamentario-del-sector-salud-y-proteccion'],
                            ['label' => 'Normativa aplicable', 'path' => '/tema/normatividad'],
                            ['label' => 'Gaceta Oficial', 'url' => 'https://impretics.gov.co/GACETA-DEPARTAMENTAL/'],
                            ['label' => 'Políticas, lineamientos y manuales', 'path' => '/tema/politicas-y-lineamientos'],
                        ],
                    ],
                    ['label' => 'Busqueda de normas', 'url' => 'https://www.suin-juriscol.gov.co/'],
                ],
            ],
            [
                'label' => 'Contratación',
                'items' => [
                    ['label' => 'Publicación del plan anual de adquisiciones', 'path' => '/tema/planes/2024-526540'],
                    ['label' => 'Publicación de la información contractual', 'path' => '/tema/contrataciones/2024-600324'],
                    ['label' => 'Publicación de la ejecución de contratos', 'path' => '/tema/contrataciones/2024-600324'],
                    ['label' => 'Manual de contratación, adquisición y/o compras', 'path' => '/procesos-y-procedimientos/manual-de-contratacion-402456'],
                ],
            ],
            [
                'label' => 'Planeación',
                'items' => [
                    ['label' => 'Presupuesto general de ingresos, gastos e inversión', 'path' => '/presupuesto/acuerdo-no023-presupuesto-2024'],
                    ['label' => 'Ejecución presupuestal', 'path' => '/tema/presupuesto/ejecucion-presupuestal-2024'],
                    ['label' => 'Planes de Acción', 'path' => '/tema/planes/2024-445229'],
                    ['label' => 'Proyectos de Inversión', 'path' => '/metas-objetivos-e-indicadores/proyectos-de-inversion-vigencia-2024'],
                    ['label' => 'Informes de empalme', 'path' => '/tema/control/informes-de-empalme'],
                    ['label' => 'Información pública y/o relevante', 'path' => '/planes/plan-de-desarrollo-institucional-20242027'],
                    [
                        'label' => 'Informes de gestión, evaluación y auditoría',
                        'path' => '/tema/control/informes-de-gestion-evaluacion-y-auditoria',
                        'children' => [
                            ['label' => 'Informe de Gestión', 'path' => '/control/informe-al-entidad'],
                            ['label' => 'Informe de rendición de cuentas ante la Contraloría General de la República', 'path' => '/control/informe-de-rendicion-de-cuenta-fiscal'],
                            ['label' => 'Informe de rendición de cuentas a la ciudadanía', 'path' => '/control/informe-de-rendicion-de-cuentas-a-la-ciudadania'],
                            ['label' => 'Informes a organismos de inspección, vigilancia y control', 'path' => '/control/informes-a-organismos-de-inspeccion-vigilancia-y-control'],
                            ['label' => 'Planes de mejoramiento', 'path' => '/tema/planes/plan-de-mejoramiento'],
                            ['label' => 'Enlace al organismo de control', 'url' => 'https://www.contraloriavalledelcauca.gov.co/publicaciones/32725/informes-de-las-auditorias-realizadas-por-la-cdvc/'],
                        ],
                    ],
                    ['label' => 'Informes de la Oficina de Control Interno', 'path' => '/tema/control-interno/2024-995206'],
                    ['label' => 'Informe sobre Defensa Pública y Prevención del Daño Antijurídico -https://ekogui.defensajuridica.gov.co/Pages/NEW/index.aspx', 'url' => 'https://ekogui.defensajuridica.gov.co/Pages/inicio_bop.aspx'],
                    ['label' => 'Informes trimestrales sobre acceso a información, quejas y reclamos', 'path' => '/tema/control/informes-trimestrales-pqrsfd-2023/2024-483422'],
                ],
            ],
            [
                'label' => 'Trámites',
                'items' => [
                    ['label' => 'Trámites', 'path' => '/tema/tramites-y-servicios'],
                ],
            ],
            [
                'label' => 'Participa',
                'items' => [
                    ['label' => 'Descripción Menu Participa', 'path' => '/tema/descripcion-participa'],
                    ['label' => 'Diagnóstico e identificación de problemas', 'path' => '/tema/diagnostico-e-identificacion-de-problemas'],
                    ['label' => 'Planeación y presupuesto participativo', 'path' => '/tema/planeacion-presupuesto-participativo/ita'],
                    ['label' => 'Consulta Ciudadana', 'path' => '/tema/consulta-ciudadana'],
                    ['label' => 'Colaboración e innovación', 'path' => '/tema/colaboracion-e-innovacion'],
                    ['label' => 'Rendición de cuentas - control', 'path' => '/tema/rendicion-de-cuentas/apcr-2024'],
                    ['label' => 'Control social', 'path' => '/control-ciudadano/modalidades-control-social'],
                ],
            ],
            [
                'label' => 'Datos abiertos',
                'items' => [
                    [
                        'label' => 'Instrumentos de gestión de la información',
                        'path' => '/tema/tablas-de-retencion-documental',
                        'children' => [
                            ['label' => 'Registros de activos de información', 'url' => 'https://www.datos.gov.co/Salud-y-Protecci-n-Social/REGISTRO-DE-ACTIVOS-DE-INFORMACION/naix-b9bv'],
                            ['label' => 'Índice de información clasificada y reservada', 'path' => '/control/indice-de-informacion-clasificada-y-reservada'],
                            ['label' => 'Esquema de publicación de la información', 'path' => '/control/esquema-de-publicacion-de-informacion'],
                            ['label' => 'Programa de gestión documental', 'path' => '/programas/programa-de-gestion-documental-755714'],
                            ['label' => 'Tablas de retención documental', 'path' => '/tema/tablas-de-retencion-documental'],
                        ],
                    ],
                    ['label' => 'Sección de Datos Abiertos', 'url' => 'https://www.datos.gov.co/d/naix-b9bv'],
                    ['label' => 'Inventario único documental', 'path' => '/tema/inventario-unico-documental'],
                ],
            ],
            [
                'label' => 'Información específica para grupos de interés',
                'items' => [
                    ['label' => 'Información para niños, niñas y adolescentes', 'path' => '/politicas-y-lineamientos/derechos-del-nino-hospitalizado-872252'],
                    ['label' => 'Información para Mujeres', 'path' => '/politicas-y-lineamientos/politica-institucion-amiga-de-la-mujer-y-de-la-infancia'],
                    ['label' => 'Otros de grupos de interés', 'path' => '/tema/politicas-y-lineamientos/poblacion-vulnerable-31962'],
                ],
            ],
            [
                'label' => 'Obligación de reporte de información específica',
                'items' => [
                    ['label' => 'Normatividad especial', 'path' => '/otros/normatividad-especial'],
                ],
            ],
            [
                'label' => 'Atención y servicio a la ciudadanía',
                'items' => [
                    ['label' => 'Trámites, Otros Procedimientos Administrativos y consultas de acceso a información pública', 'path' => '/tema/tramites-y-servicios'],
                    ['label' => 'Canales de atención y pida una cita', 'path' => '/contactenos/'],
                    ['label' => 'PQRSFD', 'url' => 'https://acortar.link/OUtyCS'],
                ],
            ],
            [
                'label' => 'Noticias',
                'items' => [
                    ['label' => 'Sección de Noticias', 'path' => '/tema/noticias'],
                ],
            ],
            [
                'label' => 'Condiciones técnicas y de seguridad digital',
                'items' => [
                    ['label' => 'Condiciones técnicas y de seguridad digital', 'path' => '/politicas-y-lineamientos/politica-de-seguridad-digital'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pie de página
    |--------------------------------------------------------------------------
    */
    'footer' => [

        /*
         | Datos de contacto tal como aparecen en el pie institucional.
         |
         | Cada fila lleva `key` porque «Mecanismos de contacto» publica los
         | mismos datos con otros rótulos y en otro orden. Con la clave, los
         | números viven en un solo sitio: cambiar una extensión aquí la
         | cambia en el pie y en esa página a la vez.
         */
        'contact' => [
            [
                'key' => 'direccion',
                'label' => 'Dirección',
                'value' => 'Cl. 5 # 36-08 Santiago de Cali. Valle del Cauca, Colombia',
            ],
            [
                'key' => 'horario',
                'label' => 'Horario de atención',
                'value' => 'Lunes a Viernes de 7:00 A.M. a 12:00 M y 1:00 P.M. a 5:30 P.M.',
            ],
            [
                'key' => 'conmutador',
                'label' => 'Teléfono Conmutador',
                'value' => '(57+2) 6206000 Ext. 1001',
                'tel' => '+5726206000,1001',
            ],
            [
                'key' => 'linea-gratuita',
                'label' => 'Línea de atención gratuita',
                'value' => '(57+2) 6206000 Ext: 1218 / 1216',
                'tel' => '+5726206000,1218',
            ],
            [
                'key' => 'anticorrupcion',
                'label' => 'Línea anticorrupción',
                'value' => '(57+2) 6206000 Ext: 1043',
                'tel' => '+5726206000,1043',
            ],
            [
                'key' => 'correo',
                'label' => 'Correo institucional',
                'value' => 'pqrsf@correohuv.gov.co',
                'mailto' => 'pqrsf@correohuv.gov.co',
            ],
            [
                'key' => 'correo-judicial',
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
                'url' => 'https://www.youtube.com/channel/UCE_g-XfOMAhSdoEZ5m1zqCA',
            ],
            [
                'network' => 'instagram',
                'name' => 'Instagram',
                'handle' => '@huvoficial',
                'url' => 'https://www.instagram.com/huvoficial',
            ],
        ],

        'legal_links' => [
            ['label' => 'Políticas', 'path' => '/politicas'],
            ['label' => 'Transparencia', 'path' => '/transparencia'],
            ['label' => 'Mapa del sitio', 'path' => '/mapa-del-sitio'],
            ['label' => 'Estadísticas', 'path' => '/estadisticas'],
        ],
    ],

];
