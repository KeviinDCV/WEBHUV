@php
    use App\Models\ContentBlock;

    $backUrl = route('home');
    $chosen = old('categories', $block->option('categories', []));
@endphp

@extends('layouts.admin')

@section('title', __('admin-bloques.eventos.titulo'))
@section('heading', __('admin-bloques.eventos.encabezado'))

@section('content')
    <form method="POST" action="{{ route('admin.events.block.update') }}" class="max-w-[720px]">
        @csrf
        @method('PUT')

        <div class="mb-7">
            <label for="name" class="text-13-5 font-semibold text-heading">
                {{ __('admin-bloques.comun.nombre_bloque') }}
                <span class="font-normal text-muted">{{ __('admin-bloques.comun.limite_30') }}</span>
            </label>
            <input id="name" name="name" type="text" maxlength="30" required
                   value="{{ old('name', $block->name) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            <p class="m-0 mt-1 text-12-5 text-faint">{{ __('admin-bloques.eventos.nombre_ayuda') }}</p>
        </div>

        <div class="mb-7">
            <label for="source" class="text-13-5 font-semibold text-heading">
                {{ __('admin-bloques.eventos.seccion') }} <span aria-hidden="true">*</span>
            </label>
            <select id="source" name="source" required
                    class="mt-1 w-full max-w-[420px] rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                {{-- El nombre del tema es contenido del portal, en español: se
                     declara su idioma para que un lector de pantalla en inglés
                     no lo lea con fonética inglesa. --}}
                @foreach ($sources as $slug => $rotulo)
                    <option value="{{ $slug }}" @selected($block->option('source') === $slug){!! App\Support\PortalLang::attribute() !!}>
                        {{ $rotulo }}
                    </option>
                @endforeach
            </select>
        </div>

        <fieldset class="mb-8 border-0 p-0">
            <legend class="p-0 text-13-5 font-semibold text-heading">
                {{ __('admin-bloques.eventos.categorias') }}
                <span class="font-normal text-muted">{{ __('admin-bloques.eventos.opcional') }}</span>
            </legend>
            <p class="m-0 mt-1 mb-3 text-12-5 text-muted">
                {{ __('admin-bloques.eventos.categorias_ayuda') }}
            </p>

            {{-- Son las del tema que alimenta el calendario, no una lista
                 aparte: las mismas que se ven en su listado y las que la
                 importación mantiene al día. Por eso no se crean aquí. --}}
            @if ($categories->isEmpty())
                <p class="m-0 text-13-5 text-muted">
                    {{ __('admin-bloques.eventos.sin_categorias') }}
                </p>
            @else
                <div class="flex flex-col gap-2">
                    @foreach ($categories as $category)
                        <label for="category_{{ $category->id }}" class="flex items-center gap-2 text-13-5 text-body">
                            <input id="category_{{ $category->id }}" name="categories[]" type="checkbox"
                                   value="{{ $category->id }}" @checked(in_array($category->id, $chosen))
                                   class="size-4 rounded-[2px] border-stroke accent-azure">
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('home') }}"
               class="rounded-full border border-stroke bg-card px-6 py-[10px] font-display text-14
                      font-semibold text-heading no-underline hover:bg-tint hover:no-underline">
                {{ __('admin-bloques.acciones.cancelar') }}
            </a>
            <button type="submit"
                    class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14 font-semibold
                           text-on-accent transition-colors hover:bg-azure-dark">
                {{ __('admin-bloques.acciones.guardar') }}
            </button>
        </div>
    </form>

@endsection
