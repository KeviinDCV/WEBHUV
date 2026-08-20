@props([
    'section',
    'label',
    /** Destino de la administración. Sin él, el control se anuncia como pendiente. */
    'url' => null,
    /** Ancla el control sobre la sección en lugar de reservarle su propia fila. */
    'floating' => false,
])

@auth
    <div x-show="$store.huvUi.editMode" x-cloak
         class="flex justify-end {{ $floating ? 'absolute top-3 right-3 z-30' : 'mb-4' }}">

        @if ($url)
            <a href="{{ $url }}"
               data-huv-edit="{{ $section }}"
               class="inline-flex items-center gap-[6px] rounded-full border border-rule-accent bg-card
                      px-4 py-[6px] text-12-5 font-semibold text-link no-underline shadow-sm
                      transition-colors hover:bg-tint hover:no-underline">
                <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                </svg>
                {{-- Sobre la propia sección basta con «Editar»: el contexto ya
                     lo da el sitio donde está. Para lectores de pantalla, que no
                     tienen ese contexto, se nombra la sección. --}}
                @if ($floating)
                    {{ __('componentes.editar.rotulo') }}<span class="sr-only"> {{ $label }}</span>
                @else
                    {{ __('componentes.editar.seccion', ['seccion' => $label]) }}
                @endif
            </a>
        @else
            {{-- Todavía no hay administrador para esta sección: el control se
                 anuncia como pendiente en lugar de fingir que funciona. Se marca
                 con `aria-disabled` en vez de `disabled` para que siga siendo
                 enfocable y los lectores de pantalla expliquen por qué no actúa. --}}
            <button type="button"
                    data-huv-edit="{{ $section }}"
                    aria-disabled="true"
                    @click.prevent
                    title="{{ __('componentes.editar.pendiente_titulo') }}"
                    class="inline-flex cursor-not-allowed items-center gap-[6px] rounded-full border border-dashed
                           border-stroke-strong bg-card px-3 py-[5px] text-12 font-semibold text-muted">
                <svg class="size-[13px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                </svg>
                {{ __('componentes.editar.seccion', ['seccion' => $label]) }}
                <span class="sr-only">{{ __('componentes.editar.pendiente_nota') }}</span>
            </button>
        @endif
    </div>
@endauth
