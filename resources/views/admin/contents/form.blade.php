@php
    use App\Models\Content;

    $editing = $content->exists;
    $backUrl = route('home');
    $noDate = (bool) old('no_date', $editing && $content->published_at === null);
@endphp

@extends('layouts.admin')

@section('title', $editing ? 'Actualizar contenido' : 'Nuevo contenido')
@section('heading', $editing ? 'Actualizar contenido' : 'Nuevo contenido')

@push('head')
    @vite('resources/js/admin.js')
@endpush

@section('content')
    <form method="POST" enctype="multipart/form-data"
          x-data="{
              noDate: {{ $noDate ? 'true' : 'false' }},
              title: @js(old('title', $content->title ?? '')),
              publishedAt: @js(old('published_at', ($content->published_at ?? now())->format('Y-m-d\TH:i'))),
              /* «Programar» solo tiene sentido con una fecha por delante. */
              get isFuture() {
                  return ! this.noDate && this.publishedAt && new Date(this.publishedAt) > new Date();
              },
          }"
          action="{{ $editing ? route('admin.contents.update', $content) : route('admin.contents.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- ---------------- Categoría ---------------- --}}
        <div class="mb-6 max-w-[320px]">
            <label for="category" class="text-13-5 font-semibold text-heading">Categoría</label>
            <select id="category" name="category"
                    class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                @foreach (Content::CATEGORIES as $category)
                    <option value="{{ $category }}" @selected(old('category', $content->category) === $category)>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ---------------- Título ---------------- --}}
        <div class="mb-5">
            <label for="title" class="text-13-5 font-semibold text-heading">
                Título
                <span class="font-normal text-muted">(150 caracteres)</span>
            </label>
            <input id="title" name="title" type="text" maxlength="150" required x-model="title"
                   value="{{ old('title', $content->title) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
            <output class="mt-1 block text-12-5 text-muted" x-text="`Quedan ${150 - title.length} caracteres.`"></output>
        </div>

        {{-- ---------------- Fecha ---------------- --}}
        <div class="mb-6">
            <label for="no_date" class="flex items-center gap-2 text-13-5 text-body">
                <input id="no_date" name="no_date" type="checkbox" value="1" x-model="noDate"
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                Sin fecha de visualización
            </label>

            <div x-show="! noDate" x-cloak class="mt-3 max-w-[280px]">
                <label for="published_at" class="text-13-5 font-semibold text-heading">Fecha de publicación</label>
                <input id="published_at" name="published_at" type="datetime-local" x-model="publishedAt"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                <p x-show="isFuture" x-cloak class="m-0 mt-2 text-12-5 text-link">
                    La fecha está por delante: el contenido quedará programado y no se verá hasta entonces.
                </p>
            </div>
        </div>

        {{-- ---------------- Resumen ---------------- --}}
        <div class="mb-6">
            <label for="excerpt" class="text-13-5 font-semibold text-heading">
                Resumen <span class="font-normal text-muted">(opcional, 400 caracteres)</span>
            </label>
            <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
                Es lo que se lee en la portada. Si se deja vacío se toman las primeras líneas de la descripción.
            </p>
            <textarea id="excerpt" name="excerpt" rows="3" maxlength="400"
                      class="w-full rounded-[3px] border border-stroke bg-card px-3 py-2 text-14 text-ink">{{ old('excerpt', $content->excerpt) }}</textarea>
        </div>

        {{-- ---------------- Descripción ---------------- --}}
        <div class="mb-8">
            <span id="body-label" class="text-13-5 font-semibold text-heading">Descripción</span>
            <p class="m-0 mt-1 mb-2 text-12-5 text-muted">Cuerpo completo del contenido.</p>

            <input type="hidden" name="body" id="body-input" value="{{ old('body', $content->body) }}">

            {{-- Quill escribe en el input oculto de arriba; ver resources/js/admin.js. --}}
            <div data-huv-editor="#body-input"
                 data-huv-editor-label="Descripción del contenido"
                 class="min-h-[280px] rounded-b-[3px] bg-card"></div>

            <noscript>
                <p class="m-0 mt-2 rounded-[3px] border border-line bg-tint px-3 py-2 text-12-5 text-muted">
                    El editor con formato necesita JavaScript. Sin él, la descripción no se puede modificar
                    desde esta pantalla.
                </p>
            </noscript>
        </div>

        @include('admin.contents.partials.media', ['content' => $content])
        @include('admin.contents.partials.library', ['content' => $content])

        {{-- ---------------- Participación ---------------- --}}
        <div class="mb-8 max-w-[420px]">
            <label for="participation" class="text-13-5 font-semibold text-heading">
                Participación ciudadana
            </label>
            <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
                Etapa del proceso participativo con la que se relaciona (Ley 1757 de 2015).
                Al elegir una, la página del contenido la muestra y enlaza con la sección «Participa».
            </p>
            <select id="participation" name="participation"
                    class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                <option value="">Contenido sin participación</option>
                @foreach (Content::PARTICIPATION_STAGES as $stage)
                    <option value="{{ $stage }}" @selected(old('participation', $content->participation) === $stage)>
                        {{ $stage }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ---------------- Enlace ---------------- --}}
        <div class="mb-8">
            <label for="link" class="text-13-5 font-semibold text-heading">Enlace</label>
            <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
                Si el contenido completo vive fuera del portal. Debe incluir http:// o https://
            </p>
            <input id="link" name="link" type="url" inputmode="url" placeholder="https://"
                   value="{{ old('link', $content->link) }}"
                   class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>

        {{-- ---------------- Publicación ---------------- --}}
        <fieldset class="mb-8 border-0 p-0">
            <legend class="p-0 font-display text-15 font-bold text-heading">Publicación</legend>

            <div class="mt-3 flex flex-col gap-3">
                <label for="is_featured" class="flex items-start gap-2 text-13-5 text-body">
                    <input id="is_featured" name="is_featured" type="checkbox" value="1"
                           @checked(old('is_featured', $content->is_featured))
                           class="mt-[3px] size-4 rounded-[2px] border-stroke accent-azure">
                    <span>
                        Destacar contenido
                        <span class="block text-12-5 text-muted">
                            Ocupa el espacio grande del bloque. Solo puede haber uno por categoría:
                            al marcarlo, se desmarca el anterior.
                        </span>
                    </span>
                </label>

                <label for="show_in_feed" class="flex items-start gap-2 text-13-5 text-body">
                    <input id="show_in_feed" name="show_in_feed" type="checkbox" value="1"
                           @checked(old('show_in_feed', $content->show_in_feed ?? true))
                           class="mt-[3px] size-4 rounded-[2px] border-stroke accent-azure">
                    <span>
                        Mostrar en muro de contenidos
                        <span class="block text-12-5 text-muted">
                            Además del bloque de su categoría, aparece en el listado general de la portada.
                        </span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('home') }}"
               class="rounded-full border border-stroke bg-card px-6 py-[10px] font-display text-14
                      font-semibold text-heading no-underline hover:bg-tint hover:no-underline">
                Cancelar
            </a>

            {{-- Programar es el mismo envío: lo que decide es la fecha. Se
                 deshabilita si no hay una por delante, para no prometer algo
                 que el servidor rechazaría. --}}
            <button type="submit" :disabled="! isFuture"
                    :title="isFuture ? '' : 'Elija una fecha futura para poder programar'"
                    class="rounded-full border border-rule-accent bg-card px-7 py-[10px] font-display text-14
                           font-semibold text-link transition-colors hover:bg-tint
                           disabled:cursor-not-allowed disabled:border-stroke disabled:text-faint
                           disabled:hover:bg-card">
                Programar
            </button>

            <button type="submit" @click="if (isFuture) { noDate = false; publishedAt = @js(now()->format('Y-m-d\TH:i')) }"
                    class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14 font-semibold
                           text-on-accent transition-colors hover:bg-azure-dark">
                Publicar
            </button>

            @if ($editing)
                <details class="relative">
                    <summary class="cursor-pointer rounded-full bg-navy px-7 py-[10px] font-display text-14
                                    font-semibold text-on-brand">
                        Compartir en redes
                    </summary>
                    <div class="absolute right-0 z-20 mt-2 rounded-[4px] border border-line bg-card p-4
                                shadow-[0_10px_28px_rgba(23,32,64,0.22)]">
                        <x-share :title="$content->title" :url="route('contents.show', $content->slug)" />
                    </div>
                </details>
            @endif
        </div>

        <p class="m-0 mt-4 text-12-5 text-faint">
            Contenido en «{{ old('category', $content->category) }}»
        </p>
    </form>

    @stack('after-form')

    {{-- Fuera del formulario principal: un formulario no puede anidarse. --}}
    @if ($editing)
        <form method="POST" action="{{ route('admin.contents.destroy', $content) }}" class="mt-5"
              onsubmit="return confirm('¿Eliminar este contenido? La acción no se puede deshacer.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="border-0 bg-transparent p-0 text-13-5 font-semibold text-[#8c1d18] underline underline-offset-4">
                Eliminar contenido
            </button>
        </form>
    @endif
@endsection
