@extends('layouts.app')

@section('title', 'Políticas — '.config('huv.institution.short_name'))
@section('description', 'Política de derechos de autor y autorización de uso de contenidos del '
    .config('huv.institution.name_plain').'.')

@section('content')
    {{--
        Políticas.

        Una página, no un tema: en el portal vive en «/politicas» y su texto no
        sale de la API de contenidos sino de «policiesterms», que devuelve dos
        bloques —el del hospital y el de la plataforma— y se pintan uno detrás
        del otro. Aquí van escritos, como las sucursales: son dos textos legales
        que cambian una vez cada varios años.
    --}}
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">Políticas</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    Políticas
                </h1>

                {{-- ---------------- Política del hospital ---------------- --}}
                <section aria-labelledby="huv-derechos-de-autor" class="huv-prose mt-8">
                    <h2 id="huv-derechos-de-autor">
                        Política de derechos de autor y autorización de uso de contenidos
                    </h2>

                    <p>
                        El {{ config('huv.institution.name') }} establece que todos los contenidos
                        producidos o administrados en el marco de su actividad institucional constituyen
                        activos estratégicos cuya creación, uso, divulgación y explotación deben someterse
                        a principios de responsabilidad, legalidad, seguridad y protección institucional.
                        En consecuencia, los derechos patrimoniales derivados de los contenidos generados
                        por servidores públicos, contratistas, docentes, residentes o estudiantes en el
                        ejercicio de sus funciones pertenecen al hospital, sin perjuicio del reconocimiento
                        de los derechos morales de autor. Ningún contenido institucional podrá ser
                        reproducido, publicado, transformado, licenciado, divulgado por cualquier medio o
                        puesto a disposición del público sin la autorización expresa, previa y escrita de
                        las dependencias facultadas para ello. El manejo del contenido deberá responder a
                        estándares de seguridad de la información, calidad del dato, gestión documental y
                        protección del dato personal y clínico, asegurando la trazabilidad, conservación y
                        uso ético del conocimiento generado dentro de la institución.
                    </p>
                </section>

                {{-- ---------------- Política de la plataforma ---------------- --}}
                <section aria-labelledby="huv-mi-colombia-digital" class="huv-prose mt-10">
                    <h2 id="huv-mi-colombia-digital">Mi Colombia Digital</h2>

                    <p>
                        A continuación podrás consultar los términos y condiciones y las políticas de
                        privacidad de información y el tratamiento de datos personales de la solución que
                        debes tener en cuenta para el uso correcto del servicio de portales territoriales
                        ofrecidos por el Gobierno Digital; recuerda dar clic en los títulos para conocer
                        más:
                    </p>

                    @php
                        // Los nueve del portal, en su orden y con su destino.
                        $soporte = 'https://micolombiadigital.gov.co/soporte/';

                        $terminos = [
                            'Sobre las bases de datos.' => 'sobre-las-bases-de-datos',
                            'Sobre la adquisición de información.' => 'sobre-la-adquisicion-de-informacion',
                            'Sobre las copias de seguridad.' => 'sobre-las-copias-de-seguridad',
                            'Sobre el Registro de Usuario.' => 'sobre-el-registro-de-usuario',
                            'Gestión de Sesiones Seguras.' => 'gestion-de-sesiones-seguras',
                            'Términos y condiciones de uso Mi Colombia Digital.'
                                => 'terminos-y-condiciones-de-uso---mi-colombia-digital',
                            'Políticas de privacidad y tratamiento de datos.'
                                => 'politicas-de-privacidad-y-tratamiento-de-datos',
                            'Términos y condiciones de uso - Cuentas de correo'
                                => 'terminos-y-condiciones-de-uso---cuentas-de-correo',
                            'Uso de Cookies - Mi Colombia Digital.'
                                => 'politica-de-cookies----mi-colombia-digital',
                        ];
                    @endphp

                    <ul>
                        @foreach ($terminos as $titulo => $ruta)
                            <li>
                                <a href="{{ $soporte.$ruta }}" target="_blank" rel="noopener noreferrer">
                                    {{ $titulo }}
                                    <span class="sr-only">(se abre en una pestaña nueva)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        </x-container>
    </div>
@endsection
