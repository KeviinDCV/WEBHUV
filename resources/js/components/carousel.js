/**
 * Carrusel del banner principal.
 *
 * Mejoras sobre el diseño original:
 *  - Se detiene al pasar el ratón o al enfocar cualquier elemento interior
 *    (requisito WCAG 2.2.2 «Pausar, detener, ocultar»).
 *  - Respeta `prefers-reduced-motion`: no arranca solo ni anima el desplazamiento.
 *  - Navegación con flechas del teclado y gesto de deslizamiento táctil.
 *  - Se pausa cuando la pestaña deja de estar visible (ahorra batería/CPU).
 *  - Las diapositivas ocultas quedan fuera del orden de tabulación.
 */

const SWIPE_THRESHOLD_PX = 45;

export default function huvCarousel({ count = 3, autoplay = true, seconds = 7, textos = {} } = {}) {
    return {
        textos,
        count,
        slide: 0,
        playing: autoplay,
        hovering: false,
        focused: false,
        reducedMotion: false,
        timer: null,
        touchStartX: null,

        init() {
            const query = window.matchMedia('(prefers-reduced-motion: reduce)');

            this.reducedMotion = query.matches;
            if (this.reducedMotion) this.playing = false;

            query.addEventListener('change', (event) => {
                this.reducedMotion = event.matches;
                if (this.reducedMotion) this.playing = false;
                this.arm();
            });

            document.addEventListener('visibilitychange', () => this.arm());

            this.$watch('playing', () => this.arm());
            this.$watch('hovering', () => this.arm());
            this.$watch('focused', () => this.arm());

            this.arm();
        },

        destroy() {
            this.stop();
        },

        get running() {
            return this.playing && !this.hovering && !this.focused && !document.hidden;
        },

        get trackStyle() {
            return {
                width: `${this.count * 100}%`,
                transform: `translateX(-${(this.slide * 100) / this.count}%)`,
                transition: this.reducedMotion
                    ? 'none'
                    : 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)',
            };
        },

        // Los rotulos llegan traducidos desde la vista: aqui no hay forma de
        // saber en que idioma esta la pagina, y escribirlos en el JS los dejaba
        // en espanol pisando lo que ya traducia el Blade.
        get playLabel() {
            return this.playing ? this.textos.detener : this.textos.reproducir;
        },

        get statusLabel() {
            return this.textos.posicion
                .replace(':posicion', this.slide + 1)
                .replace(':total', this.count);
        },

        isActive(index) {
            return index === this.slide;
        },

        stop() {
            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },

        arm() {
            this.stop();
            if (!this.running) return;

            this.timer = window.setInterval(() => this.go(this.slide + 1), seconds * 1000);
        },

        go(index) {
            this.slide = (index + this.count) % this.count;
            this.arm();
        },

        next() {
            this.go(this.slide + 1);
        },

        prev() {
            this.go(this.slide - 1);
        },

        togglePlay() {
            this.playing = !this.playing;
        },

        onKeydown(event) {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.prev();
            }
        },

        onTouchStart(event) {
            this.touchStartX = event.changedTouches[0].clientX;
        },

        onTouchEnd(event) {
            if (this.touchStartX === null) return;

            const delta = event.changedTouches[0].clientX - this.touchStartX;

            if (Math.abs(delta) > SWIPE_THRESHOLD_PX) {
                delta < 0 ? this.next() : this.prev();
            }

            this.touchStartX = null;
        },
    };
}
