<?php

return [

    'ruta' => [
        'etiqueta' => 'Breadcrumb',
        'inicio' => 'Home',
    ],

    'enlace' => [
        'pestana_nueva' => '(opens in a new tab)',
    ],

    'listado' => [
        'categorias' => [
            'filtro' => 'Filter by category',
            'todas' => 'All categories',
            'ver_mas' => 'Show more',
            'ver_menos' => 'Show less',
        ],

        'busqueda' => [
            'etiqueta' => 'Search in :tema',
            'boton' => 'Search',
        ],

        'orden' => [
            'etiqueta' => 'Sort by:',
            'recientes' => 'Most recent',
            'az' => 'A-Z',
        ],

        'tipo' => [
            'etiqueta' => 'Filter by content type',
            'todos' => 'All content',
        ],

        'periodo' => [
            'etiqueta' => 'Filter by date',
            'mes' => 'Last month',
            'semestre' => 'Last six months',
            'ano' => 'Last year',
            'trienio' => 'Last three years',
        ],

        'vista' => [
            'etiqueta' => 'Listing layout',
            'cuadricula' => 'View as a grid',
            'lista' => 'View as a list',
        ],

        'nuevo' => 'New content',
        'ocultar' => 'Hide',
        'carga_masiva' => 'Bulk upload',
        'vacio' => 'No content has been published in :tema yet.',
        'sin_resultados' => 'No content matches your search.',
        'quitar_filtros' => 'Clear the filters',
        'cargar_mas' => 'Load more content',

        'mostrando' => 'Showing :visibles of :total :contenidos',
        'mostrando_pagina' => 'Showing :desde–:hasta of :total items',
    ],

    'tema' => [
        'descripcion' => ':tema published by :entidad.',
        'descripcion_noticias' => 'News from :entidad.',
        'descripcion_enlaces' => ':tema at :entidad.',
    ],

    'ficha' => [
        'modificacion' => 'Last modified:',
        'creacion' => 'Created:',
        'editar' => 'Edit',
        'expedicion' => 'Date of issue:',
        'archivos' => 'Files to download',
        'expediente' => 'View the complete record',
        'relacionados' => 'Also in :contexto',

        'evento' => [
            'cuando' => 'When',
            'donde' => 'Where',
            'organiza' => 'Organized by',
            'formato' => 'l, j F Y, H:i',
        ],
    ],

    'contacto' => [
        'titulo' => 'Contact channels',
        'descripcion' => 'Address, telephone numbers, email addresses and service hours of :entidad.',

        'mecanismos' => [
            'direccion' => 'Address',
            'conmutador' => 'Telephone',
            'linea_gratuita' => 'Toll-free helpline',
            'correo' => 'Email',
            'correo_judicial' => 'Judicial notifications',
            'horario' => 'Service hours',
        ],

        'formulario' => 'Electronic form for requests, petitions, complaints, claims and reports',
    ],

    'sucursales' => [
        'titulo' => 'Branches',
        'descripcion' => 'Sites of :entidad.',
    ],

    'politicas' => [
        'titulo' => 'Policies',
        'descripcion' => 'Copyright policy and authorization for the use of content of :entidad.',

        'derechos' => [
            'titulo' => 'Copyright policy and authorization for the use of content',
            'texto' => ':entidad establishes that all content produced or managed as part of its '
                .'institutional activity constitutes a strategic asset whose creation, use, disclosure and '
                .'exploitation must comply with the principles of accountability, legality, security and '
                .'institutional protection. Accordingly, the economic rights arising from content created '
                .'by public servants, contractors, teaching staff, residents or students in the exercise of '
                .'their duties belong to the hospital, without prejudice to the recognition of the author\'s '
                .'moral rights. No institutional content may be reproduced, published, altered, licensed, '
                .'disclosed by any means or made available to the public without the express, prior and '
                .'written authorization of the offices empowered to grant it. Content must be handled in '
                .'accordance with the standards for information security, data quality, records management '
                .'and the protection of personal and clinical data, ensuring the traceability, preservation '
                .'and ethical use of the knowledge generated within the institution.',
        ],

        'plataforma' => [
            'intro' => 'Below you can consult the terms and conditions, the information privacy policies '
                .'and the personal data processing policies of the solution that you must take into account '
                .'for the correct use of the territorial portal service provided by Gobierno Digital; '
                .'remember to click on the titles to find out more:',
        ],
    ],

    'transparencia' => [
        'titulo' => 'Transparency',
        'descripcion' => 'Index of transparency and access to public information of :entidad, '
            .'under Resolución 1519 de 2020.',
    ],

    'pqrds' => [
        'titulo' => 'PQRDS Request Reception',
        'descripcion' => 'Submit petitions, complaints, claims, suggestions, reports and requests for '
            .'information to :entidad.',
        'encabezado' => 'Submit petitions, complaints, claims, suggestions and reports (PQRDS)',
        'entradilla' => 'Please review the following definitions to determine the type of request to '
            .'submit and the applicable response times.',
        'seleccion' => 'Select the type of request you wish to file',

        'tramites' => [
            'peticion' => [
                'titulo' => 'Petition',
                'definicion' => 'The fundamental right of every person to submit respectful requests to the '
                    .'authorities on matters of general or individual interest and to obtain a prompt '
                    .'resolution.',
                'boton' => 'Submit a petition or right of petition',
            ],
            'queja' => [
                'titulo' => 'Complaint',
                'definicion' => 'A statement of protest, censure, dissatisfaction or disagreement made by a '
                    .'person regarding conduct considered improper on the part of one or more public '
                    .'servants in the exercise of their duties.',
                'boton' => 'Submit a complaint',
            ],
            'reclamo' => [
                'titulo' => 'Claim',
                'definicion' => 'The right of every person to demand or claim a solution, whether on a '
                    .'general or individual matter, concerning the improper delivery of a service or the '
                    .'failure to respond to a request.',
                'boton' => 'Submit a claim',
            ],
            'sugerencia' => [
                'titulo' => 'Suggestion',
                'definicion' => 'A statement of an idea or proposal to improve the service or the '
                    .'management of the institution.',
                'boton' => 'Submit a suggestion',
            ],
            'felicitacion' => [
                'titulo' => 'Compliment',
                'definicion' => 'A statement of satisfaction with a service provided or with the management '
                    .'of the institution.',
                'boton' => 'Submit a compliment',
            ],
            'denuncia' => [
                'titulo' => 'Report of misconduct',
                'definicion' => 'Bringing to the attention of a competent authority conduct that may be '
                    .'improper, so that the corresponding criminal, disciplinary, fiscal, administrative or '
                    .'professional ethics investigation may proceed.',
                'boton' => 'Submit a report of misconduct',
            ],
            'solicitud_informacion' => [
                'titulo' => 'Request for information',
                'definicion' => 'A request to access public information, without the applicant having to '
                    .'prove their identity, the type of interest, the reasons for the request or the '
                    .'purposes for which the data will be used.',
                'boton' => 'Request information',
            ],
            'solicitud_datos' => [
                'titulo' => 'Request regarding personal data',
                'definicion' => 'A request to change and/or delete the user\'s personal data when it '
                    .'requires correction or updating.',
                'boton' => 'Submit a request',
            ],
            'cita' => [
                'titulo' => 'Book an appointment',
                'definicion' => 'An in-person or virtual meeting when a procedure needs to be carried out.',
                'boton' => 'Book an appointment',
            ],
        ],

        'seguimiento' => [
            'texto' => 'Track your request using the code the portal generates when you complete the '
                .'corresponding form and submit your request.',
            'boton' => 'Track a request',
        ],
    ],

    'acceso' => [
        'titulo' => 'Sign in',
        'descripcion' => 'Portal access for Hospital Universitario del Valle staff.',
        'entradilla' => 'Access for hospital staff. Content published on the portal is managed from here.',
        'error' => 'Sign-in was not possible',
        'correo' => 'Institutional email',
        'contrasena' => 'Password',
        'recordar' => 'Keep me signed in',
        'entrar' => 'Sign in',
        'sin_cuenta' => 'Do not have an account? The portal does not allow public registration. '
            .'Request access from the hospital IT Office.',
    ],

];
