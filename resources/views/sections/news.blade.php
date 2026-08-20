@php
    // Un bloque puede combinar varias secciones; cada una trae su propio
    // rótulo y su propia lista.
    $groups = $newsBlock['groups'];
@endphp

<section id="noticias" aria-labelledby="huv-noticias" class="relative text-on-brand"
         style="background: {{ $newsBlock['block']->themeColor() }}">

    <x-edit-chip section="noticias" :label="__('portada.chip.noticias')"
                 :url="route('admin.blocks.edit', $newsBlock['block'])" floating />

    <x-container class="py-12 lg:py-14">

        <h2 id="huv-noticias" class="sr-only">{{ __('portada.noticias.titulo') }}</h2>

        @if ($groups->isEmpty())
            <p class="m-0 rounded-[4px] border border-dashed border-on-brand/40 px-5 py-10 text-center
                      text-14 text-on-brand-muted">
                {{ __('portada.noticias.vacio') }}
            </p>
        @endif

        @foreach ($groups as $group)
            <div class="{{ ! $loop->first ? 'mt-12' : '' }}">

                <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                    @if ($newsBlock['block']->show_title)
                        <p class="m-0 font-display text-22 font-bold underline decoration-2 underline-offset-8 lg:text-26">
                            {{ $group['title'] }}
                        </p>
                    @endif

                    @auth
                        <a href="{{ route('admin.contents.create') }}"
                           x-show="$store.huvUi.editMode" x-cloak
                           class="inline-flex items-center gap-2 rounded-full border border-on-brand/50 px-4 py-[7px]
                                  text-12-5 font-semibold text-on-brand no-underline hover:bg-white/10
                                  hover:text-on-brand hover:no-underline">
                            <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            {{ __('portada.noticias.agregar') }}
                        </a>
                    @endauth
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">

                    {{-- Nota principal --}}
                    @if ($group['featured'])
                        @php $featured = $group['featured']; @endphp
                        <article class="flex flex-col gap-4">
                            <a href="{{ $featured->url() }}" tabindex="-1" aria-hidden="true"
                               class="block aspect-video overflow-hidden rounded-[3px] bg-white/10">
                                <x-image-slot :src="$featured->imageUrl()" :alt="''"
                                              :hint="__('portada.noticias.foto_principal')" :bordered="false" />
                            </a>

                            <div class="flex flex-col gap-[10px]">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="m-0 font-display text-17 leading-[1.4] font-bold text-balance lg:text-19">
                                        <a href="{{ $featured->url() }}"
                                           class="text-on-brand underline decoration-1 underline-offset-4 hover:text-on-brand">
                                            <x-texto-del-portal>{{ $featured->title }}</x-texto-del-portal>
                                        </a>
                                    </h3>
                                    <x-content-actions :content="$featured" tone="on-brand" class="mt-[2px]" />
                                </div>

                                {{-- El resumen conserva sus saltos de línea, así que se imprime
                                     pegado a las etiquetas: con `whitespace-pre-line`, el sangrado
                                     de esta plantilla saldría como una línea en blanco delante. --}}
                                <x-texto-del-portal tag="p" class="m-0 text-14 leading-[1.6] whitespace-pre-line text-pretty text-on-brand-muted">{{ $featured->summary() }}</x-texto-del-portal>

                                @if ($featured->displayDate())
                                    <x-published-at :value="$featured->displayDate()" class="text-12-5 text-on-brand-label" />
                                @endif

                                <x-content-badges :content="$featured" />
                            </div>
                        </article>
                    @endif

                    {{-- Titulares --}}
                    <ul class="flex flex-col gap-5 {{ $group['featured'] ? '' : 'lg:col-span-2 lg:grid lg:grid-cols-2 lg:gap-8' }}">
                        @foreach ($group['items'] as $item)
                            <li>
                                <article class="grid grid-cols-[110px_minmax(0,1fr)] gap-4 sm:grid-cols-[150px_minmax(0,1fr)]">
                                    <a href="{{ $item->url() }}" tabindex="-1" aria-hidden="true"
                                       class="block aspect-video overflow-hidden rounded-[3px] bg-white/10">
                                        <x-image-slot :src="$item->imageUrl()" :alt="''"
                                                      :hint="__('portada.noticias.miniatura')" :bordered="false" />
                                    </a>

                                    <div class="flex min-w-0 flex-col gap-[6px]">
                                        <div class="flex items-start justify-between gap-2">
                                            <h3 class="m-0 font-display text-14 leading-[1.4] font-semibold text-pretty">
                                                <a href="{{ $item->url() }}"
                                                   class="text-on-brand underline decoration-1 underline-offset-4 hover:text-on-brand">
                                                    <x-texto-del-portal>{{ $item->title }}</x-texto-del-portal>
                                                </a>
                                            </h3>
                                            <x-content-actions :content="$item" tone="on-brand" />
                                        </div>

                                        @if ($item->displayDate())
                                            <x-published-at :value="$item->displayDate()" class="text-12 text-on-brand-label" />
                                        @endif

                                        <x-content-badges :content="$item" />
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach

        @if ($groups->isNotEmpty())
            <p class="m-0 mt-9">
                <a href="{{ config('huv.news.all_url') }}"
                   class="group inline-flex items-center gap-2 rounded-[3px] border border-on-brand/40 px-5 py-[10px]
                          font-display text-13-5 font-semibold text-on-brand no-underline
                          transition-colors hover:border-on-brand hover:bg-white/10 hover:text-on-brand hover:no-underline">
                    {{ __('portada.noticias.ver_todas') }}
                    <span aria-hidden="true" class="transition-transform group-hover:translate-x-[3px]">→</span>
                </a>
            </p>
        @endif
    </x-container>
</section>
