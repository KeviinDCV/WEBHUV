/**
 * Muro de contenidos: pestañas, filtros, vista y «cargar más».
 *
 * Las tarjetas se renderizan enteras en el servidor —bien para buscadores y
 * para quien navega sin JavaScript— y este componente solo decide cuáles se
 * muestran y en qué posición, mediante `x-show` y la propiedad CSS `order`.
 * Sin JavaScript el listado sigue siendo legible: aparece completo y ordenado
 * de más reciente a más antiguo.
 */

import { acomodar, vigilar } from '../lib/masonry';

const VIEW_KEY = 'huv:feedView';

export default function huvContentFeed({
    meta = [],
    perPage = 6,
    canModerate = false,
    openEditor = false,
    // Los rótulos se traducen en el servidor y viajan hasta aquí: escritos en
    // este fichero se quedarían en español en la web en inglés, que es lo que
    // ya pasó dos veces.
    tabs = [],
    moderationTabs = [],
} = {}) {
    return {
        meta,
        perPage,
        canModerate,
        publicTabs: tabs,
        moderationTabs,

        /** Editor incrustado bajo la barra de controles. */
        editor: openEditor,

        tab: 'recientes',
        period: 'todos',
        category: 'todos',
        view: 'grid',
        shown: perPage,

        init() {
            try {
                const stored = window.localStorage.getItem(VIEW_KEY);
                if (stored === 'grid' || stored === 'list') this.view = stored;
            } catch {
                /* Modo privado: se queda en rejilla. */
            }

            // Cualquier cambio de criterio reinicia la paginación: mantener el
            // «cargar más» anterior mostraría un recuento incoherente.
            ['tab', 'period', 'category'].forEach((prop) =>
                this.$watch(prop, () => (this.shown = this.perPage))
            );

            // Mampostería: que la tarjeta de abajo suba al hueco que deja la
            // corta, como en el portal actual.
            this.$nextTick(() => {
                this.dejarDeVigilar = vigilar(this.$refs.rejilla);
            });

            // Cambiar de vista, de pestaña o de filtro no cambia el alto de
            // ninguna tarjeta, así que el observador de tamaño no se entera:
            // hay que recolocar a mano.
            ['view', 'tab', 'period', 'category', 'shown'].forEach((prop) =>
                this.$watch(prop, () => this.$nextTick(() => acomodar(this.$refs.rejilla)))
            );
        },

        destroy() {
            this.dejarDeVigilar?.();
        },

        setView(view) {
            this.view = view;

            try {
                window.localStorage.setItem(VIEW_KEY, view);
            } catch {
                /* No es crítico que la preferencia no persista. */
            }
        },

        /** Pestañas disponibles; las de moderación solo con sesión iniciada. */
        get tabs() {
            const tabs = [...this.publicTabs];

            if (!this.canModerate) return tabs;

            const de = (key) => this.moderationTabs.find((tab) => tab.key === key);

            // «Inactivos» va entre las dos públicas y «Ocultos» al final, que
            // es el orden en el que se revisa: primero lo que no se publica,
            // al final lo que se publica pero no sale en la portada.
            const inactivos = de('inactivos');
            const ocultos = de('ocultos');

            if (inactivos) tabs.splice(1, 0, inactivos);
            if (ocultos) tabs.push(ocultos);

            return tabs;
        },

        matchesTab(item) {
            if (this.tab === 'inactivos') return !item.isActive;
            if (this.tab === 'destacados') return item.isFeatured;
            if (this.tab === 'ocultos') return item.isHidden;

            return true;
        },

        get matching() {
            const now = Date.now();
            const days = this.period === 'todos' ? null : Number(this.period);

            return this.meta
                .filter((item) => this.matchesTab(item))
                .filter((item) => this.category === 'todos' || item.category === this.category)
                .filter((item) => days === null || now - item.timestamp <= days * 86400000)
                .sort((a, b) => b.timestamp - a.timestamp);
        },

        get visibleIds() {
            return this.matching.slice(0, this.shown).map((item) => item.id);
        },

        get total() {
            return this.matching.length;
        },

        get showing() {
            return Math.min(this.shown, this.total);
        },

        get hasMore() {
            return this.shown < this.total;
        },

        get isEmpty() {
            return this.total === 0;
        },

        isVisible(id) {
            return this.visibleIds.includes(id);
        },

        /** Posición dentro del listado visible, para reordenar sin tocar el DOM. */
        positionOf(id) {
            const index = this.visibleIds.indexOf(id);

            return index === -1 ? 0 : index + 1;
        },

        loadMore() {
            this.shown += this.perPage;
        },

        reset() {
            this.tab = 'recientes';
            this.period = 'todos';
            this.category = 'todos';
            this.shown = this.perPage;
        },
    };
}
