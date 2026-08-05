/**
 * Rail de accesibilidad: alto contraste y escalado real del texto.
 *
 * Alto contraste
 * --------------
 * Solo se conmuta el atributo `data-huv-contrast` en <html>; toda la paleta
 * vive en resources/css/app.css como tokens semánticos. No se usa
 * `filter: invert()`: además de degradar las fotografías, un elemento con
 * `filter` distinto de `none` pasa a ser bloque contenedor de sus
 * descendientes `position: fixed`, lo que rompía el anclaje de este mismo rail
 * y del cajón de navegación móvil.
 *
 * Tamaño de texto
 * ---------------
 * Se aplica sobre <html> en px: como toda la tipografía del sitio está
 * expresada en rem, el escalado afecta a todo el contenido (a diferencia de
 * fijar font-size en un contenedor cuyos hijos están en px).
 *
 * Ambas preferencias se guardan en localStorage y se reaplican en la siguiente
 * visita (ver el script previo al primer pintado en layouts/app.blade.php).
 */

const STORAGE_KEY = 'huv:a11y';
const BASE_FONT_PX = 16;
const STEP_PX = 2;
const MIN_STEP = -1;
const MAX_STEP = 3;
const THEME_COLOR = { on: '#000000', off: '#2b3b80' };

function readPreferences() {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;

        const parsed = JSON.parse(raw);

        return {
            contrast: parsed.contrast === true,
            fontStep: clampStep(Number(parsed.fontStep) || 0),
        };
    } catch {
        return null;
    }
}

function clampStep(step) {
    return Math.min(MAX_STEP, Math.max(MIN_STEP, step));
}

export default function huvA11y() {
    return {
        contrast: false,
        fontStep: 0,

        init() {
            const stored = readPreferences();

            if (stored) {
                this.contrast = stored.contrast;
                this.fontStep = stored.fontStep;
            }

            this.apply();
        },

        get fontLabel() {
            return `${BASE_FONT_PX + this.fontStep * STEP_PX} px`;
        },

        apply() {
            const root = document.documentElement;

            root.dataset.huvContrast = this.contrast ? 'on' : 'off';
            root.style.fontSize =
                this.fontStep === 0 ? '' : `${BASE_FONT_PX + this.fontStep * STEP_PX}px`;

            // La barra del navegador en móvil acompaña al tema activo.
            document
                .querySelector('meta[name="theme-color"]')
                ?.setAttribute('content', this.contrast ? THEME_COLOR.on : THEME_COLOR.off);

            try {
                window.localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify({ contrast: this.contrast, fontStep: this.fontStep })
                );
            } catch {
                /* Modo privado o almacenamiento lleno: no es crítico. */
            }
        },

        toggleContrast() {
            this.contrast = !this.contrast;
            this.apply();
        },

        biggerFont() {
            this.fontStep = clampStep(this.fontStep + 1);
            this.apply();
        },

        smallerFont() {
            this.fontStep = clampStep(this.fontStep - 1);
            this.apply();
        },

        reset() {
            this.contrast = false;
            this.fontStep = 0;
            this.apply();
        },
    };
}
