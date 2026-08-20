@php
    use App\Models\Banner;

    $editing = $banner->exists;
    $backUrl = route('admin.banners.index');

    $state = [
        'imageUrl' => $banner->imageUrl(),
        'filterColor' => old('filter_color', $banner->filter_color),
        'filterOpacity' => (int) old('filter_opacity', $banner->filter_opacity),
        'title' => old('title', $banner->title ?? ''),
        'titleColor' => old('title_color', $banner->title_color),
        'titleBackground' => old('title_background', $banner->title_background ?? ''),
        'titleFont' => old('title_font', $banner->title_font),
        'titleBold' => (bool) old('title_bold', $banner->title_bold),
        'titleItalic' => (bool) old('title_italic', $banner->title_italic),
        'subtitle' => old('subtitle', $banner->subtitle ?? ''),
        'subtitleColor' => old('subtitle_color', $banner->subtitle_color),
        'subtitleBackground' => old('subtitle_background', $banner->subtitle_background ?? ''),
        'subtitleFont' => old('subtitle_font', $banner->subtitle_font),
        'subtitleBold' => (bool) old('subtitle_bold', $banner->subtitle_bold),
        'subtitleItalic' => (bool) old('subtitle_italic', $banner->subtitle_italic),
        'alignment' => old('alignment', $banner->alignment),
        'altText' => old('alt_text', $banner->alt_text ?? ''),
    ];
@endphp

@extends('layouts.admin')

@section('title', $editing ? __('admin-bloques.banner.titulo_editar') : __('admin-bloques.banner.titulo_nuevo'))
@section('heading', __('admin-bloques.banner.encabezado'))
@section('subheading', $editing
    ? __('admin-bloques.banner.descripcion_editar')
    : __('admin-bloques.banner.descripcion_nuevo'))

