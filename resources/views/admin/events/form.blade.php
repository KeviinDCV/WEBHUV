@php
    $editing = $event->exists;
    $backUrl = route('home').'#eventos';
    $chosen = old('categories', $editing ? $event->categories->pluck('id')->all() : []);
@endphp

@extends('layouts.admin')

@section('title', $editing ? 'Editar evento' : 'Nuevo evento')
@section('heading', $editing ? 'Editar evento' : 'Nuevo evento')

@section('content')
    <form method="POST" class="max-w-[720px]"
          action="{{ $editing ? route('admin.events.update', $event) : route('admin.events.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="mb-6">
            <label for="title" class="text-13-5 font-semibold text-heading">
                Título <span class="font-normal text-muted">(150 caracteres)</span>
            </label>
            <input id="title" name="title" type="text" maxlength="150" required
                   value="{{ old('title', $event->title) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="starts_at" class="text-13-5 font-semibold text-heading">Inicio</label>
                <input id="starts_at" name="starts_at" type="datetime-local" required
                       value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>
            <div>
                <label for="ends_at" class="text-13-5 font-semibold text-heading">
                    Fin <span class="font-normal text-muted">(opcional)</span>
                </label>
                <input id="ends_at" name="ends_at" type="datetime-local"
                       value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                <p class="m-0 mt-1 text-12-5 text-faint">
                    Si abarca varios días, aparecerá en todos ellos.
                </p>
            </div>
        </div>

        <div class="mb-6">
            <label for="place" class="text-13-5 font-semibold text-heading">Lugar</label>
            <input id="place" name="place" type="text" maxlength="200"
                   value="{{ old('place', $event->place) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <div class="mb-6">
            <label for="url" class="text-13-5 font-semibold text-heading">Enlace</label>
            <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
                Inscripción, transmisión o más información. Debe incluir http:// o https://
            </p>
            <input id="url" name="url" type="url" inputmode="url" placeholder="https://"
                   value="{{ old('url', $event->url) }}"
                   class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        <div class="mb-6">
            <label for="description" class="text-13-5 font-semibold text-heading">
                Descripción <span class="font-normal text-muted">(opcional)</span>
            </label>
            <textarea id="description" name="description" rows="4" maxlength="2000"
                      class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-2 text-14 text-ink">{{ old('description', $event->description) }}</textarea>
        </div>

        <fieldset class="mb-6 border-0 p-0">
            <legend class="p-0 text-13-5 font-semibold text-heading">Categorías</legend>
            @if ($categories->isEmpty())
                <p class="m-0 mt-2 text-13-5 text-muted">
                    No hay categorías todavía. Se crean desde la configuración del bloque.
                </p>
            @else
                <div class="mt-2 flex flex-col gap-2">
                    @foreach ($categories as $category)
                        <label for="cat_{{ $category->id }}" class="flex items-center gap-2 text-13-5 text-body">
                            <input id="cat_{{ $category->id }}" name="categories[]" type="checkbox"
                                   value="{{ $category->id }}" @checked(in_array($category->id, $chosen))
                                   class="size-4 rounded-[2px] border-stroke accent-azure">
                            {{ $category->name }}
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>

        <label for="is_active" class="mb-8 flex items-start gap-2 text-13-5 text-body">
            <input id="is_active" name="is_active" type="checkbox" value="1"
                   @checked(old('is_active', $editing ? $event->is_active : true))
                   class="mt-[3px] size-4 rounded-[2px] border-stroke accent-azure">
            <span>
                Activo
                <span class="block text-12-5 text-muted">
                    Si se desactiva, deja de verse en el calendario público.
                </span>
            </span>
        </label>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('home') }}#eventos"
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

    @if ($editing)
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-5"
              onsubmit="return confirm('¿Eliminar este evento? La acción no se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="border-0 bg-transparent p-0 text-13-5 font-semibold text-danger underline underline-offset-4">
                Eliminar evento
            </button>
        </form>
    @endif
@endsection
