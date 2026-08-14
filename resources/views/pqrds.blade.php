@extends('layouts.app')

@section('title', 'PQRDS Recepción de Solicitudes — '.config('huv.institution.short_name'))
@section('description', 'Presente peticiones, quejas, reclamos, sugerencias, denuncias y solicitudes de '
    .'información ante el '.config('huv.institution.name_plain').'.')

@php
    /*
     | Los diez trámites del portal, con su definición literal.
     |
     | El texto es el suyo palabra por palabra, erratas incluidas
     | —«competenten», «la presentación indebida de un servicio»—: son
     | definiciones legales que la entidad publica así, y corregirlas por
     | nuestra cuenta cambiaría un texto oficial.
     |
     | El formulario que las recibe NO es de este aplicativo: vive en la
     | plataforma (pqrs-api.micolombiadigital.gov.co) y su dirección se pide con
     | una credencial que este portal no tiene. Hasta que el hospital decida con
     | qué lo reemplaza, cada botón lleva a la página donde el trámite funciona
     | hoy, en vez de a un formulario nuestro que no radicaría nada.
     */
    $tramites = [
        [
            'icono' => 'peticion',
            'titulo' => 'Petición',
            'definicion' => 'Es el derecho fundamental que tiene toda persona a presentar solicitudes '
                .'respetuosas a las autoridades por motivos de interés general o particular y a obtener '
                .'su pronta resolución.',
            'boton' => 'Envía una petición o un derecho de petición',
        ],
        [
            'icono' => 'queja',
            'titulo' => 'Queja',
            'definicion' => 'Es la manifestación de protesta, censura, descontento o inconformidad que '
                .'formula una persona en relación con una conducta que considera irregular de uno o '
                .'varios servidores públicos en desarrollo de sus funciones.',
            'boton' => 'Envía una queja',
        ],
        [
            'icono' => 'reclamo',
            'titulo' => 'Reclamo',
            'definicion' => 'Es el derecho que tiene toda persona de exigir, reivindicar o demandar una '
                .'solución, ya sea por motivo general o particular, referente a la presentación indebida '
                .'de un servicio o a la falta de atención de una solicitud.',
            'boton' => 'Envía un reclamo',
        ],
        [
            'icono' => 'sugerencia',
            'titulo' => 'Sugerencia',
            'definicion' => 'Es la manifestación de una idea o propuesta para mejorar el servicio o la '
                .'gestión de la entidad.',
            'boton' => 'Envía una sugerencia',
        ],
        [
            'icono' => 'felicitacion',
            'titulo' => 'Felicitación',
            'definicion' => 'Es la manifestación de la alegría y satisfacción de un servicio brindado o '
                .'la gestión de la entidad.',
            'boton' => 'Envía una felicitación',
        ],
        [
            'icono' => 'denuncia',
            'titulo' => 'Denuncia',
            'definicion' => 'Es la puesta en conocimiento ante una autoridad competente de una conducta '
                .'posiblemente irregular, para que se adelante la correspondiente investigación penal, '
                .'disciplinaria, fiscal, administrativa - sancionatoria o ético profesional.',
            'boton' => 'Envía una denuncia',
        ],
        [
            'icono' => 'solicitud-informacion',
            'titulo' => 'Solicitud de información',
            'definicion' => 'Petición formulada para acceder a información pública, sin necesidad de que '
                .'los solicitantes acrediten su personalidad, el tipo de interés, las causas por las '
                .'cuales presentan su solicitud o los fines a los cuales habrán de destinar los datos '
                .'solicitados.',
            'boton' => 'Solicita información',
        ],
        [
            'icono' => 'solicitud-datos',
            'titulo' => 'Solicitud de datos personales',
            'definicion' => 'Es la solicitud de cambio y/o eliminación de información correspondiente a '
                .'los datos personales del usuario que requieran correcciones o actualizaciones.',
            'boton' => 'Envía una solicitud',
        ],
        [
            'icono' => 'cita',
            'titulo' => 'Agenda tu cita',
            'definicion' => 'Reunión de tipo presencial o virtual en caso de tener necesidad de realizar '
                .'un trámite.',
            'boton' => 'Agendar cita',
        ],
    ];

    // Donde el trámite se radica de verdad, hoy.
    $radicacion = rtrim((string) config('huv.legacy_base'), '/').'/peticiones-quejas-reclamos';
@endphp

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">PQRDS Recepción de Solicitudes</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-balance text-heading lg:text-33">
                    Realizar peticiones, quejas, reclamos, sugerencias y denuncias (PQRDS)
                </h1>

                <p class="m-0 mt-3 max-w-[70ch] text-14-5 text-muted">
                    Por favor tenga en cuenta las siguientes definiciones para establecer el tipo de
                    solicitud a presentar y los términos de respuesta.
                </p>

                <h2 id="huv-tipos-de-solicitud"
                    class="m-0 mt-8 font-display text-19 font-bold text-heading lg:text-21">
                    Seleccione el tipo de solicitud que desea registrar
                </h2>

                {{-- Una lista y no una sucesión de <div>: son diez opciones
                     equivalentes, y quien navega con lector de pantalla merece
                     saber cuántas hay antes de recorrerlas. --}}
                <ul aria-labelledby="huv-tipos-de-solicitud" class="m-0 mt-4 flex list-none flex-col p-0">
                    @foreach ($tramites as $tramite)
                        <li class="flex gap-5 border-t border-line py-6">
                            {{-- Decorativo: lo que dice el icono ya está en el
                                 título que tiene al lado. --}}
                            <img src="{{ asset('img/pqrds/'.$tramite['icono'].'.svg') }}" alt=""
                                 aria-hidden="true" width="44" height="52" loading="lazy"
                                 class="huv-pqrds-icono mt-1 hidden h-[52px] w-[44px] shrink-0 object-contain sm:block">

                            <div class="min-w-0 flex-1">
                                <h3 class="m-0 font-display text-17 font-bold text-heading">
                                    {{ $tramite['titulo'] }}
                                </h3>

                                <p class="m-0 mt-1 text-14 leading-[1.6] text-pretty text-body">
                                    {{ $tramite['definicion'] }}
                                </p>

                                <p class="m-0 mt-3">
                                    <a href="{{ $radicacion }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-5 py-[9px]
                                              font-display text-12-5 font-bold tracking-[0.04em] text-on-accent
                                              uppercase no-underline transition-colors hover:bg-azure-dark
                                              hover:no-underline">
                                        {{ $tramite['boton'] }}
                                        <span class="sr-only">(se abre en una pestaña nueva)</span>
                                    </a>
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- ---------------- Seguimiento ---------------- --}}
                <div class="mt-8 rounded-[4px] bg-tint px-5 py-6 lg:px-8">
                    <p class="m-0 max-w-[70ch] text-14-5 leading-[1.6] text-heading">
                        Hazle seguimiento a tu solicitud a través del código generado por el portal cuando
                        llenas el respectivo formulario y envías tu solicitud.
                    </p>

                    <p class="m-0 mt-4">
                        <a href="{{ $radicacion }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-full border-0 bg-azure px-6 py-[10px]
                                  font-display text-12-5 font-bold tracking-[0.04em] text-on-accent uppercase
                                  no-underline transition-colors hover:bg-azure-dark hover:no-underline">
                            Hacer seguimiento
                            <span class="sr-only">(se abre en una pestaña nueva)</span>
                        </a>
                    </p>
                </div>
            </div>
        </x-container>
    </div>
@endsection
