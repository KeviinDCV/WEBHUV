/**
 * Aviso de salida del portal.
 *
 * Intercepta los enlaces marcados con `data-huv-confirm-exit` que apuntan a
 * otro dominio y pide confirmación antes de abandonar el sitio. Es la conducta
 * habitual en portales públicos: quien pulsa un banner no siempre sabe que va
 * a salir de la web del hospital.
 *
 * Se respeta la intención del usuario: si abre el enlace en otra pestaña
 * (Ctrl/⌘, botón central o Mayús) no se interpone nada, porque no está
 * abandonando la página que está viendo.
 */
export default function huvLeaveSite() {
    return {
        open: false,
        url: '',
        newTab: false,
        host: '',
        trigger: null,

        init() {
            document.addEventListener('click', (event) => this.intercept(event));
        },

        intercept(event) {
            const link = event.target.closest?.('a[data-huv-confirm-exit]');
            if (!link) return;

            // Si es QUIEN NAVEGA el que decide abrirlo aparte, no se le
            // interrumpe: ya sabe lo que hace y no está abandonando la página.
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return;

            // Antes se descartaba también cualquier enlace con target="_blank",
            // y eso dejaba el aviso inerte: TODOS los enlaces externos del
            // portal abren en pestaña nueva, así que no se mostraba nunca. Que
            // el destino se abra aparte no cambia lo que hay que avisar —que se
            // va a un sitio ajeno al hospital—; lo único que cambia es dónde se
            // abre después de aceptar, y de eso se encarga accept().

            if (this.isSameSite(link.href)) return;

            event.preventDefault();
            this.show(link);
        },

        isSameSite(href) {
            try {
                return new URL(href, window.location.href).hostname === window.location.hostname;
            } catch {
                // Ante una dirección que no se puede interpretar, no se
                // interrumpe: es preferible dejar pasar que bloquear.
                return true;
            }
        },

        show(link) {
            this.url = link.href;
            this.newTab = link.target === '_blank';
            this.host = new URL(link.href, window.location.href).hostname;
            this.trigger = link;

            // El bloqueo del desplazamiento y la retención del foco los aporta
            // `x-trap.noscroll` en la plantilla.
            this.open = true;
        },

        close() {
            if (!this.open) return;

            this.open = false;

            // El foco vuelve a donde estaba: si no, quien navega con teclado
            // aparecería al principio del documento.
            this.trigger?.focus();
            this.trigger = null;
        },

        accept() {
            const destination = this.url;
            const aparte = this.newTab;

            this.close();

            // Se respeta lo que decía el enlace. Y `window.open` funciona aquí
            // porque esto ocurre dentro del clic de «Aceptar», que para el
            // navegador es un gesto de la persona: llamado fuera de un gesto,
            // el bloqueador de ventanas lo impediría.
            if (aparte) {
                window.open(destination, '_blank', 'noopener');

                return;
            }

            window.location.href = destination;
        },
    };
}
