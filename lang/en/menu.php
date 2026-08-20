<?php

/*
| Rótulos que viven en config/huv.php.
|
| Mismas claves y mismo orden que lang/es/menu.php. Los nombres de leyes y
| resoluciones colombianas se citan como se citan allí —«Ley 1712 de 2014»,
| «Resolución 1519 de 2020»—, y «Participa», «PQRDS», «PQRSFD» y «gov.co» son
| nombres de secciones y trámites del Estado: no se traducen.
*/

return [

    'mapa' => [
        'titulo' => 'Physical location',
    ],

    'seo' => [
        'descripcion' => 'High-complexity public health institution of south-western Colombia. '
            .'Procedures, services, news and transparency of Hospital Universitario del Valle E.S.E.',
        'claves' => 'HUV, Hospital Universitario del Valle, Evaristo García, health, Cali, Valle del Cauca, high complexity',
    ],

    'nav' => [
        'inicio' => 'Home',
        'transparencia' => 'Transparency and access to public information',

        'atencion' => [
            'rotulo' => 'Citizen support and services',
            'politicas' => 'Data policy and protection',
            'pqrds' => 'PQRDS Request Reception',
            'contacto' => 'Contact channels',
            'tramites' => 'Procedures and services',
            'laboratorio' => 'Check laboratory results',
            'citas' => 'Appointments',
            'servicios' => 'Services',
            'programas' => 'Programs',
            'pagos' => 'Online payments',
            'denuncias' => 'Reports of possible acts of corruption',
            'ciau' => 'Comprehensive User Support Center - CIAU',
            'voluntariados' => 'Volunteering',
            'encuestas' => 'Satisfaction surveys',
            'tic' => 'ICT tool for people with disabilities',
            'academica' => 'Academic Coordination Office',
            'etica' => 'Hospital Research Ethics Committee',
        ],

        'participa' => [
            'rotulo' => 'Participate',
            'diagnostico' => 'Diagnosis and identification of problems',
            'presupuesto' => 'Participatory planning and budgeting',
            'consulta' => 'Citizen consultation',
            'colaboracion' => 'Collaboration and innovation',
            'rendicion' => 'Accountability',
            'control' => 'Citizen oversight',
            'descripcion' => 'About Participa',
        ],

        'noticias' => 'News',
        'normatividad' => 'Regulations',
    ],

    'completo' => [

        'documentos' => [
            'rotulo' => 'Documents',
            'presupuesto' => 'Budget',
            'programas' => 'Programs',
            'planes' => 'Plans',
            'politicas' => 'Policies and guidelines',
            'proyectos' => 'Ongoing projects',
            'informes_pqrds' => 'PQRDS reports',
            'control_interno' => 'Internal Control',
            'otros' => 'Other',
        ],

        'informate' => [
            'rotulo' => 'Get informed',
            'contrataciones' => 'Procurement',
            'poblacion' => 'Vulnerable population',
            'rendicion' => 'Accountability',
            'empleo' => 'Job openings',
            'metas' => 'Goals, objectives and indicators',
            'preguntas' => 'Questions and answers',
            'convocatorias' => 'Calls for applications',
            'datos' => 'Open data',
            'sucursales' => 'Branches',
            'notificaciones' => 'Judicial notifications',
            'restructuracion' => 'Restructuring',
            'retencion' => 'Records retention schedules',
            'actos' => 'Administrative acts for appointments and acting assignments',
            'inventario' => 'Single documentary inventory',
        ],

        'nosotros' => [
            'rotulo' => 'About us',
            'correo' => 'Internal email',
            'procesos' => 'Processes and procedures',
            'funcionarios' => 'Directory of public servants',
            'institucional' => 'Institutional directory',
            'entidad' => 'The institution',
            'entidades' => 'Directory of institutions',
            'agremiaciones' => 'Directory of trade associations, associations and other interest groups',
            'servicio' => 'Public service, regulations and forms',
        ],

        'entidades' => [
            'rotulo' => 'Related institutions',
        ],
    ],

    'accesos' => [
        'titulo' => 'Citizen support and services',
        'subtitulo' => 'Most requested procedures and service channels.',

        'citas' => [
            'titulo' => 'Appointment scheduling',
            'texto' => 'Request, check or cancel your outpatient appointment.',
            'accion' => 'Go to the procedure',
        ],
        'pqrsd' => [
            'titulo' => 'PQRSD',
            'texto' => 'Petitions, complaints, claims, suggestions and reports.',
            'accion' => 'Go to the procedure',
        ],
        'historia' => [
            'titulo' => 'Copy of medical records',
            'texto' => 'Request for a copy by the patient or their legal representative.',
            'accion' => 'Go to the procedure',
        ],
        'portafolio' => [
            'titulo' => 'Service portfolio',
            'texto' => 'Authorized medium- and high-complexity services.',
            'accion' => 'View',
        ],
        'sangre' => [
            'titulo' => 'Blood Bank',
            'texto' => 'Requirements, opening hours and donation points.',
            'accion' => 'View',
        ],
        'judiciales' => [
            'titulo' => 'Judicial notifications',
            'texto' => 'Official inbox for judicial notifications and communications.',
            'accion' => 'Go to the inbox',
        ],
        'contratacion' => [
            'titulo' => 'Procurement',
            'texto' => 'Processes, public invitations and preliminary studies.',
            'accion' => 'View',
        ],
        'empleo' => [
            'titulo' => 'Job openings',
            'texto' => 'Current job openings and selection processes.',
            'accion' => 'View',
        ],
    ],

    'lineas' => [
        'conmutador' => ['rotulo' => 'Switchboard'],
        'atencion' => ['rotulo' => 'User support'],
        'urgencias' => ['rotulo' => 'Emergency care', 'valor' => '24-hour service'],
        'sede' => ['rotulo' => 'Main site'],
    ],

    'entidad' => [
        'antetitulo' => 'Our institution',
        'marcador' => 'HUV façade (800×600)',

        'parrafos' => [
            'The Hospital is a decentralized public institution (Empresa Social del Estado) of the '
                .'departmental level, attached to the Secretaría de Salud del Valle del Cauca. It provides '
                .'health services with an emphasis on the care of high-complexity patients, and it is one '
                .'of the largest and most important health institutions in south-western Colombia.',
            'As a University Hospital, it takes part in the training, development and continuing education '
                .'of human talent, in both formal and non-formal modes, under the teaching-service '
                .'agreements signed with national and international educational institutions.',
        ],

        'mision' => [
            'titulo' => 'Mission',
            'texto' => 'The purpose of Hospital Universitario del Valle «Evaristo García» E.S.E. is to '
                .'provide medium- and high-complexity health services to the population that requires them, '
                .'through competent and committed staff.',
        ],
        'naturaleza' => [
            'titulo' => 'Legal status',
            'texto' => 'Under Departmental Decree No. 1807 of 7 November 1995, the Hospital became an '
                .'Empresa Social del Estado, in compliance with articles 194 and 197 of Ley 100 de 1993.',
        ],

        'accion_entidad' => 'About the institution',
        'accion_historia' => 'Historical overview',

        'anos' => ['rotulo' => 'years of service', 'extra' => 'since 1956'],
        'urgencias' => ['rotulo' => 'emergency care and', 'extra' => 'high complexity'],
        'ese' => ['rotulo' => 'Empresa Social', 'extra' => 'del Estado'],
    ],

    'boletines' => [
        'titulo' => 'Bulletins and press releases',

        'destacado' => [
            'titulo' => 'Press release',
            'resumen' => 'We guarantee the stable operation of our electronic service channels.',
            'marcador' => 'PDF preview of the press release (600×780)',
        ],
        'institucional' => [
            'titulo' => 'Institutional newsletter',
            'resumen' => 'Monthly summary of the clinical, teaching and research work of the hospital.',
            'marcador' => 'Bulletin thumbnail (240×160)',
        ],
        'sede_electronica' => [
            'titulo' => 'Contents of the electronic office',
            'resumen' => 'Guide to the procedures and services available online and their response times.',
            'marcador' => 'Document thumbnail (240×160)',
        ],
    ],

    'entidades_interes' => 'Related institutions and links',

    'servicios' => [
        'titulo' => 'Services and specialties',
        'subtitulo' => 'Medium- and high-complexity services authorized at the institution.',

        'items' => [
            'Pediatrics',
            'Internal Medicine',
            'ICU',
            'High Complexity',
            'Orthopedics',
            'Oncology',
            'Obstetrics',
            'Neurology',
            'Cardiology',
            'Physical Medicine',
            'Mental Health',
            'Pain Clinic',
            'Family Medicine',
            'Hematology-Oncology',
            'Blood Bank',
            'Radiology',
            'Milk Bank',
            'Psychiatry',
            'Dermatology',
            'Ophthalmology',
            'Otolaryngology',
            'Chemotherapy',
            'Clinical Laboratory',
        ],
    ],

    'transparencia_mosaico' => [
        'titulo' => 'Transparency and access to public information',
        'subtitulo' => 'Ley 1712 de 2014 and Resolución 1519 de 2020 of MinTIC.',

        'items' => [
            'Institutional information',
            'Regulations',
            'Procurement',
            'Planning, budget and reports',
            'Procedures',
            'Participate',
            'Open data',
            'Specific information for interest groups',
            'Obligation to report specific information',
            'Citizen support and services',
        ],
    ],

    'transparencia' => [
        'titulo' => 'Transparency',

        'g1' => 'Institutional information',
        'g1_1' => 'Mission, vision, functions and duties',
        'g1_1_1' => 'Mission and Vision',
        'g1_1_2' => 'Functions and duties',
        'g1_2' => 'Organizational chart',
        'g1_3' => 'Process maps and descriptive charts',
        'g1_4' => 'Institutional directory including sites, offices, branches or regional offices, and units',
        'g1_5' => 'Directory of public servants, employees or contractors',
        'g1_6' => 'Directory of institutions',
        'g1_7' => 'Directory of associations, trade associations and other interest groups',
        'g1_8' => 'Public service, regulations, forms and service protocols',
        'g1_9' => 'Procedures followed to make decisions in the different areas',
        'g1_10' => 'Mechanism for submitting requests, complaints and claims directly',
        'g1_11' => 'Calendar of activities',
        'g1_12' => 'Information on decisions that may affect the public',
        'g1_13' => 'Bodies and authorities that oversee the institution',
        'g1_14' => 'Publication of résumés',
        'g1_15' => 'Administrative acts for appointments and acting assignments',

        'g2' => 'Regulations',
        'g2_1' => 'Regulations',
        'g2_1_1' => 'Laws',
        'g2_1_2' => 'Decreto Único Reglamentario',
        'g2_1_3' => 'Applicable regulations',
        'g2_1_4' => 'Gaceta Oficial',
        'g2_1_5' => 'Policies, guidelines and manuals',
        'g2_2' => 'Search for regulations',

        'g3' => 'Procurement',
        'g3_1' => 'Publication of the annual procurement plan',
        'g3_2' => 'Publication of contract information',
        'g3_3' => 'Publication of contract execution',
        'g3_4' => 'Manual for contracting, acquisition and/or purchasing',

        'g4' => 'Planning',
        'g4_1' => 'General budget of revenue, expenditure and investment',
        'g4_2' => 'Budget execution',
        'g4_3' => 'Action plans',
        'g4_4' => 'Investment projects',
        'g4_5' => 'Handover reports',
        'g4_6' => 'Public and/or relevant information',
        'g4_7' => 'Management, evaluation and audit reports',
        'g4_7_1' => 'Management report',
        'g4_7_2' => 'Accountability report to the Contraloría General de la República',
        'g4_7_3' => 'Accountability report to the public',
        'g4_7_4' => 'Reports to inspection, monitoring and control bodies',
        'g4_7_5' => 'Improvement plans',
        'g4_7_6' => 'Link to the oversight body',
        'g4_8' => 'Reports of the Internal Control Office',
        'g4_9' => 'Report on Public Defense and Prevention of Unlawful Harm '
            .'-https://ekogui.defensajuridica.gov.co/Pages/NEW/index.aspx',
        'g4_10' => 'Quarterly reports on access to information, complaints and claims',

        'g5' => 'Procedures',
        'g5_1' => 'Procedures',

        'g6' => 'Participate',
        'g6_1' => 'About the Participa menu',
        'g6_2' => 'Diagnosis and identification of problems',
        'g6_3' => 'Participatory planning and budgeting',
        'g6_4' => 'Citizen consultation',
        'g6_5' => 'Collaboration and innovation',
        'g6_6' => 'Accountability - oversight',
        'g6_7' => 'Social oversight',

        'g7' => 'Open data',
        'g7_1' => 'Information management instruments',
        'g7_1_1' => 'Information asset registers',
        'g7_1_2' => 'Index of classified and restricted information',
        'g7_1_3' => 'Information publication scheme',
        'g7_1_4' => 'Records management program',
        'g7_1_5' => 'Records retention schedules',
        'g7_2' => 'Open data section',
        'g7_3' => 'Single documentary inventory',

        'g8' => 'Specific information for interest groups',
        'g8_1' => 'Information for children and adolescents',
        'g8_2' => 'Information for women',
        'g8_3' => 'Other interest groups',

        'g9' => 'Obligation to report specific information',
        'g9_1' => 'Special regulations',

        'g10' => 'Citizen support and service',
        'g10_1' => 'Procedures, other administrative processes and requests for access to public information',
        'g10_2' => 'Service channels and appointment booking',
        'g10_3' => 'PQRSFD',

        'g11' => 'News',
        'g11_1' => 'News section',

        'g12' => 'Technical and digital security conditions',
        'g12_1' => 'Technical and digital security conditions',
    ],

    'pie' => [

        'contacto' => [
            'direccion' => ['rotulo' => 'Address'],
            'horario' => [
                'rotulo' => 'Service hours',
                'valor' => 'Monday to Friday, 7:00 a.m. to 12:00 noon and 1:00 p.m. to 5:30 p.m.',
            ],
            'conmutador' => ['rotulo' => 'Switchboard telephone'],
            'linea_gratuita' => ['rotulo' => 'Toll-free helpline'],
            'anticorrupcion' => ['rotulo' => 'Anti-corruption hotline'],
            'correo' => ['rotulo' => 'Institutional email'],
            'correo_judicial' => ['rotulo' => 'Judicial notifications email'],
        ],

        'redes' => [
            'x' => 'X (formerly Twitter)',
        ],

        'legal' => [
            'politicas' => 'Policies',
            'transparencia' => 'Transparency',
            'mapa' => 'Site map',
            'estadisticas' => 'Statistics',
        ],
    ],

];
