@extends('layouts.app')

@section('content')
    {{--
        El encabezado principal de la portada.

        Va oculto a la vista porque el portal no lo dibuja —el logotipo hace de
        título— pero la página tiene que declarar de qué trata: era la única de
        las quince del sitio sin <h1>, y justo la que más peso tiene. Quien
        navega con lector de pantalla y pide «ir al encabezado principal» no
        encontraba nada.

        Es la razón social, que es contenido del portal y está en español: de ahí
        el componente, que le declara el idioma cuando la página va en inglés.
    --}}
    <x-texto-del-portal tag="h1" class="sr-only">{{ config('huv.institution.name_plain') }}</x-texto-del-portal>

    {{-- El orden reproduce el del portal institucional: banner, noticias,
         accesos directos, listado de contenidos, agenda y entidades. --}}
    @include('sections.hero')
    @include('sections.news')
    @include('sections.quick-links')
    @include('sections.content-feed')
    @include('sections.events')
    @include('sections.bulletins')
    @include('sections.partners')
@endsection
