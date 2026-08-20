@php
    use App\Models\Content;
    use App\Models\ContentBlock;
    use App\Support\Themes;

    $backUrl = route('home');

    $sections = old('sections', $block->sections->map(fn ($section) => [
        'category' => $section->category,
        'title' => $section->title,
        'hide_in_feed' => $section->hide_in_feed,
    ])->all());
@endphp

@extends('layouts.admin')

@section('title', __('admin-bloques.bloque.titulo'))
@section('heading', __('admin-bloques.bloque.titulo'))
@section('subheading', __('admin-bloques.bloque.descripcion'))

@section('content')
    <form method="POST" action="{{ route('admin.blocks.update', $block) }}"
          x-data="{
              count: {{ (int) old('sections_count', max(1, $block->sections->count())) }},
              theme: @js(old('theme', $block->theme)),
              showTitle: {{ old('show_title', $block->show_title) ? 'true' : 'false' }},
          }">
        @csrf
        @method('PUT')

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
                {{ __('admin-bloques.bloque.nombre_ayuda') }}
            </p>
        </div>

        {{-- ---------------- Selección ---------------- --}}
        <h2 class="m-0 font-display text-15 font-bold text-heading">
            {{ __('admin-bloques.bloque.seleccion') }}
        </h2>

        <div class="mt-3 mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:max-w-[720px]">
            <div>
                <label for="sections_count" class="text-13-5 text-body">{{ __('admin-bloques.bloque.numero') }}</label>
                <input id="sections_count" name="sections_count" type="number" min="1"
                       max="{{ ContentBlock::MAX_SECTIONS }}" x-model.number="count"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>

            <div>
                <label for="sort" class="text-13-5 text-body">{{ __('admin-bloques.bloque.orden.etiqueta') }}</label>
                <select id="sort" name="sort"
                        class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                    @foreach (array_keys(ContentBlock::SORTS) as $value)
                        <option value="{{ $value }}" @selected(old('sort', $block->sort) === $value)>
                            {{ __('admin-bloques.bloque.orden.'.$value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <label for="show_title" class="mb-8 flex items-start gap-2 text-13-5 text-body">
            <input id="show_title" name="show_title" type="checkbox" value="1" x-model="showTitle"
                   class="mt-[3px] size-4 rounded-[2px] border-stroke accent-azure">
            <span>
                {{ __('admin-bloques.bloque.titulo_visible') }}
                <span class="block text-12-5 text-muted">
                    {{ __('admin-bloques.bloque.titulo_visible_ayuda') }}
                </span>
            </span>
        </label>

        {{-- ---------------- Secciones ---------------- --}}
        <h2 class="m-0 font-display text-15 font-bold text-heading">
            {{ __('admin-bloques.bloque.secciones') }}
        </h2>

        <div class="mt-3 mb-8 flex flex-col gap-5">
            @for ($i = 0; $i < ContentBlock::MAX_SECTIONS; $i++)
                @php
                    $section = $sections[$i] ?? ['category' => Content::NEWS_CATEGORY, 'title' => '', 'hide_in_feed' => false];
                    $ordinal = __('admin-bloques.bloque.ordinal.'.['uno', 'dos', 'tres'][$i]);
                @endphp
                <fieldset x-show="count > {{ $i }}" x-cloak
                          class="rounded-[3px] border border-line bg-card p-4">
                    <legend class="px-1 text-12-5 font-semibold text-heading">
                        {{ __('admin-bloques.bloque.seccion', ['ordinal' => $ordinal]) }}
                    </legend>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="section_{{ $i }}_category" class="text-13-5 text-body">
                                {{ __('admin-bloques.bloque.elige', ['ordinal' => $ordinal]) }}
                            </label>
                            <select id="section_{{ $i }}_category" name="sections[{{ $i }}][category]"
                                    :disabled="count <= {{ $i }}"
                                    class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                                @foreach (Content::CATEGORIES as $category)
                                    <option value="{{ $category }}" @selected($section['category'] === $category)>
                                        {{ __('admin-bloques.bloque.ruta_seccion', ['categoria' => Content::categoryLabel($category)]) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="section_{{ $i }}_title" class="text-13-5 text-body">
                                {{ __('admin-bloques.bloque.titulo_seccion') }}
                            </label>
                            <input id="section_{{ $i }}_title" name="sections[{{ $i }}][title]" type="text"
                                   maxlength="150" :required="count > {{ $i }}" :disabled="count <= {{ $i }}"
                                   value="{{ $section['title'] ?: $section['category'] }}"
                                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                        </div>
                    </div>

                    <label for="section_{{ $i }}_hide" class="mt-3 flex items-start gap-2 text-13-5 text-body">
                        <input id="section_{{ $i }}_hide" name="sections[{{ $i }}][hide_in_feed]" type="checkbox"
                               value="1" @checked($section['hide_in_feed']) :disabled="count <= {{ $i }}"
                               class="mt-[3px] size-4 rounded-[2px] border-stroke accent-azure">
                        <span>
                            {{ __('admin-bloques.bloque.ocultar') }}
                            <span class="block text-12-5 text-muted">
                                {{ __('admin-bloques.bloque.ocultar_ayuda') }}
                            </span>
                        </span>
                    </label>
                </fieldset>
            @endfor
        </div>

        {{-- ---------------- Tema ---------------- --}}
        <h2 class="m-0 mb-1 font-display text-15 font-bold text-heading">
            {{ __('admin-bloques.bloque.tema.titulo') }}
        </h2>
        <p class="m-0 mb-4 text-13-5 text-muted">
            {{ __('admin-bloques.bloque.tema.descripcion') }}
        </p>

        <div class="mb-9 flex flex-wrap items-start gap-8">
            <div class="flex w-[280px] overflow-hidden rounded-[3px]"
                 :style="{ backgroundColor: @js(Themes::COLORS)[theme] }">
                <span class="flex w-[80px] shrink-0 items-center justify-center text-white/90">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3v5h5" />
                        <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                    </svg>
                </span>
                <span class="flex-1 bg-white/10 px-4 py-5 text-white">
                    <span class="block font-display text-14 font-bold">
                        {{ __('admin-bloques.bloque.tema.vista_titulo') }}
                    </span>
                    <span class="block text-13">{{ __('admin-bloques.bloque.tema.vista_texto') }}</span>
                </span>
            </div>

            <fieldset class="border-0 p-0">
                <legend class="sr-only">{{ __('admin-bloques.bloque.tema.etiqueta') }}</legend>
                <div class="flex flex-wrap gap-3">
                    @foreach (Themes::COLORS as $key => $color)
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
