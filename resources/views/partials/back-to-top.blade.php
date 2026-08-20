{{-- Se oculta mientras el cajón de navegación móvil está abierto: ese cajón es
     un diálogo aria-modal y nada debe flotar por encima de él. --}}
<button x-data="huvBackToTop"
        x-show="visible && ! $store.huvUi.navOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0 translate-y-2"
        type="button"
        @click="toTop()"
        class="fixed right-4 bottom-5 z-50 inline-flex items-center gap-2 rounded-full border-0 bg-navy
               py-3 pr-5 pl-4 font-display text-13-5 font-semibold text-on-brand
               shadow-[0_6px_20px_rgba(23,32,64,0.28)] transition-colors hover:bg-navy-dark
               lg:right-6 lg:bottom-7 print:hidden">
    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5" />
        <path d="m5 12 7-7 7 7" />
    </svg>
    {{ __('estructura.volver_arriba.rotulo') }}
</button>
