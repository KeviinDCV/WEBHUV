@php $backUrl = route('home').'#huv-contenidos'; @endphp

@extends('layouts.admin')

@section('title', $content->exists ? 'Actualizar contenido' : 'Nuevo contenido')
@section('heading', $content->exists ? 'Actualizar contenido' : 'Nuevo contenido')

@push('head')
    @vite('resources/js/admin.js')
@endpush

@section('content')
    {{-- Pantalla propia del editor. Lo habitual es editar desde la portada,
         pero esta ruta sigue existiendo para enlaces directos. --}}
    @include('admin.contents.partials.editor', ['content' => $content, 'uid' => ''])
@endsection
