{{--
    Barra flotante de accesibilidad.

    El modo de alto contraste es un tema de color real (ver resources/css/app.css),
    no un `filter: invert()`. Eso importa para este componente: un ancestro con
    `filter` distinto de `none` se convierte en bloque contenedor de sus
    descendientes `position: fixed`, y el rail dejaba de seguir el scroll al
    activar el contraste.

    Las preferencias persisten en localStorage (resources/js/components/a11y.js).
--}}
<div x-data="huvA11y"
     x-show="! $store.huvUi.navOpen"
     role="toolbar"
     aria-label="Herramientas de accesibilidad"
     aria-orientation="vertical"
     class="fixed top-[42%] right-0 z-60 flex flex-col overflow-hidden rounded-l-[4px] bg-azure
            shadow-[-3px_0_14px_rgba(23,32,64,0.22)] print:hidden">

    <button type="button" @click="toggleContrast()"
            :aria-pressed="contrast ? 'true' : 'false'"
            aria-label="Alternar alto contraste"
            title="Alto contraste"
            class="flex size-10 items-center justify-center border-0 border-b border-on-accent/30 bg-transparent
                   text-on-accent hover:bg-azure-dark">
        <svg class="size-[19px]" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" />
            <path d="M12 3a9 9 0 0 0 0 18Z" fill="currentColor" />
        </svg>
    </button>

    <button type="button" @click="biggerFont()"
            aria-label="Aumentar tamaño del texto"
            :title="'Aumentar texto (actual: ' + fontLabel + ')'"
            class="flex size-10 items-center justify-center border-0 border-b border-on-accent/30 bg-transparent
                   text-14 font-bold text-on-accent hover:bg-azure-dark">
        A+
    </button>

    <button type="button" @click="smallerFont()"
            aria-label="Reducir tamaño del texto"
            :title="'Reducir texto (actual: ' + fontLabel + ')'"
            class="flex size-10 items-center justify-center border-0 border-b border-on-accent/30 bg-transparent
                   text-12 font-bold text-on-accent hover:bg-azure-dark">
        A−
    </button>

    <button type="button" @click="reset()"
            aria-label="Restablecer preferencias de accesibilidad"
            title="Restablecer"
            class="flex size-10 items-center justify-center border-0 border-b border-on-accent/30 bg-transparent
                   text-on-accent hover:bg-azure-dark">
        <svg class="size-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 12a9 9 0 1 0 3-6.7" />
            <path d="M3 4v5h5" />
        </svg>
    </button>

    <a href="#" aria-label="Centro de relevo — lengua de señas colombiana"
       title="Lengua de señas colombiana"
       class="flex size-10 items-center justify-center text-10-5 font-bold text-on-accent no-underline
              hover:bg-azure-dark hover:text-on-accent hover:no-underline">
        LSC
    </a>

    {{-- Anuncio para lectores de pantalla del estado actual. --}}
    <span class="sr-only" aria-live="polite"
          x-text="'Alto contraste ' + (contrast ? 'activado' : 'desactivado') + '. Tamaño de texto ' + fontLabel + '.'"></span>
</div>
