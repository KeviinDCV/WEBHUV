@php
    use App\Models\Content;

    $editing = $content->exists;
    $noDate = (bool) old('no_date', $editing && $content->published_at === null);
    $noEndDate = (bool) old('no_end_date', $content->expires_at === null);
@endphp

{{--
    Editor de un contenido.

    Se usa tal cual en dos sitios: incrustado en la portada, bajo «Nuevo
    contenido», y en su propia pantalla de administración. Por eso no extiende
    ningún layout ni asume dónde vive.
--}}
<form method="POST" enctype="multipart/form-data"
      x-data="{
          noDate: {{ $noDate ? 'true' : 'false' }},
          noEndDate: {{ $noEndDate ? 'true' : 'false' }},
          title: @js(old('title', $content->title ?? '')),
          publishedAt: @js(old('published_at', ($content->published_at ?? now())->format('Y-m-d\TH:i'))),
          get isFuture() {
              return ! this.noDate && this.publishedAt && new Date(this.publishedAt) > new Date();
          },
      }"
      action="{{ $editing ? route('admin.contents.update', $content) : route('admin.contents.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div role="alert"
             class="mb-6 rounded-[3px] border border-line border-l-4 border-l-danger bg-danger-surface px-4 py-3">
            <p class="m-0 text-13-5 font-semibold text-danger">{{ __('admin-contenidos.errores.titulo') }}</p>
            <ul class="m-0 mt-1 flex list-disc flex-col gap-1 pl-5 text-13-5 text-danger">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ---------------- Categoría ---------------- --}}
    <div class="mx-auto mb-6 max-w-[280px]">
        <label for="category{{ $uid }}" class="sr-only">{{ __('admin-contenidos.categoria.etiqueta') }}</label>
        <select id="category{{ $uid }}" name="category"
                class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-center text-14 text-ink">
            @foreach (Content::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $content->category) === $category)>
                    {{ Content::categoryLabel($category) }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- ---------------- Título ---------------- --}}
    <div class="mb-5">
        <label for="title{{ $uid }}" class="text-13-5 text-link">
            {{ __('admin-contenidos.titulo.etiqueta') }}
            <span class="text-muted">{{ __('admin-contenidos.titulo.limite') }}</span>
        </label>
        {{-- El `value` va también en el HTML, no solo en el x-model: sin
             JavaScript el campo llegaría vacío y se perdería el contenido. --}}
        <input id="title{{ $uid }}" name="title" type="text" maxlength="150" required x-model="title"
               placeholder="{{ __('admin-contenidos.titulo.etiqueta') }}" value="{{ old('title', $content->title) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        {{-- El contador se arma en el navegador: el texto llega con :n y
             Alpine lo sustituye por los caracteres que quedan. --}}
        <output class="mt-1 block text-12-5 text-muted"
                x-text="@js(__('admin-contenidos.titulo.restantes')).replace(':n', 150 - title.length)"></output>
    </div>

    {{-- ---------------- Fechas ---------------- --}}
    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <label for="no_date{{ $uid }}" class="flex items-center gap-2 text-13-5 text-body">
                <input id="no_date{{ $uid }}" name="no_date" type="checkbox" value="1" x-model="noDate"
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                {{ __('admin-contenidos.fechas.sin_inicio') }}
            </label>

            <div x-show="! noDate" x-cloak class="mt-2">
                <label for="published_at{{ $uid }}" class="text-13-5 text-link">{{ __('admin-contenidos.fechas.publicacion') }}</label>
                <input id="published_at{{ $uid }}" name="published_at" type="datetime-local" x-model="publishedAt"
                       value="{{ old('published_at', ($content->published_at ?? now())->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                <p x-show="isFuture" x-cloak class="m-0 mt-1 text-12-5 text-link">
                    {{ __('admin-contenidos.fechas.programado') }}
                </p>
            </div>
        </div>

        <div>
            <label for="no_end_date{{ $uid }}" class="flex items-center gap-2 text-13-5 text-body">
                <input id="no_end_date{{ $uid }}" name="no_end_date" type="checkbox" value="1" x-model="noEndDate"
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                {{ __('admin-contenidos.fechas.sin_fin') }}
            </label>

            <div x-show="! noEndDate" x-cloak class="mt-2">
                <label for="expires_at{{ $uid }}" class="text-13-5 text-link">{{ __('admin-contenidos.fechas.fin') }}</label>
                <input id="expires_at{{ $uid }}" name="expires_at" type="datetime-local"
                       value="{{ old('expires_at', $content->expires_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
                <p class="m-0 mt-1 text-12-5 text-muted">
                    {{ __('admin-contenidos.fechas.fin_ayuda') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ---------------- Resumen ---------------- --}}
    <div class="mb-6">
        <label for="excerpt{{ $uid }}" class="text-13-5 text-link">
            {{ __('admin-contenidos.resumen.etiqueta') }}
            <span class="text-muted">{{ __('admin-contenidos.resumen.limite') }}</span>
        </label>
        <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
            {{ __('admin-contenidos.resumen.ayuda') }}
        </p>
        <textarea id="excerpt{{ $uid }}" name="excerpt" rows="3" maxlength="400"
                  class="w-full rounded-[3px] border border-stroke bg-card px-3 py-2 text-14 text-ink">{{ old('excerpt', $content->excerpt) }}</textarea>
    </div>

    {{-- ---------------- Descripción ---------------- --}}
    <div class="mb-8">
        <span class="text-13-5 text-link">{{ __('admin-contenidos.descripcion.etiqueta') }}</span>
        <input type="hidden" name="body" id="body-input{{ $uid }}" value="{{ old('body', $content->body) }}">

        {{-- Quill escribe en el input oculto; ver resources/js/admin.js. --}}
        <div data-huv-editor="#body-input{{ $uid }}"
             data-huv-editor-label="{{ __('admin-contenidos.descripcion.editor') }}"
             class="mt-1 min-h-[260px] rounded-b-[3px] bg-card"></div>

        <noscript>
            <p class="m-0 mt-2 rounded-[3px] border border-line bg-tint px-3 py-2 text-12-5 text-muted">
                {{ __('admin-contenidos.descripcion.sin_javascript') }}
            </p>
        </noscript>
    </div>

    @include('admin.contents.partials.media', ['content' => $content])
    @include('admin.contents.partials.library', ['content' => $content])

    {{-- ---------------- Participación ---------------- --}}
    <div class="mb-6 max-w-[420px]">
        @include('admin.partials.participacion', [
            'uid' => $uid,
            'value' => $content->comment_wall,
        ])
    </div>

    {{-- ---------------- Enlace ---------------- --}}
    <div class="mb-6">
        <label for="link{{ $uid }}" class="text-13-5 text-link">{{ __('admin-contenidos.enlace.etiqueta') }}</label>
        <p class="m-0 mt-1 mb-2 text-12-5 text-muted">
            {{ __('admin-contenidos.enlace.ayuda') }}
        </p>
        <input id="link{{ $uid }}" name="link" type="url" inputmode="url" placeholder="https://"
               value="{{ old('link', $content->link) }}"
               class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
    </div>

    {{-- ---------------- Publicación ---------------- --}}
    <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
        <div class="flex flex-col gap-2">
            <label for="is_featured{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="is_featured{{ $uid }}" name="is_featured" type="checkbox" value="1"
                       @checked(old('is_featured', $content->is_featured))
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                {{ __('admin-contenidos.publicacion.destacar') }}
            </label>

            <label for="show_in_feed{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="show_in_feed{{ $uid }}" name="show_in_feed" type="checkbox" value="1"
                       @checked(old('show_in_feed', $content->show_in_feed ?? true))
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                {{ __('admin-contenidos.publicacion.muro') }}
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="! isFuture"
                    :title="isFuture ? '' : @js(__('admin-contenidos.publicacion.programar_bloqueado'))"
                    class="rounded-full border border-rule-accent bg-card px-6 py-[9px] font-display text-12-5
                           font-bold tracking-[0.06em] text-link uppercase transition-colors hover:bg-tint
                           disabled:cursor-not-allowed disabled:border-stroke disabled:text-faint
                           disabled:hover:bg-card">
                {{ __('admin-contenidos.publicacion.programar') }}
            </button>

            <button type="submit"
                    @click="if (isFuture) { noDate = false; publishedAt = @js(now()->format('Y-m-d\TH:i')) }"
                    class="rounded-full border-0 bg-azure px-7 py-[9px] font-display text-12-5 font-bold
                           tracking-[0.06em] text-on-accent uppercase transition-colors hover:bg-azure-dark">
                {{ __('admin-contenidos.publicacion.publicar') }}
            </button>

            @if ($editing)
                <details class="relative">
                    <summary class="cursor-pointer rounded-full bg-navy px-6 py-[9px] font-display text-12-5
                                    font-bold tracking-[0.06em] text-on-brand uppercase">
                        {{ __('admin-contenidos.publicacion.compartir') }}
                    </summary>
                    <div class="absolute right-0 z-20 mt-2 rounded-[4px] border border-line bg-card p-4
                                shadow-[0_10px_28px_rgba(23,32,64,0.22)]">
                        <x-share :title="$content->title" :url="route('contents.show', $content->slug)" />
                    </div>
                </details>
            @endif
        </div>
    </div>

    <p class="m-0 mt-4 text-right text-12-5 text-faint">
        {{ __('admin-contenidos.publicacion.contexto', ['categoria' => Content::categoryLabel(old('category', $content->category))]) }}
    </p>
</form>

@if ($editing)
    {{-- Fuera del formulario: un formulario no puede anidarse. --}}
    <form method="POST" action="{{ route('admin.contents.destroy', $content) }}" class="mt-4"
          onsubmit="return confirm(@js(__('admin-contenidos.eliminar.confirmar')))">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="border-0 bg-transparent p-0 text-13-5 font-semibold text-danger underline underline-offset-4">
            {{ __('admin-contenidos.eliminar.boton') }}
        </button>
    </form>
@endif

@stack('after-form')
