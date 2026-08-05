/**
 * Menú principal: desplegables de escritorio, megamenú y cajón móvil.
 *
 * Mejoras sobre el diseño original:
 *  - `Escape` cierra y devuelve el foco al disparador.
 *  - Se cierra al hacer clic fuera o al salir con el tabulador.
 *  - En escritorio abre al pasar el ratón con un pequeño retardo de cierre,
 *    para que no desaparezca al cruzar el hueco hacia el submenú.
 *  - En móvil (o con puntero grueso) el hover se desactiva: solo clic.
 *  - Bloquea el desplazamiento del fondo mientras el cajón móvil está abierto.
 */

const CLOSE_DELAY_MS = 180;

export default function huvNav() {
    return {
        menu: null,
        mobileOpen: false,
        mobileSection: null,
        closeTimer: null,

        init() {
            this.$watch('mobileOpen', (open) => {
                document.body.style.overflow = open ? 'hidden' : '';
                // El rail de accesibilidad se oculta mientras el cajón —un
                // diálogo aria-modal— está abierto, para no taparlo ni quedar
                // tabulable fuera de él.
                this.$store.huvUi.navOpen = open;
            });
        },

        destroy() {
            document.body.style.overflow = '';
            this.$store.huvUi.navOpen = false;
            this.cancelClose();
        },

        get supportsHover() {
            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },

        isOpen(name) {
            return this.menu === name;
        },

        cancelClose() {
            if (this.closeTimer) {
                window.clearTimeout(this.closeTimer);
                this.closeTimer = null;
            }
        },

        open(name) {
            this.cancelClose();
            this.menu = name;
        },

        close() {
            this.cancelClose();
            this.menu = null;
        },

        toggle(name) {
            this.menu = this.menu === name ? null : name;
        },

        /** Apertura por hover, solo en dispositivos con puntero fino. */
        hoverOpen(name) {
            if (!this.supportsHover) return;
            this.open(name);
        },

        /** Cierre diferido para tolerar el recorrido del cursor hacia el submenú. */
        hoverClose() {
            if (!this.supportsHover) return;

            this.cancelClose();
            this.closeTimer = window.setTimeout(() => {
                this.menu = null;
                this.closeTimer = null;
            }, CLOSE_DELAY_MS);
        },

        /** `Escape`: cierra el nivel abierto y devuelve el foco al botón. */
        onEscape(event) {
            if (this.mobileOpen) {
                this.mobileOpen = false;
                this.mobileSection = null;
                this.$refs.mobileTrigger?.focus();
                return;
            }

            if (this.menu === null) return;

            const trigger = event.currentTarget?.querySelector(
                `[data-huv-trigger="${this.menu}"]`
            );

            this.close();
            trigger?.focus();
        },

        /** Cierra si el foco sale por completo del bloque de navegación. */
        onFocusOut(event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.close();
            }
        },

        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
            if (!this.mobileOpen) this.mobileSection = null;
        },

        toggleMobileSection(name) {
            this.mobileSection = this.mobileSection === name ? null : name;
        },
    };
}