@section('content')
    <div x-data="huvBannerForm(@js($state))">

        {{-- ---------------- Vista previa ---------------- --}}
        <p class="m-0 mb-2 font-display text-13 font-bold tracking-[0.06em] text-heading uppercase">
            {{ __('admin-bloques.acciones.vista_previa') }}
        </p>
        <div class="relative overflow-hidden rounded-[4px] border border-line bg-[#4b5058]"
             style="aspect-ratio: {{ Banner::IMAGE_WIDTH }} / {{ Banner::IMAGE_HEIGHT }}">

            <template x-if="imageUrl">
                <img :src="imageUrl" alt="" class="absolute inset-0 size-full object-cover">
            </template>

            <div class="absolute inset-0" :style="filterStyle" aria-hidden="true"></div>

            <div class="absolute inset-0 flex flex-col justify-center gap-[2%] px-[6%] py-[4%]"
                 :class="alignment === 'center' ? 'items-center text-center' : 'items-start text-left'">
                <p class="m-0 text-[4.2cqw] leading-[1.1]" x-show="title" :style="textStyle('title')" x-text="title"></p>
                <p class="m-0 text-[2.4cqw] leading-[1.25]" x-show="subtitle" :style="textStyle('subtitle')" x-text="subtitle"></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="mt-8"
              action="{{ $editing ? route('admin.banners.update', $banner) : route('admin.banners.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            {{-- ---------------- Imagen ---------------- --}}
            <fieldset class="border-0 p-0">
                <legend class="p-0 font-display text-15 font-bold text-heading">
                    {{ __('admin-bloques.banner.imagen.titulo') }}
                </legend>
                <p class="m-0 mt-1 mb-4 text-13-5 text-muted">
                    {{ __('admin-bloques.banner.imagen.descripcion') }}
                </p>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <div class="flex flex-wrap items-start gap-5">
                        <div>
                            <label for="media_type" class="sr-only">{{ __('admin-bloques.banner.imagen.titulo') }}</label>
                            <select id="media_type" name="media_type"
                                    class="w-[190px] rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                                <option value="image" selected>{{ __('admin-bloques.banner.imagen.foto_fondo') }}</option>
                            </select>

                            <div class="relative mt-3 w-[190px]">
                                <label for="image"
                                       class="flex h-[86px] cursor-pointer items-center justify-center overflow-hidden
                                              rounded-[3px] border border-dashed border-stroke-strong bg-tint">
                                    <template x-if="! imageUrl">
                                        <svg class="size-7 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 7.5h3l1.5-2.5h7L17 7.5h3a1 1 0 0 1 1 1V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8.5a1 1 0 0 1 1-1Z" />
                                            <circle cx="12" cy="13" r="3.4" />
                                        </svg>
                                    </template>
                                    <template x-if="imageUrl">
                                        <img :src="imageUrl" alt="" class="size-full object-cover">
                                    </template>
                                    <span class="sr-only">{{ __('admin-bloques.banner.imagen.seleccionar') }}</span>
                                </label>

                                <button type="button" @click="clearImage()" x-show="imageUrl" x-cloak
                                        aria-label="{{ __('admin-bloques.banner.imagen.quitar') }}"
                                        class="absolute -top-2 -right-2 flex size-6 items-center justify-center rounded-[3px]
                                               border-0 bg-navy text-on-brand">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.6" stroke-linecap="round" aria-hidden="true">
                                        <path d="M5 5l14 14M19 5L5 19" />
                                    </svg>
                                </button>

                                <input id="image" name="image" type="file" x-ref="image"
                                       accept="image/jpeg,image/png,image/gif,image/bmp,image/webp"
                                       @change="onFileChange($event)"
                                       aria-describedby="image-help"
                                       class="sr-only">
                            </div>
                        </div>

                        <p id="image-help" class="m-0 max-w-[220px] text-13 leading-[1.6] text-muted">
                            {{ __('admin-bloques.banner.imagen.tamano', [
                                'ancho' => Banner::IMAGE_WIDTH,
                                'alto' => Banner::IMAGE_HEIGHT,
                            ]) }}<br>
                            {{ __('admin-bloques.banner.imagen.peso') }}<br>
                            {{ __('admin-bloques.banner.imagen.formatos') }}
                        </p>
                    </div>

                    {{-- ---------------- Filtro ---------------- --}}
                    <div class="border-line lg:border-l lg:pl-8">
                        <h2 class="m-0 font-display text-15 font-bold text-heading">
                            {{ __('admin-bloques.banner.filtro.titulo') }}
                        </h2>
                        <p class="m-0 mt-1 mb-4 text-13-5 text-muted">
                            {{ __('admin-bloques.banner.filtro.descripcion') }}
                        </p>

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                            <div class="flex items-center gap-2">
                                <label for="filter_color" class="text-13-5 text-body">
                                    {{ __('admin-bloques.banner.filtro.color') }}
                                </label>
                                <input id="filter_color" name="filter_color" type="color" x-model="filterColor"
                                       class="size-9 cursor-pointer rounded-[3px] border border-stroke bg-card p-1">
                                <output class="font-mono text-12-5 text-muted" x-text="filterColor"></output>
                            </div>

                            <div class="flex items-center gap-2">
                                <label for="filter_opacity" class="text-13-5 text-body">
                                    {{ __('admin-bloques.banner.filtro.opacidad') }}
                                </label>
                                <input id="filter_opacity" name="filter_opacity" type="range" min="0" max="100" step="5"
                                       x-model.number="filterOpacity" class="w-[150px] accent-azure">
                                <output class="w-10 text-right text-12-5 text-muted" x-text="filterOpacity + '%'"></output>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <hr class="my-8 border-0 border-t border-line">

            {{-- ---------------- Título y subtítulo ---------------- --}}
            @foreach ([
                ['prefix' => 'title', 'clave' => 'titulo', 'limit' => 90, 'model' => 'title'],
                ['prefix' => 'subtitle', 'clave' => 'subtitulo', 'limit' => 140, 'model' => 'subtitle'],
            ] as $field)
                @php
                    $p = $field['prefix'];

                    // El nombre del campo entra en varios rótulos, en mayúscula
                    // para los visibles y en minúscula dentro de las frases.
                    $nombre = __('admin-bloques.banner.'.$field['clave'].'.nombre');
                    $minuscula = __('admin-bloques.banner.'.$field['clave'].'.minuscula');
                @endphp
                <fieldset class="mb-8 border-0 p-0">
                    <legend class="p-0 font-display text-15 font-bold text-heading">{{ $nombre }}</legend>
                    <p class="m-0 mt-1 mb-3 text-13-5 text-muted">
                        {{ __('admin-bloques.banner.texto.descripcion', ['campo' => $minuscula]) }}
                    </p>

                    <div class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-t-[3px] border border-line bg-tint px-3 py-2">
                        <div class="flex items-center gap-2">
                            <label for="{{ $p }}_color" class="text-12-5 text-body">
                                {{ __('admin-bloques.banner.texto.color_letra') }}
                            </label>
                            <input id="{{ $p }}_color" name="{{ $p }}_color" type="color" x-model="{{ $p }}Color"
                                   class="size-8 cursor-pointer rounded-[3px] border border-stroke bg-card p-1">
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="{{ $p }}_background" class="text-12-5 text-body">
                                {{ __('admin-bloques.banner.texto.color_fondo') }}
                            </label>
                            <input id="{{ $p }}_background" name="{{ $p }}_background" type="color"
                                   x-model="{{ $p }}Background"
                                   class="size-8 cursor-pointer rounded-[3px] border border-stroke bg-card p-1">
                            <button type="button" @click="{{ $p }}Background = ''"
                                    aria-label="{{ __('admin-bloques.banner.texto.quitar_fondo', ['campo' => $minuscula]) }}"
                                    class="flex size-7 items-center justify-center rounded-[3px] border-0 bg-transparent text-muted hover:text-heading">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <label for="{{ $p }}_font" class="text-12-5 text-body">
                                {{ __('admin-bloques.banner.texto.tipografia') }}
                            </label>
                            <select id="{{ $p }}_font" name="{{ $p }}_font" x-model="{{ $p }}Font"
                                    class="rounded-[3px] border border-stroke bg-card px-2 py-1 text-13">
                                @foreach (Banner::FONTS as $font)
                                    <option value="{{ $font }}">{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-1">
                            <label class="flex size-8 cursor-pointer items-center justify-center rounded-[3px]
                                          border border-stroke font-display text-13 font-bold"
                                   :class="{{ $p }}Bold ? 'bg-azure text-on-accent border-azure' : 'bg-card text-heading'">
                                <input type="checkbox" name="{{ $p }}_bold" value="1" x-model="{{ $p }}Bold" class="sr-only">
                                B<span class="sr-only">{{ __('admin-bloques.banner.texto.negrita', ['campo' => $minuscula]) }}</span>
                            </label>
                            <label class="flex size-8 cursor-pointer items-center justify-center rounded-[3px]
                                          border border-stroke font-display text-13 italic"
                                   :class="{{ $p }}Italic ? 'bg-azure text-on-accent border-azure' : 'bg-card text-heading'">
                                <input type="checkbox" name="{{ $p }}_italic" value="1" x-model="{{ $p }}Italic" class="sr-only">
                                I<span class="sr-only">{{ __('admin-bloques.banner.texto.cursiva', ['campo' => $minuscula]) }}</span>
                            </label>
                        </div>

                        {{-- El rótulo se arma en el navegador: la cuenta cambia al teclear. --}}
                        <output class="ml-auto text-12-5 text-muted"
                                x-text="@js(__('admin-bloques.banner.texto.caracteres')).replace(':restantes', remaining('{{ $field['model'] }}', {{ $field['limit'] }}))">
                        </output>
                    </div>

                    <label for="{{ $p }}" class="sr-only">
                        {{ __('admin-bloques.banner.texto.etiqueta', ['campo' => $nombre]) }}
                    </label>
                    <textarea id="{{ $p }}" name="{{ $p }}" rows="2" maxlength="{{ $field['limit'] }}"
                              x-model="{{ $p }}"
                              class="w-full rounded-b-[3px] border border-t-0 border-line bg-card px-3 py-2 text-14 text-ink"></textarea>
                </fieldset>
            @endforeach

            {{-- ---------------- Justificación ---------------- --}}
            <fieldset class="mb-8 border-0 p-0">
                <legend class="p-0 font-display text-15 font-bold text-heading">
                    {{ __('admin-bloques.banner.justificacion.titulo') }}
                </legend>
                <p class="m-0 mt-1 mb-3 text-13-5 text-muted">
                    {{ __('admin-bloques.banner.justificacion.descripcion') }}
                </p>

                <div class="flex gap-2">
                    @foreach (array_keys(Banner::ALIGNMENTS) as $value)
                        <label class="flex size-9 cursor-pointer items-center justify-center rounded-[3px] border border-stroke"
                               :class="alignment === '{{ $value }}' ? 'bg-azure text-on-accent border-azure' : 'bg-card text-heading'">
                            <input type="radio" name="alignment" value="{{ $value }}" x-model="alignment" class="sr-only">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.9" stroke-linecap="round" aria-hidden="true">
                                <path d="M4 6h16" />
                                <path d="{{ $value === 'center' ? 'M7 11h10' : 'M4 11h10' }}" />
                                <path d="M4 16h16" />
                                <path d="{{ $value === 'center' ? 'M8 21h8' : 'M4 21h8' }}" />
                            </svg>
                            <span class="sr-only">{{ __('admin-bloques.banner.justificacion.'.$value) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <hr class="my-8 border-0 border-t border-line">

            {{-- ---------------- Accesibilidad y enlace ---------------- --}}
            <div class="mb-6">
                <label for="alt_text" class="font-display text-15 font-bold text-heading">
                    {{ __('admin-bloques.banner.alternativo.titulo') }} <span aria-hidden="true">*</span>
                </label>
                <p class="m-0 mt-1 mb-2 text-13-5 text-muted">
                    {{ __('admin-bloques.banner.alternativo.descripcion') }}
                    <output class="text-muted"
                            x-text="@js(__('admin-bloques.banner.alternativo.restantes')).replace(':restantes', remaining('altText', 250))"></output>
                </p>
                <textarea id="alt_text" name="alt_text" rows="2" maxlength="250" required x-model="altText"
                          aria-describedby="alt-help"
                          class="w-full rounded-[3px] border border-stroke bg-card px-3 py-2 text-14 text-ink">{{ old('alt_text', $banner->alt_text) }}</textarea>
                <p id="alt-help" class="m-0 mt-1 text-12-5 text-faint">
                    {{ __('admin-bloques.banner.alternativo.ayuda') }}
                </p>
            </div>

            <div class="mb-8">
                <label for="link" class="font-display text-15 font-bold text-heading">
                    {{ __('admin-bloques.banner.enlace.titulo') }}
                </label>
                <p class="m-0 mt-1 mb-2 text-13-5 text-muted">
                    {{ __('admin-bloques.banner.enlace.descripcion') }}
                </p>
                <input id="link" name="link" type="url" inputmode="url"
                       value="{{ old('link', $banner->link) }}"
                       placeholder="https://"
                       class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.banners.index') }}"
                   class="rounded-full border border-stroke bg-card px-6 py-[10px] font-display text-14
                          font-semibold text-heading no-underline hover:bg-tint hover:no-underline">
                    {{ __('admin-bloques.acciones.cancelar') }}
                </a>
                <button type="submit"
                        class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14 font-semibold
                               text-on-accent transition-colors hover:bg-azure-dark">
                    {{ __('admin-bloques.acciones.guardar') }}
                </button>

                @if ($editing)
                    <span class="ml-auto"></span>
                @endif
            </div>
        </form>

        {{-- Fuera del formulario principal: un formulario no puede anidarse. --}}
        @if ($editing)
            <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="mt-4"
                  onsubmit="return confirm(@js(__('admin-bloques.banner.eliminar.confirmacion')))">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="border-0 bg-transparent p-0 text-13-5 font-semibold text-danger underline underline-offset-4">
                    {{ __('admin-bloques.banner.eliminar.boton') }}
                </button>
            </form>
        @endif
    </div>
@endsection
