@php
    use App\Models\ContentMedia;

    $images = $content->exists ? $content->images() : collect();
    $files = $content->exists ? $content->files() : collect();
    $video = $content->exists ? $content->video() : null;
    $mainId = $content->exists ? $content->mainImage()?->id : null;

    // Puede haber dos editores en la misma página: sin sufijo, los `id`
    // chocarían y las etiquetas apuntarían al campo equivocado.
    $uid = $uid ?? '';
@endphp

<fieldset class="mb-8 border-0 p-0"
          x-data="{
              newPhotos: [],
              newFiles: [],
              onPhotos(event) {
                  this.newPhotos = Array.from(event.target.files).map((file) => ({
                      name: file.name,
                      url: URL.createObjectURL(file),
                  }));
              },
              onFiles(event) {
                  this.newFiles = Array.from(event.target.files).map((file) => file.name);
              },
          }">
    <legend class="p-0 font-display text-15 font-bold text-heading">Medios</legend>
    <p class="m-0 mt-1 mb-5 text-13-5 text-muted">
        Fotos, vídeo y documentos que acompañan al contenido.
    </p>

    {{-- ---------------- Fotos ya guardadas ---------------- --}}
    @if ($images->isNotEmpty())
        <p class="m-0 mb-2 text-13-5 font-semibold text-heading">Fotos publicadas</p>
        <ul class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($images as $image)
                <li x-data="{ remove: false }"
                    class="flex gap-4 rounded-[3px] border border-line bg-card p-3"
                    :class="remove && 'opacity-50'">
                    <img src="{{ $image->fileUrl() }}" alt=""
                         class="size-[86px] shrink-0 rounded-[2px] border border-line object-cover">

                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                        <label class="flex items-center gap-2 text-12-5 font-semibold text-heading">
                            <input type="radio" name="media_main" value="{{ $image->id }}"
                                   @checked($mainId === $image->id) :disabled="remove"
                                   class="size-4 accent-azure">
                            Principal
                        </label>

                        <label class="sr-only" for="media_alt_{{ $image->id }}{{ $uid }}">
                            Descripción de la foto {{ $loop->iteration }}
                        </label>
                        <input id="media_alt_{{ $image->id }}{{ $uid }}" name="media_alt[{{ $image->id }}]" type="text"
                               maxlength="250" value="{{ $image->alt }}" placeholder="Descripción de la imagen"
                               :disabled="remove"
                               class="w-full rounded-[3px] border border-stroke bg-card px-2 py-[6px] text-13">

                        <label class="flex items-center gap-2 text-12-5 text-danger">
                            <input type="checkbox" name="media_delete[]" value="{{ $image->id }}" x-model="remove"
                                   class="size-4 accent-danger">
                            Quitar esta foto
                        </label>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- ---------------- Agregar foto ---------------- --}}
        <div class="rounded-[3px] border border-dashed border-stroke-strong p-4">
            <label for="photos{{ $uid }}" class="flex cursor-pointer items-center gap-2 font-display text-14 font-semibold text-link">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 7.5h3l1.5-2.5h7L17 7.5h3a1 1 0 0 1 1 1V18a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8.5a1 1 0 0 1 1-1Z" />
                    <circle cx="12" cy="13" r="3.4" />
                </svg>
                Agrega foto
            </label>
            <p class="m-0 mt-2 text-12-5 leading-[1.6] text-muted">
                Dimensión recomendada {{ ContentMedia::IMAGE_WIDTH }} × {{ ContentMedia::IMAGE_HEIGHT }} px.<br>
                Peso máximo 2 MB. Formatos gif, jpg, jpeg, png, bmp, webp.
            </p>
            <input id="photos{{ $uid }}" name="photos[]" type="file" multiple @change="onPhotos($event)"
                   accept="image/jpeg,image/png,image/gif,image/bmp,image/webp" class="sr-only">

            <ul class="mt-3 flex flex-col gap-3" x-show="newPhotos.length" x-cloak>
                <template x-for="(photo, index) in newPhotos" :key="index">
                    <li class="flex items-start gap-2">
                        <img :src="photo.url" alt="" class="size-12 shrink-0 rounded-[2px] object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="m-0 truncate text-12 text-muted" x-text="photo.name"></p>
                            {{-- La descripción es obligatoria: el servidor la exige
                                 por cada foto que llegue. --}}
                            <input type="text" :name="`photo_alts[${index}]`" maxlength="250" required
                                   placeholder="Descripción de la imagen"
                                   class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-2 py-[5px] text-12-5">
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        {{-- ---------------- Agregar vídeo ---------------- --}}
        <div class="rounded-[3px] border border-dashed border-stroke-strong p-4">
            <label for="video_url{{ $uid }}" class="flex items-center gap-2 font-display text-14 font-semibold text-link">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M5 4.5 19 12 5 19.5Z" />
                </svg>
                Agrega vídeo
            </label>
            <p class="m-0 mt-2 mb-2 text-12-5 leading-[1.6] text-muted">
                URL de YouTube, con https:// por delante.
            </p>
            <input id="video_url{{ $uid }}" name="video_url" type="url" inputmode="url"
                   value="{{ old('video_url', $video?->url) }}"
                   placeholder="https://www.youtube.com/watch?v=…"
                   class="w-full rounded-[3px] border border-stroke bg-card px-2 py-[7px] text-13">
            <p class="m-0 mt-2 text-12 text-faint">Deje el campo vacío para quitar el vídeo.</p>
        </div>

        {{-- ---------------- Agregar archivo ---------------- --}}
        <div class="rounded-[3px] border border-dashed border-stroke-strong p-4">
            <label for="files{{ $uid }}" class="flex cursor-pointer items-center gap-2 font-display text-14 font-semibold text-link">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 11.5 12.5 19a4.2 4.2 0 0 1-6-6l7.6-7.6a2.8 2.8 0 0 1 4 4l-7.6 7.6a1.4 1.4 0 0 1-2-2l7-7" />
                </svg>
                Agrega archivo
            </label>
            <p class="m-0 mt-2 text-12-5 leading-[1.6] text-muted">
                Peso máximo 30 MB.<br>
                pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt, zip.
            </p>
            <input id="files{{ $uid }}" name="files[]" type="file" multiple @change="onFiles($event)"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip" class="sr-only">

            <ul class="mt-3 flex flex-col gap-2" x-show="newFiles.length" x-cloak>
                <template x-for="(name, index) in newFiles" :key="index">
                    <li>
                        <p class="m-0 truncate text-12 text-muted" x-text="name"></p>
                        <input type="text" :name="`file_titles[${index}]`" maxlength="250"
                               placeholder="Título visible del documento (opcional)"
                               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-2 py-[5px] text-12-5">
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- ---------------- Archivos ya guardados ---------------- --}}
    @if ($files->isNotEmpty())
        <p class="m-0 mt-6 mb-2 text-13-5 font-semibold text-heading">Documentos publicados</p>
        <ul class="flex flex-col gap-2">
            @foreach ($files as $file)
                <li x-data="{ remove: false }"
                    class="flex flex-wrap items-center gap-3 rounded-[3px] border border-line bg-card px-4 py-3"
                    :class="remove && 'opacity-50'">
                    <span class="shrink-0 rounded-[2px] bg-tint px-2 py-[2px] text-11 font-bold text-heading">
                        {{ $file->extension() }}
                    </span>

                    <label class="sr-only" for="file_alt_{{ $file->id }}{{ $uid }}">Título del documento</label>
                    <input id="file_alt_{{ $file->id }}{{ $uid }}" name="media_alt[{{ $file->id }}]" type="text"
                           maxlength="250" value="{{ $file->alt }}" :disabled="remove"
                           class="min-w-[200px] flex-1 rounded-[3px] border border-stroke bg-card px-2 py-[6px] text-13">

                    <span class="shrink-0 text-12 text-muted">{{ $file->humanSize() }}</span>

                    <label class="flex shrink-0 items-center gap-2 text-12-5 text-danger">
                        <input type="checkbox" name="media_delete[]" value="{{ $file->id }}" x-model="remove"
                               class="size-4 accent-danger">
                        Quitar
                    </label>
                </li>
            @endforeach
        </ul>
    @endif
</fieldset>
