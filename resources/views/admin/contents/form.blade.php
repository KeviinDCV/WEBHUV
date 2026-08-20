@php $backUrl = route('home').'#huv-contenidos'; @endphp

@extends('layouts.admin')

@php
    $screenTitle = $content->exists
        ? __('admin-contenidos.pantalla.actualizar')
        : __('admin-contenidos.pantalla.nuevo');
@endphp

@section('title', $screenTitle)
@section('heading', $screenTitle)

@push('head')
    @vite('resources/js/admin.js')
@endpush

@section('content')
    {{-- Pantalla propia del editor. Lo habitual es editar desde la portada,
         pero esta ruta sigue existiendo para enlaces directos. --}}
    @include('admin.contents.partials.editor', ['content' => $content, 'uid' => ''])
@endsection
