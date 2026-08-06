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

export default function huvNav(megaTabs = []) {
    return {
        menu: null,
        mobileOpen: false,
        mobileSection: null,
        closeTimer: null,

        /** Categoría abierta dentro del menú completo (botón ☰). */
        megaTabs,
        megaTab: megaTabs[0] ?? null,

        init() {
            this.$watch('mobileOpen', (open) => {
                // El rail de accesibilidad se oculta mientras el cajón —un
                // diálogo aria-modal— está abierto, para no taparlo ni quedar
                // tabulable fuera de él. El bloqueo del desplazamiento y la
                // retención del foco los aporta `x-trap.noscroll` en la
                // plantilla.
                this.$store.huvUi.navOpen = open;
            });
        },

        destroy() {
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

        /* ------------------------------------------------------------------
         | Menú completo: lista de categorías a la izquierda, enlaces al lado.
         | Patrón de pestañas verticales de WAI-ARIA, con foco móvil: solo la
         | categoría activa entra en el orden de tabulación y las flechas
         | recorren el resto.
         ------------------------------------------------------------------ */

        isTab(key) {
            return this.megaTab === key;
        },

        selectTab(key) {
            this.megaTab = key;
        },

        onTabKeydown(event, key) {
            const keys = this.megaTabs;
            const current = keys.indexOf(key);
            let next = null;

            if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
                next = (current + 1) % keys.length;
            } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
                next = (current - 1 + keys.length) % keys.length;
            } else if (event.key === 'Home') {
                next = 0;
            } else if (event.key === 'End') {
                next = keys.length - 1;
            }

            if (next === null) return;

            event.preventDefault();
            this.megaTab = keys[next];
            this.$nextTick(() => {
                event.currentTarget
                    ?.closest('[data-huv-tablist]')
                    ?.querySelector(`[data-huv-tab="${this.megaTab}"]`)
                    ?.focus();
            });
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
