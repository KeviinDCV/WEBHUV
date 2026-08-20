@php use App\Models\ShortcutBlock; @endphp

<section aria-labelledby="huv-accesos" class="bg-page">
    <x-container class="py-12 lg:py-16">
        <h2 id="huv-accesos" class="sr-only">{{ __('portada.atajos.titulo') }}</h2>

        @if ($shortcutBlocks->isEmpty())
            @auth
                <p x-show="$store.huvUi.editMode" x-cloak
                   class="m-0 rounded-[4px] border border-dashed border-stroke-strong bg-card px-5 py-10
                          text-center text-14 text-muted">
                    {{ __('portada.atajos.vacio') }}
                </p>
            @endauth
        @else
            <div class="flex flex-col gap-12">
                @foreach ($shortcutBlocks as $block)
                    @continue ($block->shortcuts->isEmpty())

                    <div>
                        @auth
                            <div x-show="$store.huvUi.editMode" x-cloak
                                 class="mb-4 flex flex-wrap items-center justify-end gap-3">
                                @unless ($block->isPublishable())
                                    <span class="rounded-[2px] bg-warning px-2 py-[2px] text-10-5 font-bold
                                                 tracking-[0.06em] uppercase" style="color: #000">
                                        {{ __('portada.atajos.sin_publicar', [
                                            'faltan' => ShortcutBlock::MIN_TO_PUBLISH - $block->shortcuts->count(),
                                        ]) }}
                                    </span>
                                @endunless

                                <a href="{{ route('admin.shortcuts.edit', $block) }}"
                                   data-huv-edit="accesos-{{ $block->id }}"
                                   class="inline-flex items-center gap-[6px] rounded-full border border-rule-accent
                                          bg-card px-4 py-[6px] text-12-5 font-semibold text-link no-underline
                                          hover:bg-tint hover:no-underline">
                                    <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                    {{ __('portada.atajos.editar') }}<span class="sr-only">{{ __('portada.atajos.editar_barra', ['barra' => $block->name]) }}</span>
                                </a>
                            </div>
                        @endauth

                        <ul class="grid grid-cols-2 gap-x-4 gap-y-9 sm:grid-cols-3 md:grid-cols-5">
                            @foreach ($block->shortcuts as $shortcut)
                                <li>
                                    <a href="{{ $shortcut->resolvedUrl() }}"
                                       @if ($shortcut->isExternal())
                                           target="_blank" rel="noopener noreferrer"
                                       @endif
                                       class="group flex h-full flex-col items-center gap-3 rounded-[4px] px-2 py-3
                                              text-center no-underline transition-colors hover:bg-tint hover:no-underline">
                                        <span style="color: {{ $block->themeColor() }}">
                                            <x-quick-icon :name="$shortcut->icon"
                                                          class="size-7 transition-transform group-hover:-translate-y-[2px]" />
                                        </span>
                                        <span class="font-display text-12-5 leading-[1.35] font-bold text-heading">
                                            <x-texto-del-portal>{{ $shortcut->label }}</x-texto-del-portal>
                                            @if ($shortcut->isExternal())
                                                <span class="sr-only">{{ __('portada.nueva_pestana') }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </x-container>
</section>
