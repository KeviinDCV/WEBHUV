/**
 * Carrusel de logotipos de entidades.
 *
 * Se apoya en el desplazamiento nativo con `scroll-snap` en lugar de mover un
 * `transform`: así funciona con rueda, gesto táctil y teclado sin código extra,
 * y sigue siendo utilizable si el JavaScript falla. Los botones solo empujan el
 * scroll y se desactivan al llegar a cada extremo.
 */
export default function huvLogoStrip() {
    return {
        atStart: true,
        atEnd: false,

        init() {
            this.sync();

            this.$refs.strip.addEventListener('scroll', () => this.sync(), { passive: true });
            window.addEventListener('resize', () => this.sync(), { passive: true });
        },

        sync() {
            const el = this.$refs.strip;
            if (!el) return;

            // 2px de holgura: el scroll fraccionario de algunos navegadores
            // nunca alcanza el máximo exacto y el botón quedaría activo.
            this.atStart = el.scrollLeft <= 2;
            this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 2;
        },

        scrollBy(direction) {
            const el = this.$refs.strip;
            if (!el) return;

            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            el.scrollBy({
                left: direction * Math.max(240, el.clientWidth * 0.8),
                behavior: reduced ? 'auto' : 'smooth',
            });
        },
    };
}
