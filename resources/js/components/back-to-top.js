/**
 * Botón «Volver arriba».
 *
 * Aparece al bajar y devuelve al inicio de la página. Detalles que importan:
 *  - El listener de scroll es pasivo y se limita con requestAnimationFrame,
 *    para no penalizar el rendimiento del desplazamiento.
 *  - Respeta `prefers-reduced-motion`: sin animación de desplazamiento.
 *  - Tras subir, el foco se mueve al enlace del logotipo. Sin eso, quien navega
 *    con teclado seguiría con el foco en un botón ya oculto y la siguiente
 *    tabulación continuaría desde el pie de página.
 */

const SHOW_AFTER_PX = 400;

export default function huvBackToTop() {
    return {
        visible: false,
        ticking: false,

        init() {
            this.onScroll();
            window.addEventListener('scroll', () => this.schedule(), { passive: true });
        },

        schedule() {
            if (this.ticking) return;

            this.ticking = true;
            window.requestAnimationFrame(() => {
                this.onScroll();
                this.ticking = false;
            });
        },

        onScroll() {
            this.visible = window.scrollY > SHOW_AFTER_PX;
        },

        toTop() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });

            document.getElementById('huv-inicio-pagina')?.focus({ preventScroll: true });
        },
    };
}
