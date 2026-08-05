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
 * `editMode` muestra los controles de edición de cada sección. Viene activado:
 * si hay sesión iniciada es porque se entró a administrar, y obligar a pulsar
 * un interruptor para ver los botones solo esconde la función. El interruptor
 * queda para lo contrario —ver la portada como la ve un visitante— y su estado
 * se recuerda entre páginas.
 *
 * Es solo una preferencia visual: quien decide si esos controles existen es el
 * servidor, según haya sesión iniciada o no.
 */
Alpine.store('huvUi', {
    navOpen: false,
    editMode: true,

    init() {
        try {
            const stored = window.localStorage.getItem('huv:editMode');

            // Sin preferencia guardada se muestran; solo se ocultan si alguien
            // lo pidió expresamente.
            this.editMode = stored === null ? true : stored === '1';
        } catch {
            /* Modo privado: se quedan visibles, que es el valor por defecto. */
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
