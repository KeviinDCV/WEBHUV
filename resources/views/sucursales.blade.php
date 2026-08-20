@extends('layouts.app')

@section('title', __('paginas.sucursales.titulo').' — '.config('huv.institution.short_name'))
@section('description', __('paginas.sucursales.descripcion', ['entidad' => config('huv.institution.name_plain')]))

@section('content')
    {{--
        Sucursales.

        Una página, no un tema: en el portal vive en «/sucursales» y no bajo
        «/tema/…», y no la sirve la API de contenidos —«branches», «sucursales»,
        «offices» y «sedes» devuelven la cáscara de su aplicación, no datos—.

        Está vacía a propósito, como allí: el hospital no publica ninguna sede
        aparte de la principal, cuya dirección ya está en el pie. Cuando publique
        alguna, aquí es donde entra.
    --}}
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">

            <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                    <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                    <li aria-hidden="true">›</li>
                    <li aria-current="page" class="font-semibold text-heading">{{ __('paginas.sucursales.titulo') }}</li>
                </ol>
            </nav>

            <h1 class="m-0 font-display text-25 leading-[1.2] font-bold tracking-[-0.015em] text-heading lg:text-33">
                {{ __('paginas.sucursales.titulo') }}
            </h1>

        </x-container>
    </div>
@endsection
