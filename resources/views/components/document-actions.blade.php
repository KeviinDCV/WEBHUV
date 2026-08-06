@props(['document'])

@auth
    @php
        $topic = $document->topic;

        $actions = [
            [
                'label' => $document->is_featured ? 'Ya está destacado' : 'Destacar',
                'route' => route('admin.documents.feature', [$topic, $document]),
                'disabled' => $document->is_featured,
                'icon' => 'M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z',
            ],
            [
                'label' => $document->is_active ? 'Inactivar' : 'Activar',
                'route' => route('admin.documents.active', [$topic, $document]),
                'icon' => 'M12 4v8M7.5 6.3a7.5 7.5 0 1 0 9 0',
            ],
            [
                'label' => $document->is_hidden ? 'Mostrar' : 'Ocultar',
                'route' => route('admin.documents.hidden', [$topic, $document]),
                'icon' => $document->is_hidden
                    ? 'M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Zm9.5 2.6a2.6 2.6 0 1 0 0-5.2 2.6 2.6 0 0 0 0 5.2Z'
                    : 'M4 4l16 16M9.9 5.8A8.4 8.4 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a15 15 0 0 1-3.3 4M6.6 7.9A15 15 0 0 0 2.5 12S6 18.5 12 18.5c1 0 1.9-.2 2.7-.5',
            ],
        ];
    @endphp

    <div x-show="$store.huvUi.editMode" x-cloak
         x-data="{ open: false }"
         @keydown.escape.window="open = false"
         @click.outside="open = false"
         {{ $attributes->class('relative shrink-0') }}>

        <button type="button" @click="open = ! open"
                :aria-expanded="open ? 'true' : 'false'"
                aria-haspopup="true"
                class="flex size-7 items-center justify-center rounded-full border-0 bg-transparent
                       text-link hover:bg-tint">
            <svg class="size-[14px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </svg>
            <span class="sr-only">Acciones del documento «{{ Str::limit($document->title, 60) }}»</span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 -translate-y-1"
             class="absolute top-full right-0 z-40 mt-1 w-[204px] overflow-hidden rounded-[3px]
                    border border-line bg-card py-1 text-left
                    shadow-[0_10px_28px_rgba(23,32,64,0.22)]">
            <ul>
                <li>
                    {{-- Editar abre el editor en el propio listado del tema. --}}
                    <a href="{{ route('topics.show', $topic) }}?editar={{ $document->id }}#huv-editor-documento"
                       class="flex items-center gap-[10px] px-4 py-[9px] text-13-5 text-ink no-underline
                              hover:bg-tint hover:text-heading hover:no-underline">
                        <svg class="size-4 shrink-0 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                        </svg>
                        Editar
                    </a>
                </li>

                @foreach ($actions as $action)
                    <li>
                        <form method="POST" action="{{ $action['route'] }}">
                            @csrf
                            <button type="submit" @disabled($action['disabled'] ?? false)
                                    class="flex w-full items-center gap-[10px] border-0 bg-transparent px-4 py-[9px]
                                           text-left text-13-5 text-ink hover:bg-tint hover:text-heading
                                           disabled:cursor-not-allowed disabled:text-faint disabled:hover:bg-transparent">
                                <svg class="size-4 shrink-0 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="{{ $action['icon'] }}" />
                                </svg>
                                {{ $action['label'] }}
                            </button>
                        </form>
                    </li>
                @endforeach

                <li class="mt-1 border-t border-line pt-1">
                    <form method="POST" action="{{ route('admin.documents.destroy', [$topic, $document]) }}"
                          onsubmit="return confirm('¿Eliminar «{{ Str::limit(addslashes($document->title), 60) }}»? La acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="flex w-full items-center gap-[10px] border-0 bg-transparent px-4 py-[9px]
                                       text-left text-13-5 text-[#8c1d18] hover:bg-[#fdf3f2]">
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13" />
                            </svg>
                            Eliminar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
@endauth
