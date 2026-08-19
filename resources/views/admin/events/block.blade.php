@php
    use App\Models\ContentBlock;

    $backUrl = route('home');
    $chosen = old('categories', $block->option('categories', []));
@endphp

@extends('layouts.admin')

@section('title', 'Configuración del bloque de eventos')
@section('heading', 'Configuración del bloque')

@section('content')
    <form method="POST" action="{{ route('admin.events.block.update') }}" class="max-w-[720px]">
        @csrf
        @method('PUT')

        <div class="mb-7">
            <label for="name" class="text-13-5 font-semibold text-heading">
                Nombre del bloque <span class="font-normal text-muted">(30 caracteres)</span>
            </label>
            <input id="name" name="name" type="text" maxlength="30" required
                   value="{{ old('name', $block->name) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            <p class="m-0 mt-1 text-12-5 text-faint">Es el título que se ve sobre el calendario.</p>
        </div>

        <div class="mb-7">
            <label for="source" class="text-13-5 font-semibold text-heading">
                Selecciona una sección <span aria-hidden="true">*</span>
            </label>
            <select id="source" name="source" required
                    class="mt-1 w-full max-w-[420px] rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                @foreach (ContentBlock::EVENT_SOURCES as $slug => $rotulo)
                    <option value="{{ $slug }}" @selected($block->option('source') === $slug)>
                        {{ $rotulo }}
                    </option>
                @endforeach
            </select>
        </div>

        <fieldset class="mb-8 border-0 p-0">
            <legend class="p-0 text-13-5 font-semibold text-heading">
                Selecciona una o varias categorías <span class="font-normal text-muted">(opcional)</span>
            </legend>
            <p class="m-0 mt-1 mb-3 text-12-5 text-muted">
                Sin ninguna marcada, el calendario muestra toda la agenda.
            </p>

            {{-- Son las del tema que alimenta el calendario, no una lista
                 aparte: las mismas que se ven en su listado y las que la
                 importación mantiene al día. Por eso no se crean aquí. --}}
            @if ($categories->isEmpty())
                <p class="m-0 text-13-5 text-muted">
                    La sección elegida todavía no tiene categorías. Se crean al editar
                    sus contenidos.
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
                Cancelar
            </a>
            <button type="submit"
                    class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14 font-semibold
                           text-on-accent transition-colors hover:bg-azure-dark">
                Guardar
            </button>
        </div>
    </form>

@endsection
