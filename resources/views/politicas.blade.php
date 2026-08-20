@extends('layouts.app')

@section('title', __('paginas.politicas.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.politicas.descripcion', [
    'entidad' => config('huv.institution.name_plain'),
]))

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

                <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.politicas.titulo') }}</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    {{ __('paginas.politicas.titulo') }}
                </h1>

                {{-- ---------------- Política del hospital ---------------- --}}
                <section aria-labelledby="huv-derechos-de-autor" class="huv-prose mt-8">
                    <h2 id="huv-derechos-de-autor">
                        {{ __('paginas.politicas.derechos.titulo') }}
                    </h2>

                    <p>
                        {{ __('paginas.politicas.derechos.texto', ['entidad' => config('huv.institution.name')]) }}
                    </p>
                </section>

                {{-- ---------------- Política de la plataforma ---------------- --}}
                <section aria-labelledby="huv-mi-colombia-digital" class="huv-prose mt-10">
                    <h2 id="huv-mi-colombia-digital">Mi Colombia Digital</h2>

                    <p>{{ __('paginas.politicas.plataforma.intro') }}</p>

                    @php
                        /*
                         | Los nueve del portal, en su orden y con su destino.
                         |
                         | Los rótulos no pasan por los ficheros de idioma: nombran documentos
                         | legales que «Mi Colombia Digital» publica solo en español, y un rótulo
                         | traducido prometería una versión inglesa que no existe. Por eso mismo
                         | van marcados con su idioma: son español dentro de una página inglesa.
                         */
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
                                    <x-texto-del-portal>{{ $titulo }}</x-texto-del-portal>
                                    <span class="sr-only">{{ __('paginas.enlace.pestana_nueva') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        </x-container>
    </div>
@endsection
