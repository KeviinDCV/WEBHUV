@php
    use App\Models\Shortcut;

    $editing = $shortcut->exists;
    $backUrl = route('admin.shortcuts.edit', $block);
@endphp

@extends('layouts.admin')

@section('title', $editing ? 'Editar acceso directo' : 'Nuevo acceso directo')
@section('heading', $editing ? 'Editar acceso directo' : 'Nuevo acceso directo')
@section('subheading', 'Barra «'.$block->name.'»')

@section('content')
    <form method="POST"
          x-data="{
              label: @js(old('label', $shortcut->label ?? '')),
              icon: @js(old('icon', $shortcut->icon ?? 'info')),
          }"
          action="{{ $editing
              ? route('admin.shortcuts.item.update', [$block, $shortcut])
              : route('admin.shortcuts.store', $block) }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_200px]">
            <div>
                <div class="mb-6">
                    <label for="label" class="text-13-5 font-semibold text-heading">
                        Nombre <span class="font-normal text-muted">(40 caracteres)</span>
                    </label>
                    <input id="label" name="label" type="text" maxlength="40" required x-model="label"
                           class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                </div>

                <div class="mb-6">
                    <label for="url" class="text-13-5 font-semibold text-heading">Enlace</label>
                    <p class="m-0 mt-1 mb-2 text-12-5 leading-[1.6] text-muted">
                        Una dirección completa con http:// o https://, o una ruta del portal que
                        empiece por «/» —por ejemplo <code>/tema/ciau</code>—. Las rutas se sirven
                        del portal actual hasta que esa sección exista aquí; entonces pasarán a
                        resolverse contra este sitio sin tocar nada.
                    </p>
                    <input id="url" name="url" type="text" maxlength="2048" required
                           value="{{ old('url', $shortcut->url) }}"
                           placeholder="https://…  o  /tema/…"
                           class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                    @if ($editing)
                        <p class="m-0 mt-2 text-12-5 break-all text-faint">
                            Destino actual: {{ $shortcut->resolvedUrl() }}
                        </p>
                    @endif
                </div>

                <fieldset class="border-0 p-0">
                    <legend class="p-0 text-13-5 font-semibold text-heading">Icono</legend>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach (Shortcut::ICONS as $key => $name)
                            <label class="flex cursor-pointer flex-col items-center gap-1 rounded-[3px] border p-3"
                                   :class="icon === '{{ $key }}' ? 'border-azure bg-tint' : 'border-line bg-card'">
                                <input type="radio" name="icon" value="{{ $key }}" x-model="icon" class="sr-only">
                                <x-quick-icon :name="$key" class="size-6 text-link" />
                                <span class="text-11-5 text-muted">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            {{-- Vista previa: se ve igual que en la portada. --}}
            <div class="lg:sticky lg:top-6">
                <p class="m-0 mb-2 font-display text-12-5 font-bold tracking-[0.06em] text-heading uppercase">
                    Vista previa
                </p>
                <div class="flex flex-col items-center gap-3 rounded-[3px] border border-line bg-card px-3 py-6 text-center">
                    @foreach (Shortcut::ICONS as $key => $name)
                        <template x-if="icon === '{{ $key }}'">
                            <span style="color: {{ $block->themeColor() }}">
                                <x-quick-icon :name="$key" class="size-7" />
                            </span>
                        </template>
                    @endforeach
                    <span class="font-display text-12-5 leading-[1.35] font-bold text-heading"
                          x-text="label || 'Nombre de acceso'"></span>
                </div>
            </div>
        </div>

        <div class="mt-9 flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.shortcuts.edit', $block) }}"
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
        <form method="POST" action="{{ route('admin.shortcuts.item.destroy', [$block, $shortcut]) }}" class="mt-5"
              onsubmit="return confirm('¿Eliminar «{{ addslashes($shortcut->label) }}»?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="border-0 bg-transparent p-0 text-13-5 font-semibold text-danger underline underline-offset-4">
                Eliminar acceso directo
            </button>
        </form>
    @endif
@endsection
