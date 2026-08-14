@extends('layouts.app')

@section('title', 'Mecanismos de contacto — '.config('huv.institution.short_name'))
@section('description', 'Dirección, teléfonos, correos y horario de atención del '
    .config('huv.institution.name_plain').'.')

@php
    /*
     | Los mismos datos del pie, con los rótulos y el orden de esta página.
     |
     | Se toman por clave y no se vuelven a escribir: son los teléfonos de una
     | entidad pública, y tenerlos en dos sitios es garantizar que un día digan
     | cosas distintas. El pie aparece justo debajo, así que cualquier desajuste
     | se vería a simple vista.
     |
     | «Línea anticorrupción» no está: el portal la publica en el pie pero no
     | aquí.
     */
    $filas = collect(config('huv.footer.contact'))->keyBy('key');

    $mecanismos = [
        'direccion' => 'Dirección',
        'conmutador' => 'Teléfono',
        'linea-gratuita' => 'Línea de atención gratuita',
        'correo' => 'Email',
        'correo-judicial' => 'Notificaciones Judiciales',
        'horario' => 'Horario de atención',
    ];
@endphp

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                <nav aria-label="Ruta de navegación" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">Inicio</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">Mecanismos de contacto</li>
                    </ol>
                </nav>

                <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                    Mecanismos de contacto
                </h1>

                {{-- <address> y <dl>: son los datos de contacto de la entidad, y
                     cada rótulo describe el dato que tiene al lado. --}}
                <address class="not-italic">
                    <dl class="m-0 mt-7 flex flex-col gap-4 text-15 leading-[1.6]">
                        @foreach ($mecanismos as $clave => $rotulo)
                            @php($fila = $filas->get($clave))

                            @if ($fila)
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="shrink-0 font-semibold text-heading">{{ $rotulo }}:</dt>
                                    <dd class="m-0 min-w-0 text-body">
                                        @if (! empty($fila['tel']))
                                            <a href="tel:{{ $fila['tel'] }}"
                                               class="text-link underline underline-offset-2">{{ $fila['value'] }}</a>
                                        @elseif (! empty($fila['mailto']))
                                            <a href="mailto:{{ $fila['mailto'] }}"
                                               class="break-all text-link underline underline-offset-2">{{ $fila['value'] }}</a>
                                        @else
                                            {{ $fila['value'] }}
                                        @endif
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </address>

                {{-- El portal enlaza aquí un acortador que lleva al CROSS del
                     hospital, su sistema de radicación. Se escribe el destino
                     final: un acortador esconde a dónde se va y deja el enlace
                     a merced de un servicio de terceros. --}}
                <p class="m-0 mt-8">
                    <a href="{{ config('huv.contact.request_form') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="font-display text-15 font-semibold text-link underline underline-offset-4">
                        Formulario electrónico de solicitudes, peticiones, quejas, reclamos y denuncias
                        <span class="sr-only">(se abre en una pestaña nueva)</span>
                    </a>
                </p>
            </div>
        </x-container>
    </div>
@endsection
