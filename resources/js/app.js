import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import huvA11y from './components/a11y';
import huvBackToTop from './components/back-to-top';
import huvBannerForm from './components/banner-form';
import huvBannerOrder from './components/banner-order';
import huvCarousel from './components/carousel';
import huvClock from './components/clock';
import huvContentFeed from './components/content-feed';
import huvLogoStrip from './components/logo-strip';
import huvNav from './components/nav';

window.Alpine = Alpine;

Alpine.plugin(collapse);

/**
 * Estado de interfaz compartido entre componentes.
 *
 * `navOpen` permite que el rail de accesibilidad y el botón «Volver arriba» se
 * retiren mientras el cajón de navegación móvil está abierto: ese cajón es un
 * diálogo `aria-modal`, y un control flotante por encima lo taparía y seguiría
 * siendo tabulable pese a estar excluido del árbol de accesibilidad.
 *
 * `editMode` muestra los controles de edición de cada sección. Se recuerda
 * entre páginas para no tener que reactivarlo en cada navegación. Es solo una
 * preferencia visual: quien decide si esos controles existen es el servidor,
 * según haya sesión iniciada o no.
 */
Alpine.store('huvUi', {
    navOpen: false,
    editMode: false,

    init() {
        try {
            this.editMode = window.localStorage.getItem('huv:editMode') === '1';
        } catch {
            /* Modo privado: se queda desactivado, sin más. */
        }
    },

    toggleEdit() {
        this.editMode = !this.editMode;

        try {
            window.localStorage.setItem('huv:editMode', this.editMode ? '1' : '0');
        } catch {
            /* No es crítico que la preferencia no persista. */
        }
    },
});

Alpine.data('huvA11y', huvA11y);
Alpine.data('huvBackToTop', huvBackToTop);
Alpine.data('huvBannerForm', huvBannerForm);
Alpine.data('huvBannerOrder', huvBannerOrder);
Alpine.data('huvCarousel', huvCarousel);
Alpine.data('huvClock', huvClock);
Alpine.data('huvContentFeed', huvContentFeed);
Alpine.data('huvLogoStrip', huvLogoStrip);
Alpine.data('huvNav', huvNav);

Alpine.start();
