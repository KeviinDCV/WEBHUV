@php
    $backUrl = route('admin.menu.index');
@endphp

@extends('layouts.admin')

@section('title', __('admin-usuarios.titulo'))
@section('heading', __('admin-usuarios.titulo'))
@section('subheading', __('admin-usuarios.subtitulo'))

@section('content')
    <a href="{{ route('admin.users.create') }}"
       class="mb-7 inline-flex items-center gap-2 rounded-full border border-rule-accent bg-card
              px-5 py-[9px] font-display text-14 font-semibold text-link no-underline
              hover:bg-tint hover:no-underline">
        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
        </svg>
        {{ __('admin-usuarios.listado.nueva') }}
    </a>

    {{-- Una tabla de verdad y no una lista de cajas: son tres datos por fila y
         la relación entre columnas es justo lo que se viene a mirar. --}}
    <div class="overflow-x-auto rounded-[3px] border border-stroke bg-card">
        <table class="w-full border-collapse text-14">
            <caption class="sr-only">{{ __('admin-usuarios.titulo') }}</caption>

            <thead>
                <tr class="border-b border-stroke text-left">
                    <th scope="col" class="px-4 py-3 font-display text-13 font-bold text-heading">
                        {{ __('admin-usuarios.listado.columna_nombre') }}
                    </th>
                    <th scope="col" class="px-4 py-3 font-display text-13 font-bold text-heading">
                        {{ __('admin-usuarios.listado.columna_correo') }}
                    </th>
                    <th scope="col" class="px-4 py-3 font-display text-13 font-bold text-heading">
                        {{ __('admin-usuarios.listado.columna_rol') }}
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($usuarios as $usuario)
                    <tr class="border-b border-divider last:border-b-0">
                        <td class="px-4 py-[11px] text-ink">
                            {{ $usuario->name }}
                            @if ($usuario->is(auth()->user()))
                                <span class="ml-1 text-12-5 text-faint">({{ __('admin-usuarios.listado.usted') }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-[11px] break-all text-body">{{ $usuario->email }}</td>
                        <td class="px-4 py-[11px]">
                            <span @class([
                                'rounded-full px-3 py-[3px] text-12-5 font-semibold',
                                'bg-navy text-on-brand' => $usuario->isAdmin(),
                                'bg-tint text-heading' => ! $usuario->isAdmin(),
                            ])>
                                {{ $usuario->roleLabel() }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="m-0 mt-4 max-w-[70ch] text-13-5 text-muted">
        {{ __('admin-usuarios.listado.sin_edicion') }}
    </p>
@endsection
