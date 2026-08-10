/**
 * Listado de documentos de un tema: categorías, búsqueda, orden y vista.
 *
 * Mismo planteamiento que el muro de contenidos: las fichas se imprimen enteras
 * en el servidor y aquí solo se decide cuáles se ven y en qué posición, con
 * `x-show` y la propiedad CSS `order`. Sin JavaScript el tema sigue siendo
 * navegable: aparecen todos los documentos, de más reciente a más antiguo.
 */

const VIEW_KEY = 'huv:topicView';

/** Categorías visibles antes de pulsar «Ver más». */
const CATEGORIES_SHOWN = 5;

export default function huvTopicDocuments({
    meta = [],
    categories = [],
    perPage = 20,
    canModerate = false,
    openEditor = false,
} = {}) {
    return {
        meta,
        categories,
        perPage,
        canModerate,

        /** Editor incrustado, encima del listado. */
        editor: openEditor,

        category: 'todas',
        search: '',
        tab: 'recientes',
        period: 'todos',
        view: 'grid',
        shown: perPage,
        allCategories: false,

        init() {
            try {
                const stored = window.localStorage.getItem(VIEW_KEY);
                if (stored === 'grid' || stored === 'list') this.view = stored;
            } catch {
                /* Modo privado: se queda en rejilla. */
            }

            ['tab', 'period', 'category', 'search'].forEach((prop) =>
                this.$watch(prop, () => (this.shown = this.perPage))
            );
        },

        setView(view) {
            this.view = view;

            try {
                window.localStorage.setItem(VIEW_KEY, view);
            } catch {
                /* No es crítico que la preferencia no persista. */
            }
        },

        /* ---------------- Categorías ---------------- */

        get visibleCategories() {
            return this.allCategories ? this.categories : this.categories.slice(0, CATEGORIES_SHOWN);
        },

        get hasMoreCategories() {
            return this.categories.length > CATEGORIES_SHOWN;
        },

        /* ---------------- Orden y filtros ---------------- */

        get tabs() {
            const tabs = [
                { key: 'recientes', label: 'Recientes' },
                { key: 'az', label: 'A-Z' },
                { key: 'expedicion', label: 'Fecha de expedición' },
            ];

            if (this.canModerate) {
                tabs.push(
                    { key: 'inactivos', label: 'Inactivos' },
                    { key: 'destacados', label: 'Destacados' },
                    { key: 'ocultos', label: 'Ocultos' }
                );
            }

            return tabs;
        },

        matchesTab(item) {
            if (this.tab === 'inactivos') return !item.isActive;
            if (this.tab === 'destacados') return item.isFeatured;
            if (this.tab === 'ocultos') return item.isHidden;

            return true;
        },

        /**
         * Sin acentos y en minúsculas: quien busca «ejecucion» debe encontrar
         * «Ejecución».
         */
        normalize(text) {
            return text
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        },

        get comparator() {
            if (this.tab === 'az') {
                return (a, b) => a.title.localeCompare(b.title, 'es');
            }

            if (this.tab === 'expedicion') {
                return (a, b) => b.issued - a.issued;
            }

            return (a, b) => b.timestamp - a.timestamp;
        },

        get matching() {
            const now = Date.now();
            const days = this.period === 'todos' ? null : Number(this.period);
            const term = this.normalize(this.search.trim());

            return this.meta
                .filter((item) => this.matchesTab(item))
                .filter((item) => this.category === 'todas' || item.category === this.category)
                .filter((item) => days === null || now - item.timestamp <= days * 86400000)
                .filter((item) => term === '' || this.normalize(item.title).includes(term))
                .sort(this.comparator);
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

        positionOf(id) {
            const index = this.visibleIds.indexOf(id);

            return index === -1 ? 0 : index + 1;
        },

        loadMore() {
            this.shown += this.perPage;
        },

        reset() {
            this.category = 'todas';
            this.search = '';
            this.tab = 'recientes';
            this.period = 'todos';
            this.shown = this.perPage;
        },
    };
}
