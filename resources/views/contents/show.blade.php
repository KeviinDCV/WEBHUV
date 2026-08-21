@extends('layouts.app')

@section('title', $content->title.' — '.config('huv.institution.short_name'))
@section('description', Str::squish($content->summary(160)))

@section('og_type', 'article')
@if ($content->imageUrl())
    @section('og_image', $content->imageUrl())
@endif

@push('head')
    {{-- El bloque de la ficha y el rastro de migas. Las migas son las mismas
         que se pintan debajo: un dato estructurado que no se corresponde con
         lo que el visitante ve es marcado engañoso. --}}
    <x-datos-estructurados :datos="App\Support\StructuredData::article($content)" />
    <x-datos-estructurados :datos="App\Support\StructuredData::breadcrumbs([
        ['nombre' => __('paginas.ruta.inicio'), 'url' => route('home')],
        ['nombre' => App\Models\Content::categoryLabel($content->category), 'url' => $content->url()],
    ])" />

    @if (! $content->is_active || $content->is_hidden || $content->isScheduled())
        {{-- Lo no publicado no debe indexarse aunque alguien comparta el enlace. --}}
        <meta name="robots" content="noindex, nofollow">
    @endif
@endpush

@section('content')
    <div class="bg-page">
        <x-container class="py-8 lg:py-10">
            <div class="mx-auto max-w-[820px]">

                {{-- Rastro de navegación --}}
                <nav aria-label="{{ __('paginas.ruta.etiqueta') }}" class="mb-4">
                    <ol class="flex flex-wrap items-center gap-2 text-13 text-muted">
                        <li><a href="{{ route('home') }}" class="text-link">{{ __('paginas.ruta.inicio') }}</a></li>
                        <li aria-hidden="true">›</li>
                        <li aria-current="page" class="font-semibold text-heading">{{ App\Models\Content::categoryLabel($content->category) }}</li>
                    </ol>
                </nav>

                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    {{--
                        Las fechas del contenido, no las de su fila.

                        `updated_at` es cuándo se escribió aquí, y la
                        importación lo toca en cada pasada: la ficha de una
                        noticia de agosto decía «Modificación: hoy» cada vez que
                        se reimportaba Noticias. Se reserva para lo que se
                        escribió aquí y nunca vino del portal.
                    --}}
                    <p class="m-0 text-12 text-faint">
                        {{ __('paginas.ficha.modificacion') }}
                        <time datetime="{{ ($content->modified_at ?? $content->updated_at)->toIso8601String() }}">
                            {{ ($content->modified_at ?? $content->updated_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                        · {{ __('paginas.ficha.creacion') }}
                        <time datetime="{{ ($content->published_at ?? $content->created_at)->toIso8601String() }}">
                            {{ ($content->published_at ?? $content->created_at)->translatedFormat('Y/m/d H:i:s') }}
                        </time>
                    </p>

                    @auth
                        <a href="{{ route('admin.contents.edit', $content) }}"
                           x-show="$store.huvUi.editMode" x-cloak
                           data-huv-edit="contenido"
                           class="inline-flex items-center gap-[6px] text-13-5 font-semibold text-link">
                            {{ __('paginas.ficha.editar') }}
                            <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                        </a>
                    @endauth
                </div>

                <x-texto-del-portal tag="h1" class="m-0 font-display text-25 leading-[1.25] font-bold tracking-[-0.015em] text-balance text-heading lg:text-33">{{ $content->title }}</x-texto-del-portal>

                @auth
                    <div class="mt-3"><x-content-badges :content="$content" /></div>
                @endauth

                @if ($content->displayDate())
                    <p class="m-0 mt-3 text-13-5 text-muted">
                        <x-published-at :value="$content->displayDate()" />
                    </p>
                @endif

                <x-share :title="$content->title" class="mt-5" />

                @include('partials.ficha-medios', [
                    'item' => $content,
                    'body' => $content->body,
                    'fallback' => $content->excerpt,
                ])

                @include('partials.aviso-participacion', ['item' => $content])

                <hr class="my-10 border-0 border-t border-line">

                @include('partials.content-feedback', ['key' => $content->id])

                {{-- Contenidos relacionados --}}
                @if ($related->isNotEmpty())
                    <section aria-labelledby="huv-relacionados" class="mt-10">
                        <h2 id="huv-relacionados" class="m-0 mb-4 font-display text-17 font-bold text-heading">
                            {{ __('paginas.ficha.relacionados', ['contexto' => App\Models\Content::categoryLabel($content->category)]) }}
                        </h2>
                        {{-- Cada recuadro mide lo que mide su titular, como en el
                             resto de listados: `h-full` con la rejilla estirando
                             la fila dejaba el borde del más corto bajando hasta
                             donde acababa el más largo, con el blanco dentro. --}}
                        <ul class="grid grid-cols-1 items-start gap-4 sm:grid-cols-3">
                            @foreach ($related as $item)
                                <li>
                                    <a href="{{ $item->url() }}"
                                       class="block rounded-[4px] border border-line bg-card p-4 text-14
                                              leading-[1.45] font-semibold text-heading no-underline
                                              hover:bg-tint hover:no-underline">
                                        <x-texto-del-portal>{{ $item->title }}</x-texto-del-portal>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </x-container>
    </div>
@endsection
