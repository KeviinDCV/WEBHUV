@php
    $editing = $document->exists;
    $uid = $uid ?? '';
@endphp

{{--
    Editor de un documento de un tema.

    Vive dentro del propio listado del tema: dar de alta un documento no debe
    sacar de la página en la que se está trabajando.
--}}
<form method="POST" enctype="multipart/form-data"
      x-data="{
          title: @js(old('title', $document->title ?? '')),
          newCategory: @js(old('new_category', '')),
          adding: {{ old('new_category') ? 'true' : 'false' }},
          scheduling: {{ old('published_at') ? 'true' : 'false' }},
          publishedAt: @js(old('published_at', '')),
          get isFuture() {
              return this.scheduling && this.publishedAt && new Date(this.publishedAt) > new Date();
          },
      }"
      action="{{ $editing
          ? route('admin.documents.update', [$topic, $document])
          : route('admin.documents.store', $topic) }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div role="alert"
             class="mb-6 rounded-[3px] border border-line border-l-4 border-l-[#b3261e] bg-[#fdf3f2] px-4 py-3">
            <p class="m-0 text-13-5 font-semibold text-[#8c1d18]">Revise los siguientes puntos</p>
            <ul class="m-0 mt-1 flex list-disc flex-col gap-1 pl-5 text-13-5 text-[#8c1d18]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ---------------- Tipo de contenido ---------------- --}}
    <p class="mx-auto mb-6 w-fit rounded-[3px] border border-stroke bg-tint px-8 py-[7px] text-14 text-muted">
        Documento
    </p>

    {{-- ---------------- Enlace ---------------- --}}
    <div class="mb-5">
        <label for="link{{ $uid }}" class="text-13-5 text-link">
            Link <span class="text-muted">(Agregar https:// o http://)</span>
        </label>
        {{-- Si el archivo ya está en el servidor, su dirección de origen no
             pinta nada aquí: llenaría el campo con el enlace del portal
             anterior y bastaría con guardar para volver a apuntar fuera. --}}
        <input id="link{{ $uid }}" name="link" type="url" inputmode="url" placeholder="Link"
               value="{{ old('link', $document->isDownloaded() ? null : $document->source_url) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        <p class="m-0 mt-1 text-12-5 text-muted">
            Para documentos que se consultan en otro sitio. Si adjunta un archivo, no hace falta.
        </p>
    </div>

    {{-- ---------------- Título ---------------- --}}
    <div class="mb-5">
        <label for="title{{ $uid }}" class="text-13-5 text-link">
            Título <span class="text-muted">(150 caracteres)</span>
        </label>
        {{-- El `value` va también en el HTML, no solo en el x-model: sin
             JavaScript el campo llegaría vacío y se perdería el título. --}}
        <input id="title{{ $uid }}" name="title" type="text" maxlength="150" required x-model="title"
               placeholder="Título" value="{{ old('title', $document->title) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        <output class="mt-1 block text-12-5 text-muted" x-text="`Quedan ${150 - title.length} caracteres.`"></output>
    </div>

    {{-- ---------------- Fecha de expedición ---------------- --}}
    <div class="mb-5">
        <label for="issued_at{{ $uid }}" class="text-13-5 text-link">Fecha expedición</label>
        <input id="issued_at{{ $uid }}" name="issued_at" type="date"
               value="{{ old('issued_at', $document->issued_at?->format('Y-m-d')) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        <p class="m-0 mt-1 text-12-5 text-muted">
            La del documento en sí, no la del día en que se sube.
        </p>
    </div>

    {{-- ---------------- Descripción ---------------- --}}
    <div class="mb-6">
        <span class="text-13-5 text-link">Descripción</span>
        <input type="hidden" name="description" id="description-input{{ $uid }}"
               value="{{ old('description', $document->description) }}">

        {{-- Quill escribe en el input oculto; ver resources/js/admin.js. --}}
        <div data-huv-editor="#description-input{{ $uid }}"
             data-huv-editor-label="Descripción del documento"
             class="mt-1 min-h-[220px] rounded-b-[3px] bg-card"></div>

        <noscript>
            <p class="m-0 mt-2 rounded-[3px] border border-line bg-tint px-3 py-2 text-12-5 text-muted">
                El editor con formato necesita JavaScript.
            </p>
        </noscript>
    </div>

    {{-- ---------------- Archivo ---------------- --}}
    <div class="mb-6">
        @if ($editing && $document->isDownloaded())
            <p class="m-0 mb-2 flex flex-wrap items-center gap-2 text-13-5 text-body">
                <svg class="size-4 shrink-0 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3v5h5" />
                    <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                </svg>
                Archivo actual:
                <a href="{{ $document->fileUrl() }}" class="font-semibold text-link">{{ $document->file_name }}</a>
                <span class="text-muted">({{ $document->extension() }} · {{ $document->humanSize() }})</span>
            </p>
        @endif

        <label for="file{{ $uid }}"
               class="flex w-fit cursor-pointer flex-col gap-1 rounded-[3px] border border-dashed
                      border-stroke-strong bg-card px-5 py-4 hover:bg-tint">
            <span class="flex items-center gap-2 text-13-5 text-body">
                <svg class="size-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.5 12.9 20.6a5 5 0 0 1-7.1-7.1l8.5-8.5a3.3 3.3 0 1 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4l7.8-7.8" />
                </svg>
                {{ $editing && $document->isDownloaded() ? 'Reemplazar archivo' : 'Agrega archivo' }}
            </span>
            <span class="text-12-5 text-muted">Peso máximo: 30 MB · pdf, doc, xls, ppt, csv, txt o zip</span>
        </label>
        <input id="file{{ $uid }}" name="file" type="file" class="sr-only"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip">
    </div>

    {{-- ---------------- Descripción del archivo ---------------- --}}
    <div class="mb-6">
        {{-- El portal actual rotula este campo «Descripción de la imagen»
             aunque aquí solo se adjuntan documentos: se nombra por lo que es,
             que es lo que se lee al descargar. --}}
        <label for="file_alt{{ $uid }}" class="sr-only">Descripción del archivo</label>
        <input id="file_alt{{ $uid }}" name="file_alt" type="text" maxlength="250"
               placeholder="Agregue una descripción al archivo"
               value="{{ old('file_alt', $document->file_name) }}"
               class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
    </div>

    {{-- ---------------- Categorías ---------------- --}}
    <div class="mb-6 flex flex-wrap items-start gap-3">
        <div class="min-w-[240px]">
            <label for="topic_category_id{{ $uid }}" class="sr-only">Categoría</label>
            <select id="topic_category_id{{ $uid }}" name="topic_category_id" x-bind:disabled="adding"
                    class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink
                           disabled:bg-tint disabled:text-faint">
                <option value="">Sin categoría</option>
                @foreach ($topic->categories as $category)
                    <option value="{{ $category->id }}"
                            @selected((int) old('topic_category_id', $document->topic_category_id) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="button" @click="adding = ! adding; if (! adding) newCategory = ''"
                :aria-expanded="adding ? 'true' : 'false'"
                class="rounded-full border border-rule-accent bg-card px-4 py-[8px] text-13-5
                       font-semibold text-link transition-colors hover:bg-tint">
            Agregar categoría
            <span aria-hidden="true" x-text="adding ? '−' : '+'">+</span>
        </button>

        <div x-show="adding" x-cloak class="min-w-[240px] flex-1">
            <label for="new_category{{ $uid }}" class="sr-only">Nombre de la categoría nueva</label>
            <input id="new_category{{ $uid }}" name="new_category" type="text" maxlength="120" x-model="newCategory"
                   placeholder="Por ejemplo: Ejecución Presupuestal {{ now()->year }}"
                   class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        </div>
    </div>

    {{-- ---------------- Publicación ---------------- --}}
    <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
        <div class="flex flex-col gap-2">
            <label for="is_featured{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="is_featured{{ $uid }}" name="is_featured" type="checkbox" value="1"
                       @checked(old('is_featured', $document->is_featured))
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                Destacar contenido
            </label>

            <label for="show_in_feed{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="show_in_feed{{ $uid }}" name="show_in_feed" type="checkbox" value="1"
                       @checked(old('show_in_feed', ! $document->is_hidden))
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                Mostrar en muro de contenidos
            </label>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="scheduling = ! scheduling; if (! scheduling) publishedAt = ''"
                    :aria-expanded="scheduling ? 'true' : 'false'"
                    aria-controls="published_at{{ $uid }}"
                    class="rounded-full border border-rule-accent bg-card px-6 py-[9px] font-display text-12-5
                           font-bold tracking-[0.06em] text-link uppercase transition-colors hover:bg-tint">
                Programar
            </button>

            <button type="submit"
                    class="rounded-full border-0 bg-azure px-7 py-[9px] font-display text-12-5 font-bold
                           tracking-[0.06em] text-on-accent uppercase transition-colors hover:bg-azure-dark"
                    x-text="isFuture ? 'Guardar programado' : 'Publicar'">
                Publicar
            </button>

            @if ($editing)
                <details class="relative">
                    <summary class="cursor-pointer rounded-full bg-navy px-6 py-[9px] font-display text-12-5
                                    font-bold tracking-[0.06em] text-on-brand uppercase">
                        Compartir en redes
                    </summary>
                    <div class="absolute right-0 z-20 mt-2 rounded-[4px] border border-line bg-card p-4
                                shadow-[0_10px_28px_rgba(23,32,64,0.22)]">
                        <x-share :title="$document->title" :url="route('documents.show', [$topic, $document])" />
                    </div>
                </details>
            @endif
        </div>
    </div>

    {{-- Fecha de publicación: solo aparece al pulsar «Programar». --}}
    <div x-show="scheduling" x-cloak class="mt-4 max-w-[320px] ml-auto">
        <label for="published_at{{ $uid }}" class="text-13-5 text-link">Publicar el</label>
        <input id="published_at{{ $uid }}" name="published_at" type="datetime-local" x-model="publishedAt"
               value="{{ old('published_at') }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        <p x-show="isFuture" x-cloak class="m-0 mt-1 text-12-5 text-link">
            La fecha está por delante: no se verá hasta que llegue.
        </p>
    </div>

    <p class="m-0 mt-4 text-right text-12-5 text-faint">
        Contenido en «{{ $topic->name }}»
    </p>
</form>

@if ($editing)
    {{-- Fuera del formulario: un formulario no puede anidarse. --}}
    <form method="POST" action="{{ route('admin.documents.destroy', [$topic, $document]) }}" class="mt-4"
          onsubmit="return confirm('¿Eliminar este documento? La acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="border-0 bg-transparent p-0 text-13-5 font-semibold text-[#8c1d18] underline underline-offset-4">
            Eliminar documento
        </button>
    </form>
@endif
