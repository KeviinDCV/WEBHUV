import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import huvA11y from './components/a11y';
import huvBackToTop from './components/back-to-top';
import huvCarousel from './components/carousel';
import huvClock from './components/clock';
import huvNav from './components/nav';

window.Alpine = Alpine;

Alpine.plugin(collapse);

/**
 * Estado de interfaz compartido entre componentes.
 *
 * `navOpen` permite que el rail de accesibilidad se retire mientras el cajón
 * de navegación móvil está abierto: el cajón es un diálogo `aria-modal`, y un
 * control flotante por encima lo taparía y seguiría siendo tabulable pese a
 * estar excluido del árbol de accesibilidad.
 */
Alpine.store('huvUi', { navOpen: false });

Alpine.data('huvA11y', huvA11y);
Alpine.data('huvBackToTop', huvBackToTop);
Alpine.data('huvCarousel', huvCarousel);
Alpine.data('huvClock', huvClock);
Alpine.data('huvNav', huvNav);

Alpine.start();
