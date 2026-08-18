@php
    use App\Models\TopicItem;

    $editing = $item->exists;
    $uid = $uid ?? '';

    $kinds = $topic->supportedKinds();
    $kind = old('kind', $item->kind ?? $topic->defaultKind());

    // Al editar, el tipo ya no se cambia: mover un documento a artículo dejaría
    // su archivo huérfano y su fecha de expedición sin sitio.
    $fixedKind = $editing || count($kinds) === 1;

    $chosenCategories = collect(old('topic_category_ids', $item->categories->pluck('id')->all()))
        ->map(fn ($id) => (int) $id);

    // Si el formulario vuelve por un error de validación se respeta lo que se
    // marcó; si no, manda la fecha guardada. Derivarlo siempre del valor por
    // defecto haría que la casilla se remarcara sola y que la fecha escrita se
    // perdiera al reenviar sin tocarla.
    $noEndDate = old('no_end_date') !== null
        ? (bool) old('no_end_date')
        : $item->expires_at === null;
@endphp

{{--
    Editor de un elemento de un tema.

    Uno solo para documentos y artículos: los campos propios de cada tipo se
    muestran u ocultan según lo que se esté creando. Vive dentro del propio
    listado del tema, porque dar de alta algo no debe sacar de la página en la
    que se está trabajando.
--}}
<form method="POST" enctype="multipart/form-data"
      x-data="{
          kind: @js($kind),
          title: @js(old('title', $item->title ?? '')),
          adding: {{ old('new_category') ? 'true' : 'false' }},
          noEndDate: {{ $noEndDate ? 'true' : 'false' }},
          scheduling: {{ old('published_at') ? 'true' : 'false' }},
          publishedAt: @js(old('published_at', '')),
          get isDocument() { return this.kind === @js(TopicItem::KIND_DOCUMENT) },
          get isArticle() { return this.kind === @js(TopicItem::KIND_ARTICLE) },
          get isLink() { return this.kind === @js(TopicItem::KIND_LINK) },
          get isConvocation() { return this.kind === @js(TopicItem::KIND_CONVOCATION) },
          get isEvent() { return this.kind === @js(TopicItem::KIND_EVENT) },
          // Quién lleva fotos, vídeo y archivos. El documento entra aquí desde
          // que el editor admite varios: el portal publica hasta veinticinco
          // en uno solo. El evento va con el artículo: se publica igual, con su
          // cartel y su texto.
          get hasMedia() { return this.isArticle || this.isDocument || this.isConvocation || this.isEvent },
          get isFuture() {
              return this.scheduling && this.publishedAt && new Date(this.publishedAt) > new Date();
          },
      }"
      action="{{ $editing
          ? route('admin.topics.items.update', [$topic, $item])
          : route('admin.topics.items.store', $topic) }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    @if ($errors->any())
        <div role="alert"
             class="mb-6 rounded-[3px] border border-line border-l-4 border-l-danger bg-danger-surface px-4 py-3">
            <p class="m-0 text-13-5 font-semibold text-danger">Revise los siguientes puntos</p>
            <ul class="m-0 mt-1 flex list-disc flex-col gap-1 pl-5 text-13-5 text-danger">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ---------------- Tipo de contenido ---------------- --}}
    <div class="mx-auto mb-6 w-fit">
        @if ($fixedKind)
            <p class="m-0 rounded-[3px] border border-stroke bg-tint px-8 py-[7px] text-14 text-muted">
                {{ $topic->itemNoun($kind) }}
            </p>
            @unless ($editing)
                <input type="hidden" name="kind" value="{{ $kind }}">
            @endunless
        @else
            {{-- Hay temas que mezclan documentos y artículos en el mismo
                 listado, así que el recuadro del tipo es un selector. --}}
            <label for="kind{{ $uid }}" class="sr-only">Tipo de contenido</label>
            <select id="kind{{ $uid }}" name="kind" x-model="kind"
                    class="rounded-[3px] border border-stroke bg-card px-8 py-[7px] text-center text-14 text-ink">
                @foreach ($kinds as $option)
                    <option value="{{ $option }}" @selected($kind === $option)>{{ $topic->itemNoun($option) }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ---------------- Enlace (documento o enlace) ---------------- --}}
    <fieldset class="mb-5 border-0 p-0" x-show="isDocument || isLink" :disabled="! isDocument && ! isLink" x-cloak>
        <label for="link{{ $uid }}" class="text-13-5 text-link">
            Link <span class="text-muted">(Agregar https:// o http://)</span>
        </label>
        {{-- Si el archivo ya está en el servidor, su dirección de origen no
             pinta nada aquí: llenaría el campo con el enlace del portal
             anterior y bastaría con guardar para volver a apuntar fuera. --}}
        <input id="link{{ $uid }}" name="link" type="url" inputmode="url" placeholder="Link"
               value="{{ old('link', $item->isDownloaded() ? null : $item->source_url) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        <p class="m-0 mt-1 text-12-5 text-muted" x-show="isDocument">
            Para documentos que se consultan en otro sitio. Si adjunta un archivo, no hace falta.
        </p>
        <p class="m-0 mt-1 text-12-5 text-muted" x-show="isLink" x-cloak>
            Adonde lleva este contenido. Es lo que lo define, así que hace falta.
        </p>
    </fieldset>

    {{-- ---------------- Título ---------------- --}}
    <div class="mb-5">
        <label for="title{{ $uid }}" class="text-13-5 text-link">
            Título <span class="text-muted">(150 caracteres)</span>
        </label>
        {{-- El `value` va también en el HTML, no solo en el x-model: sin
             JavaScript el campo llegaría vacío y se perdería el título. --}}
        <input id="title{{ $uid }}" name="title" type="text" maxlength="150" required x-model="title"
               placeholder="Título" value="{{ old('title', $item->title) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        <output class="mt-1 block text-12-5 text-muted" x-text="`Quedan ${150 - title.length} caracteres.`"></output>
    </div>

    {{-- ---------------- Fecha de expedición (documento) ---------------- --}}
    <fieldset class="mb-5 border-0 p-0" x-show="isDocument" :disabled="! isDocument" x-cloak>
        <label for="issued_at{{ $uid }}" class="text-13-5 text-link">Fecha expedición</label>
        <input id="issued_at{{ $uid }}" name="issued_at" type="date"
               value="{{ old('issued_at', $item->issued_at?->format('Y-m-d')) }}"
               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        <p class="m-0 mt-1 text-12-5 text-muted">
            La del documento en sí, no la del día en que se sube.
        </p>
    </fieldset>

    {{-- ---------------- Evento ---------------- --}}
    {{--
        Organizador, lugar y cuándo empieza: los tres datos que el portal pide
        para un evento, con sus mismos topes de setenta caracteres.

        La importación ya los traía —llegan como «EventHost» y «EventLocation»
        entre los atributos del contenido— pero no había dónde escribirlos: un
        evento creado aquí salía sin lugar ni organizador, y uno importado los
        perdía en cuanto alguien lo editaba.

        La fecha y la hora, en dos campos como allí. El de la convocatoria usa
        un «datetime-local» porque son dos momentos con minutos exactos; una
        agenda se escribe en día y hora redonda.
    --}}
    <fieldset class="mb-5 border-0 p-0" x-show="isEvent" :disabled="! isEvent" x-cloak>
        <div class="mb-4">
            <label for="event_host{{ $uid }}" class="text-13-5 text-link">
                Organizador <span class="text-muted">(70 caracteres)</span>
            </label>
            <input id="event_host{{ $uid }}" name="event_host" type="text" maxlength="70"
                   value="{{ old('event_host', $item->event_host) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        </div>

        <div class="mb-4">
            <label for="event_location{{ $uid }}" class="text-13-5 text-link">
                Lugar <span class="text-muted">(70 caracteres)</span>
            </label>
            <input id="event_location{{ $uid }}" name="event_location" type="text" maxlength="70"
                   value="{{ old('event_location', $item->event_location) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="event_date{{ $uid }}" class="text-13-5 text-link">Fecha inicio</label>
                <input id="event_date{{ $uid }}" name="event_date" type="date"
                       value="{{ old('event_date', $item->opens_at?->format('Y-m-d')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>

            <div>
                <label for="event_time{{ $uid }}" class="text-13-5 text-link">Hora inicio</label>
                <input id="event_time{{ $uid }}" name="event_time" type="time"
                       value="{{ old('event_time', $item->opens_at?->format('H:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>
        </div>
    </fieldset>

    {{-- ---------------- Apertura y cierre (convocatoria) ---------------- --}}
    <fieldset class="mb-5 border-0 p-0" x-show="isConvocation" :disabled="! isConvocation" x-cloak>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="opens_at{{ $uid }}" class="text-13-5 text-link">Fecha y hora de inicio</label>
                <input id="opens_at{{ $uid }}" name="opens_at" type="datetime-local"
                       value="{{ old('opens_at', $item->opens_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>

            <div>
                <label for="closes_at{{ $uid }}" class="text-13-5 text-link">Fecha y hora de cierre</label>
                <input id="closes_at{{ $uid }}" name="closes_at" type="datetime-local"
                       value="{{ old('closes_at', $item->closes_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            </div>
        </div>

        <p class="m-0 mt-1 text-12-5 text-muted">
            Cerrada se sigue consultando: la fecha de cierre informa del proceso,
            no retira la convocatoria del listado.
        </p>
    </fieldset>

    {{-- ---------------- Fecha final de visualización (artículo) ---------------- --}}
    <fieldset class="mb-5 border-0 p-0" x-show="! isDocument && ! isConvocation"
              :disabled="isDocument || isConvocation" x-cloak>
        <label for="no_end_date{{ $uid }}" class="flex items-center gap-2 text-13-5 text-body">
            {{-- @checked además de x-model: sin JavaScript el estado tiene que
                 salir del HTML, no del componente. --}}
            <input id="no_end_date{{ $uid }}" name="no_end_date" type="checkbox" value="1" x-model="noEndDate"
                   @checked($noEndDate)
                   class="size-4 rounded-[2px] border-stroke accent-azure">
            Sin fecha final de visualización
        </label>

        <div x-show="! noEndDate" x-cloak class="mt-2">
            <label for="expires_at{{ $uid }}" class="text-13-5 text-link">Fecha final de visualización</label>
            {{-- Deshabilitado al marcar la casilla: así la fecha escondida no
                 viaja con el formulario y no puede contradecir a la casilla. --}}
            <input id="expires_at{{ $uid }}" name="expires_at" type="datetime-local" :disabled="noEndDate"
                   value="{{ old('expires_at', $item->expires_at?->format('Y-m-d\TH:i')) }}"
                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
            <p class="m-0 mt-1 text-12-5 text-muted">
                Pasada esa fecha deja de mostrarse, sin tener que retirarlo a mano.
            </p>
        </div>
    </fieldset>

    {{-- ---------------- Descripción ---------------- --}}
    <div class="mb-6">
        <span class="text-13-5 text-link">Descripción</span>
        <input type="hidden" name="body" id="body-input{{ $uid }}" value="{{ old('body', $item->body) }}">

        {{-- Quill escribe en el input oculto; ver resources/js/admin.js. --}}
        <div data-huv-editor="#body-input{{ $uid }}"
             data-huv-editor-label="Descripción del contenido"
             class="mt-1 min-h-[240px] rounded-b-[3px] bg-card"></div>

        <noscript>
            <p class="m-0 mt-2 rounded-[3px] border border-line bg-tint px-3 py-2 text-12-5 text-muted">
                El editor con formato necesita JavaScript.
            </p>
        </noscript>
    </div>

    {{-- ---------------- Medios del artículo ---------------- --}}
    {{-- `disabled` además de `x-show`: un campo obligatorio que solo está
         oculto sigue participando en la validación del navegador, y este se
         niega a enviar el formulario sin poder señalar dónde está el problema
         porque no se ve. Deshabilitado, ni valida ni viaja. --}}
    {{-- ---------------- Archivo del documento ---------------- --}}
    <fieldset class="border-0 p-0" x-show="isDocument" :disabled="! isDocument" x-cloak>
        <div class="mb-6">
            @if ($editing && $item->isDownloaded())
                <p class="m-0 mb-2 flex flex-wrap items-center gap-2 text-13-5 text-body">
                    <svg class="size-4 shrink-0 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3v5h5" />
                        <path d="M19 8v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h8Z" />
                    </svg>
                    Archivo principal:
                    <a href="{{ $item->fileUrl() }}" class="font-semibold text-link">{{ $item->file_name }}</a>
                    <span class="text-muted">({{ $item->extension() }} · {{ $item->humanSize() }})</span>
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
                    {{ $editing && $item->isDownloaded() ? 'Reemplazar el archivo principal' : 'Archivo principal' }}
                </span>
                <span class="text-12-5 text-muted">Peso máximo: 30 MB · pdf, doc, xls, ppt, csv, txt o zip</span>
            </label>
            <input id="file{{ $uid }}" name="file" type="file" class="sr-only"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip">
        </div>

        <div class="mb-6">
            {{-- El portal actual rotula este campo «Descripción de la imagen»
                 aunque aquí solo se adjuntan documentos: se nombra por lo que
                 es, que es lo que se lee al descargar. --}}
            <label for="file_alt{{ $uid }}" class="sr-only">Descripción del archivo</label>
            <input id="file_alt{{ $uid }}" name="file_alt" type="text" maxlength="250"
                   placeholder="Agregue una descripción al archivo"
                   value="{{ old('file_alt', $item->file_name) }}"
                   class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[10px] text-14 text-ink">
        </div>
    </fieldset>

    {{--
        Fotos, vídeo y archivos.

        Los archivos los lleva el artículo, la convocatoria y también el
        documento: un aviso, una pregunta o un enlace son solo título y texto.
        El documento entró aquí porque el portal publica hasta veinticinco
        archivos en uno solo y el editor únicamente dejaba subir el primero.

        Las fotos, el vídeo y la biblioteca se quedan en el artículo: son lo
        único que su ficha pinta. Ofrecerlas en un documento sería guardar en
        disco algo que no se ve en ninguna parte.
    --}}
    @if (array_intersect([TopicItem::KIND_ARTICLE, TopicItem::KIND_CONVOCATION, TopicItem::KIND_DOCUMENT], $kinds))
        <fieldset class="border-0 p-0" x-show="hasMedia" :disabled="! hasMedia" x-cloak>
            @php
                // Fotos y vídeo solo donde la ficha los pinta. La de un
                // documento y la de una convocatoria son texto y archivos; la
                // de un evento es la de un artículo, con su cartel.
                $conGaleria = array_intersect([TopicItem::KIND_ARTICLE, TopicItem::KIND_EVENT], $kinds) !== [];
            @endphp

            @include('admin.contents.partials.media', [
                'content' => $item,
                'gallery' => $conGaleria,
                'galleryWhen' => 'isArticle || isEvent',
            ])

            @if ($conGaleria)
                <div x-show="isArticle || isEvent" x-cloak>
                    @include('admin.contents.partials.library', ['content' => $item])
                </div>
            @endif
        </fieldset>
    @endif

    {{-- ---------------- Categorías ---------------- --}}
    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-13-5 text-link">Categorías</span>

            <button type="button" @click="adding = ! adding"
                    :aria-expanded="adding ? 'true' : 'false'"
                    aria-controls="new_category{{ $uid }}"
                    class="rounded-full border border-rule-accent bg-card px-4 py-[6px] text-13-5
                           font-semibold text-link transition-colors hover:bg-tint">
                Agregar categoría
                <span aria-hidden="true" x-text="adding ? '−' : '+'">+</span>
            </button>
        </div>

        @if ($topic->categories->isNotEmpty())
            {{-- Casillas y no un desplegable: un contenido puede estar en varias
                 a la vez, como el programa de transparencia, que está en
                 «Programa PTEE» y en «2025». --}}
            <fieldset class="mt-2 flex flex-wrap gap-x-5 gap-y-2 border-0 p-0">
                <legend class="sr-only">Categorías de {{ $topic->name }}</legend>
                @foreach ($topic->categories as $category)
                    <label for="cat{{ $uid }}-{{ $category->id }}" class="flex items-center gap-2 text-13-5 text-body">
                        <input id="cat{{ $uid }}-{{ $category->id }}" name="topic_category_ids[]" type="checkbox"
                               value="{{ $category->id }}" @checked($chosenCategories->contains($category->id))
                               class="size-4 rounded-[2px] border-stroke accent-azure">
                        {{ $category->name }}
                    </label>
                @endforeach
            </fieldset>
        @endif

        <div x-show="adding" x-cloak class="mt-3 max-w-[320px]">
            <label for="new_category{{ $uid }}" class="sr-only">Nombre de la categoría nueva</label>
            <input id="new_category{{ $uid }}" name="new_category" type="text" maxlength="120"
                   value="{{ old('new_category') }}"
                   placeholder="Por ejemplo: {{ now()->year }}"
                   class="w-full rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 text-ink">
        </div>
    </div>

    {{-- ---------------- Participación ---------------- --}}
    <fieldset class="mb-6 max-w-[420px] border-0 p-0" x-show="! isDocument" :disabled="isDocument" x-cloak>
        @include('admin.partials.participacion', [
            'uid' => $uid,
            'value' => $item->comment_wall,
        ])
    </fieldset>

    {{-- ---------------- Publicación ---------------- --}}
    <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-4">
        <div class="flex flex-col gap-2">
            <label for="is_featured{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="is_featured{{ $uid }}" name="is_featured" type="checkbox" value="1"
                       @checked(old('is_featured', $item->is_featured))
                       class="size-4 rounded-[2px] border-stroke accent-azure">
                Destacar contenido
            </label>

            <label for="show_in_feed{{ $uid }}" class="flex items-center gap-2 text-13-5 text-link">
                <input id="show_in_feed{{ $uid }}" name="show_in_feed" type="checkbox" value="1"
                       @checked(old('show_in_feed', ! $item->is_hidden))
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
                        <x-share :title="$item->title" :url="route('topics.items.show', [$topic, $item])" />
                    </div>
                </details>
            @endif
        </div>
    </div>

    {{-- Fecha de publicación: solo aparece al pulsar «Programar». --}}
    <div x-show="scheduling" x-cloak class="mt-4 ml-auto max-w-[320px]">
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
    <form method="POST" action="{{ route('admin.topics.items.destroy', [$topic, $item]) }}" class="mt-4"
          onsubmit="return confirm('¿Eliminar este contenido? La acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="border-0 bg-transparent p-0 text-13-5 font-semibold text-danger underline underline-offset-4">
            Eliminar contenido
        </button>
    </form>
@endif

@stack('after-form')
