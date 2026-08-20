@php
    use App\Models\Shortcut;
    use App\Models\ShortcutBlock;

    $backUrl = route('home');
@endphp

@extends('layouts.admin')

@section('title', __('admin-bloques.barra.titulo'))
@section('heading', __('admin-bloques.barra.titulo'))

@section('content')
    <div x-data="huvBannerOrder(@js($block->shortcuts->pluck('id')), @js(__('admin-bloques.orden_movido')))">

        <form method="POST" action="{{ route('admin.shortcuts.update', $block) }}">
            @csrf
            @method('PUT')

            <template x-for="id in ids" :key="id">
                <input type="hidden" name="order[]" :value="id">
            </template>

            {{-- ---------------- Nombre ---------------- --}}
            <div class="mb-8 max-w-[560px]">
                <label for="name" class="text-13-5 font-semibold text-heading">
                    {{ __('admin-bloques.comun.nombre_bloque') }}
                    <span class="font-normal text-muted">{{ __('admin-bloques.comun.limite_30') }}</span>
                </label>
                <input id="name" name="name" type="text" maxlength="30" required
                       value="{{ old('name', $block->name) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
                <p class="m-0 mt-1 text-12-5 text-faint">
                    {{ __('admin-bloques.barra.nombre_ayuda') }}
                </p>
            </div>

            {{-- ---------------- Accesos ---------------- --}}
            <h2 class="m-0 font-display text-15 font-bold text-heading">
                {{ __('admin-bloques.barra.accesos') }}
            </h2>
            <p class="m-0 mt-1 mb-4 text-13-5 text-muted">
                {{ __('admin-bloques.barra.minimo', ['minimo' => ShortcutBlock::MIN_TO_PUBLISH]) }}
            </p>

            @if ($block->hasRoom())
                <a href="{{ route('admin.shortcuts.create', $block) }}"
                   class="mb-6 inline-flex items-center gap-2 rounded-full border border-rule-accent bg-card
                          px-5 py-[9px] font-display text-14 font-semibold text-link no-underline
                          hover:bg-tint hover:no-underline">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('admin-bloques.acciones.agregar') }}
                </a>
            @else
                <p class="mb-6 rounded-[3px] border border-line bg-card px-4 py-3 text-13-5 text-muted">
                    {{ __('admin-bloques.barra.completo', ['maximo' => ShortcutBlock::MAX_SHORTCUTS]) }}
                </p>
            @endif

            @if ($block->shortcuts->isEmpty())
                <p class="rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    {{ __('admin-bloques.barra.vacio') }}
                </p>
            @else
                @if (! $block->isPublishable())
                    <p class="mb-4 rounded-[3px] border border-line border-l-4 border-l-warning bg-card px-4 py-3
                              text-13-5 text-body">
                        {{ __('admin-bloques.barra.pendiente', [
                            'actuales' => $block->shortcuts->count(),
                            'faltan' => ShortcutBlock::MIN_TO_PUBLISH - $block->shortcuts->count(),
                        ]) }}
                    </p>
                @endif

                <ul class="flex flex-col gap-px bg-line">
                    @foreach ($block->shortcuts as $shortcut)
                        <li :style="{ order: position({{ $shortcut->id }}) }"
                            class="flex flex-wrap items-center gap-x-5 gap-y-2 bg-card px-4 py-3">

                            <div class="flex shrink-0 flex-col">
                                <button type="button" @click="move({{ $shortcut->id }}, -1)"
                                        x-show="! isFirst({{ $shortcut->id }})"
                                        aria-label="{{ __('admin-bloques.barra.subir', ['texto' => $shortcut->label]) }}"
                                        class="flex size-6 items-center justify-center border-0 bg-transparent text-link hover:text-heading">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m5 15 7-7 7 7" />
                                    </svg>
                                </button>
                                <button type="button" @click="move({{ $shortcut->id }}, 1)"
                                        x-show="! isLast({{ $shortcut->id }})"
                                        aria-label="{{ __('admin-bloques.barra.bajar', ['texto' => $shortcut->label]) }}"
                                        class="flex size-6 items-center justify-center border-0 bg-transparent text-link hover:text-heading">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m5 9 7 7 7-7" />
                                    </svg>
                                </button>
                            </div>

                            <span class="w-4 shrink-0 font-display text-15 font-bold text-heading"
                                  x-text="position({{ $shortcut->id }})">{{ $loop->iteration }}</span>

                            <x-quick-icon :name="$shortcut->icon" class="size-6 shrink-0 text-muted" />

                            <div class="min-w-[220px] flex-1">
                                <p class="m-0 text-13-5 font-semibold text-heading">{{ $shortcut->label }}</p>
                                <p class="m-0 text-12-5 break-all text-muted">{{ $shortcut->resolvedUrl() }}</p>
                            </div>

                            <a href="{{ route('admin.shortcuts.item.edit', [$block, $shortcut]) }}"
                               class="shrink-0 text-13-5 font-semibold text-link underline underline-offset-4">
                                {{ __('admin-bloques.acciones.editar') }}<span class="sr-only">
                                    {{ __('admin-bloques.barra.editar_detalle', ['texto' => $shortcut->label]) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <p class="sr-only" aria-live="polite" x-text="announcement"></p>
            @endif

            {{-- ---------------- Tema ---------------- --}}
            <h2 class="mt-9 mb-1 font-display text-15 font-bold text-heading">
                {{ __('admin-bloques.barra.tema.titulo') }}
            </h2>
            <p class="m-0 mb-4 text-13-5 text-muted">
                {{ __('admin-bloques.barra.tema.descripcion') }}
            </p>

            <div x-data="{ theme: @js(old('theme', $block->theme)) }" class="flex flex-wrap items-start gap-8">
                {{-- Vista previa --}}
                <div class="flex w-[170px] flex-col items-center gap-2 rounded-[3px] border border-line bg-card px-4 py-6">
                    <template x-for="[key, color] in Object.entries(@js(ShortcutBlock::THEMES))" :key="key">
                        <svg x-show="theme === key" class="size-8" viewBox="0 0 24 24" fill="none"
                             :stroke="color" stroke-width="1.6" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 11v5.5" />
                            <path d="M12 7.6h.01" />
                        </svg>
                    </template>
                    <span class="text-center font-display text-12-5 font-bold text-heading">
                        {{ __('admin-bloques.comun.nombre_acceso') }}
                    </span>
                </div>

                <fieldset class="border-0 p-0">
                    <legend class="sr-only">{{ __('admin-bloques.barra.tema.etiqueta') }}</legend>
                    <div class="flex flex-wrap gap-3">
                        @foreach (ShortcutBlock::THEMES as $key => $color)
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="theme" value="{{ $key }}" x-model="theme"
                                       class="size-4 accent-azure">
                                <span class="flex size-8 items-center justify-center rounded-[3px] font-display
                                             text-14 font-bold text-white"
                                      style="background: {{ $color }}" aria-hidden="true">T</span>
                                <span class="sr-only">{{ Str::headline($key) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>

            <div class="mt-9 flex flex-wrap gap-3">
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
    </div>
@endsection
