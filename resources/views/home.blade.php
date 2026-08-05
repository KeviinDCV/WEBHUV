@extends('layouts.app')

@section('content')
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
