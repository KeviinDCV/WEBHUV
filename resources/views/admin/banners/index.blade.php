@extends('layouts.admin')

@section('title', 'Administración de banners')
@section('heading', 'Administración de banners')
@section('subheading', 'Agrega y/o edita el contenido de los banners y el orden en que aparecerán (máximo '.\App\Models\Banner::MAX.' banners).')

@section('content')
    <div x-data="huvBannerOrder(@js($banners->pluck('id')))">

        @if ($banners->count() < \App\Models\Banner::MAX)
            <a href="{{ route('admin.banners.create') }}"
               class="mb-7 inline-flex items-center gap-2 rounded-full border border-rule-accent bg-card px-5 py-[10px]
                      font-display text-14 font-semibold text-link no-underline transition-colors
                      hover:bg-tint hover:no-underline">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Agregar
            </a>
        @else
            <p class="mb-7 rounded-[3px] border border-line bg-card px-4 py-3 text-13-5 text-muted">
                Ya hay {{ \App\Models\Banner::MAX }} banners publicados, el máximo permitido.
                Elimine uno para poder agregar otro.
            </p>
        @endif

        <form method="POST" action="{{ route('admin.banners.arrange') }}">
            @csrf

            {{-- El orden viaja como lista de identificadores en la posición final. --}}
            <template x-for="id in ids" :key="id">
                <input type="hidden" name="order[]" :value="id">
            </template>

            @if ($banners->isEmpty())
                <p class="rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10 text-center text-14 text-muted">
                    Todavía no hay banners. Use «Agregar» para publicar el primero.
                </p>
            @else
                <ul class="flex flex-col gap-px bg-line">
                    @foreach ($banners as $banner)
                        <li :style="{ order: position({{ $banner->id }}) }"
                            class="flex flex-wrap items-center gap-x-5 gap-y-3 bg-card px-4 py-3">

                            <div class="flex shrink-0 flex-col">
                                <button type="button" @click="move({{ $banner->id }}, -1)"
                                        x-show="! isFirst({{ $banner->id }})"
                                        aria-label="Subir el banner {{ $loop->iteration }}"
                                        class="flex size-6 items-center justify-center border-0 bg-transparent text-link hover:text-heading">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m5 15 7-7 7 7" />
                                    </svg>
                                </button>
                                <button type="button" @click="move({{ $banner->id }}, 1)"
                                        x-show="! isLast({{ $banner->id }})"
                                        aria-label="Bajar el banner {{ $loop->iteration }}"
                                        class="flex size-6 items-center justify-center border-0 bg-transparent text-link hover:text-heading">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m5 9 7 7 7-7" />
                                    </svg>
                                </button>
                            </div>

                            <span class="w-5 shrink-0 font-display text-16 font-bold text-heading"
                                  x-text="position({{ $banner->id }})">{{ $loop->iteration }}</span>

                            <img src="{{ $banner->imageUrl() }}" alt=""
                                 width="{{ \App\Models\Banner::IMAGE_WIDTH }}"
                                 height="{{ \App\Models\Banner::IMAGE_HEIGHT }}"
                                 loading="lazy" decoding="async"
                                 class="h-[42px] w-[100px] shrink-0 rounded-[2px] border border-line object-cover">

                            <p class="m-0 min-w-[200px] flex-1 text-13 break-all text-muted">
                                @if ($banner->link)
                                    <span class="font-semibold text-body">Enlace:</span> {{ $banner->link }}
                                @else
                                    <span class="italic">Sin enlace</span>
                                @endif
                            </p>

                            <a href="{{ route('admin.banners.edit', $banner) }}"
                               class="shrink-0 text-13-5 font-semibold text-link underline underline-offset-4">
                                Editar<span class="sr-only"> el banner «{{ $banner->alt_text }}»</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <p class="sr-only" aria-live="polite" x-text="announcement"></p>
            @endif

            <div class="mt-9">
                <h2 class="m-0 font-display text-15 font-bold text-heading">Duración de rotación</h2>
                <p class="m-0 mt-1 mb-3 text-13-5 text-muted">
                    Define los segundos que durará la rotación automática de las imágenes.
                </p>
                <label for="rotation" class="sr-only">Segundos entre banners</label>
                <select id="rotation" name="rotation"
                        class="rounded-[3px] border border-stroke bg-card px-3 py-[9px] text-14 font-semibold text-heading">
                    @foreach (\App\Http\Controllers\Admin\BannerController::ROTATION_OPTIONS as $seconds)
                        <option value="{{ $seconds }}" @selected($rotation === $seconds)>
                            {{ $seconds }} segundos
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('home') }}"
                   class="rounded-full border border-stroke bg-card px-6 py-[10px] font-display text-14
                          font-semibold text-heading no-underline hover:bg-tint hover:no-underline">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14 font-semibold text-on-accent
                               transition-colors hover:bg-azure-dark">
                    Guardar
                </button>
            </div>
        </form>
    </div>
@endsection
