{{--
    Aviso de salida del portal.

    Se muestra al pulsar un enlace marcado con `data-huv-confirm-exit` que
    lleva a otro dominio. Vive una sola vez en el documento y escucha los
    clics; ver resources/js/components/leave-site.js.
--}}
<div x-data="huvLeaveSite" x-cloak class="print:hidden">

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-70 flex items-center justify-center bg-overlay px-5"
         @click.self="close()"
         @keydown.escape.window="close()">

        <div role="alertdialog"
             aria-modal="true"
             aria-labelledby="huv-salida-titulo"
             aria-describedby="huv-salida-texto"
             x-trap.noscroll="open"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-[430px] rounded-[10px] bg-card px-8 pt-12 pb-9 text-center
                    shadow-[0_20px_50px_rgba(23,32,64,0.3)]">

            <button type="button" @click="close()" aria-label="{{ __('estructura.salida.cerrar') }}"
                    class="absolute top-4 right-4 flex size-8 items-center justify-center rounded-full
                           border-0 bg-transparent text-heading hover:bg-tint">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M5 5l14 14M19 5L5 19" />
                </svg>
            </button>

            {{-- Decorativo: el mensaje ya dice «Atención». --}}
            <span aria-hidden="true"
                  class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-warning">
                <svg class="size-8 text-on-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.6" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 7v6.5" />
                    <path d="M12 17h.01" />
                </svg>
            </span>

            <h2 id="huv-salida-titulo"
                class="m-0 mb-3 font-display text-24 font-bold text-warning-ink">
                {{ __('estructura.salida.titulo') }}
            </h2>

            <p id="huv-salida-texto" class="m-0 mb-7 text-14-5 leading-[1.6] text-body">
                {{ __('estructura.salida.texto') }}
                <strong class="block font-semibold break-all text-heading" x-text="host"></strong>
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <button type="button" @click="accept()"
                        class="rounded-full border-0 bg-azure px-7 py-[10px] font-display text-14
                               font-semibold text-on-accent transition-colors hover:bg-azure-dark">
                    {{ __('estructura.salida.aceptar') }}
                </button>
                <button type="button" @click="close()"
                        class="rounded-full border border-rule-accent bg-card px-7 py-[10px] font-display
                               text-14 font-semibold text-link transition-colors hover:bg-tint">
                    {{ __('estructura.salida.cancelar') }}
                </button>
            </div>
        </div>
    </div>
</div>
