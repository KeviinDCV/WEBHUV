@php
    use App\Models\LibraryImage;
    use App\Models\MediaCategory;

    $categories = MediaCategory::orderBy('name')->get();
    $libraryImages = LibraryImage::with('category')->latest()->get();

    // Imágenes de la biblioteca ya vinculadas a este contenido.
    $attached = $content->exists
        ? $content->media->pluck('library_image_id')->filter()->values()->all()
        : [];

    // Puede haber dos editores en la misma página: sin sufijo, los `id`
    // chocarían y las etiquetas apuntarían al campo equivocado.
    $uid = $uid ?? '';
@endphp

<div class="mb-8"
     x-data="{
         category: 'todas',
         picked: @js(old('library_ids', $attached)).map(Number),
         toggle(id) {
             const at = this.picked.indexOf(id);
             at === -1 ? this.picked.push(id) : this.picked.splice(at, 1);
         },
         isPicked(id) { return this.picked.includes(id) },
         get visible() { return this.$refs.grid?.children ?? [] },
     }">

    <p class="m-0 mb-3 text-13-5 font-semibold text-heading">Elige una imagen de la biblioteca</p>

    {{-- Los identificadores elegidos viajan con el formulario del contenido. --}}
    <template x-for="id in picked" :key="id">
        <input type="hidden" name="library_ids[]" :value="id">
    </template>

    {{-- ---------------- Categorías ---------------- --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <button type="button" @click="category = 'todas'"
                :class="category === 'todas' ? 'bg-azure text-on-accent' : 'bg-tint text-heading'"
                class="rounded-[3px] border-0 px-3 py-[5px] text-12-5 font-semibold">
            Todas
        </button>

        @foreach ($categories as $categoryOption)
            <button type="button" @click="category = '{{ $categoryOption->slug }}'"
                    :class="category === '{{ $categoryOption->slug }}' ? 'bg-azure text-on-accent' : 'bg-tint text-heading'"
                    class="rounded-[3px] border-0 px-3 py-[5px] text-12-5 font-semibold">
                {{ $categoryOption->name }}
            </button>
        @endforeach

        {{-- Los formularios para crear categorías y subir imágenes están al pie
             de la página: no pueden anidarse dentro del formulario del
             contenido. --}}
        <a href="#huv-biblioteca-gestion{{ $uid }}"
           class="rounded-full border border-rule-accent bg-card px-3 py-[5px] text-12-5 font-semibold text-link no-underline">
            Agregar categoría +
        </a>
    </div>

    {{-- ---------------- Rejilla ---------------- --}}
    @if ($libraryImages->isEmpty())
        <p class="m-0 rounded-[3px] border border-dashed border-stroke-strong bg-card px-4 py-8 text-center
                  text-14 text-muted">
            No hay imágenes en la biblioteca todavía.
        </p>
    @else
        <ul x-ref="grid" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($libraryImages as $image)
                <li x-show="category === 'todas' || category === '{{ $image->category?->slug ?? 'sin-categoria' }}'">
                    <label class="block cursor-pointer">
                        <input type="checkbox" class="sr-only"
                               :checked="isPicked({{ $image->id }})"
                               @change="toggle({{ $image->id }})">
                        <img src="{{ $image->fileUrl() }}" alt="{{ $image->alt }}"
                             loading="lazy" decoding="async"
                             class="aspect-[4/3] w-full rounded-[3px] border-2 object-cover transition"
                             :class="isPicked({{ $image->id }}) ? 'border-azure' : 'border-line'">
                        <span class="mt-1 block truncate text-11-5 text-muted">{{ $image->alt }}</span>
                        <span class="sr-only" x-text="isPicked({{ $image->id }}) ? 'Seleccionada' : 'Sin seleccionar'"></span>
                    </label>
                </li>
            @endforeach
        </ul>

        <p class="m-0 mt-2 text-12-5 text-muted">
            <span x-text="picked.length"></span> imagen(es) de la biblioteca en este contenido.
        </p>
    @endif

    <p class="m-0 mt-3 text-12 text-faint">
        Estas imágenes se comparten entre contenidos: al quitarlas de aquí no se borran de la
        biblioteca.
    </p>
</div>

{{-- ---------------------------------------------------------------------
     Formularios auxiliares. Van fuera del formulario del contenido porque
     un formulario no puede anidarse dentro de otro.
--------------------------------------------------------------------- --}}
@push('after-form')
    <section id="huv-biblioteca-gestion{{ $uid }}" aria-labelledby="huv-biblioteca-titulo{{ $uid }}" class="mt-10">
        <h2 id="huv-biblioteca-titulo{{ $uid }}" class="m-0 mb-3 font-display text-15 font-bold text-heading">
            Gestionar la biblioteca de imágenes
        </h2>

        {{-- <details> en lugar de mostrar/ocultar con JavaScript: es plegable
             de forma nativa y accesible sin una línea de código. --}}
        <details class="rounded-[3px] border border-line bg-card">
            <summary class="cursor-pointer px-4 py-3 text-13-5 font-semibold text-link">
                Subir una imagen a la biblioteca
            </summary>
            <form method="POST" action="{{ route('admin.library.images.store') }}"
                  enctype="multipart/form-data" class="border-t border-line px-4 py-4">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="library_image{{ $uid }}" class="text-12-5 text-body">Archivo</label>
                        <input id="library_image{{ $uid }}" name="image" type="file" required
                               accept="image/jpeg,image/png,image/gif,image/bmp,image/webp"
                               class="mt-1 w-full text-12-5">
                    </div>
                    <div>
                        <label for="library_alt{{ $uid }}" class="text-12-5 text-body">Descripción</label>
                        <input id="library_alt{{ $uid }}" name="alt" type="text" maxlength="250" required
                               class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-2 py-[7px] text-13">
                    </div>
                    <div>
                        <label for="library_category{{ $uid }}" class="text-12-5 text-body">Categoría</label>
                        <select id="library_category{{ $uid }}" name="media_category_id"
                                class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-2 py-[7px] text-13">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $categoryOption)
                                <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="m-0 mt-2 text-12 text-faint">
                    La descripción es obligatoria: acompañará a la imagen en todos los contenidos
                    donde se use.
                </p>
                <button type="submit"
                        class="mt-3 rounded-full border-0 bg-azure px-5 py-[9px] text-13-5 font-semibold text-on-accent">
                    Subir imagen
                </button>
            </form>
        </details>

        <details class="mt-3 rounded-[3px] border border-line bg-card">
            <summary class="cursor-pointer px-4 py-3 text-13-5 font-semibold text-link">
                Agregar una categoría
            </summary>
            <form method="POST" action="{{ route('admin.library.categories.store') }}"
                  class="flex flex-wrap items-end gap-3 border-t border-line px-4 py-4">
                @csrf
                <div class="min-w-[220px] flex-1">
                    <label for="category_name{{ $uid }}" class="text-12-5 text-body">Nombre</label>
                    <input id="category_name{{ $uid }}" name="name" type="text" maxlength="60" required
                           placeholder="Por ejemplo: Fachadas"
                           class="mt-1 w-full rounded-[3px] border border-stroke bg-card px-3 py-[8px] text-14">
                </div>
                <button type="submit"
                        class="rounded-full border-0 bg-azure px-5 py-[9px] text-13-5 font-semibold text-on-accent">
                    Crear categoría
                </button>
            </form>
        </details>
    </section>
@endpush
