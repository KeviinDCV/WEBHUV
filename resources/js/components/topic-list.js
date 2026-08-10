/**
 * Listado de un tema: categorías, búsqueda, orden y vista.
 *
 * Mismo planteamiento que el muro de contenidos: las fichas se imprimen enteras
 * en el servidor y aquí solo se decide cuáles se ven y en qué posición, con
 * `x-show` y la propiedad CSS `order`. Sin JavaScript el tema sigue siendo
 * navegable: aparecen todos los contenidos, de más reciente a más antiguo.
 */

const VIEW_KEY = 'huv:topicView';

/** Categorías visibles antes de pulsar «Ver más». */
const CATEGORIES_SHOWN = 5;

export default function huvTopicList({
    meta = [],
    categories = [],
    publicTabs = [],
    perPage = 6,
    canModerate = false,
    openEditor = false,
} = {}) {
    return {
        meta,
        categories,
        publicTabs,
        perPage,
        canModerate,

        /** Editor incrustado, encima del listado. */
        editor: openEditor,

        category: 'todas',
        kind: 'todos',
        search: '',
        // Sin pestañas públicas el tema tiene orden manual: se respeta el que
        // llega del servidor en lugar de reordenar por fecha.
        tab: publicTabs.length ? publicTabs[0].key : 'manual',
        period: 'todos',
        view: 'grid',
        shown: perPage,
        allCategories: false,

        init() {
            this.categoryFromUrl();

            try {
                const stored = window.localStorage.getItem(VIEW_KEY);
                if (stored === 'grid' || stored === 'list') this.view = stored;
            } catch {
                /* Modo privado: se queda en rejilla. */
            }

            ['tab', 'period', 'category', 'kind', 'search'].forEach((prop) =>
                this.$watch(prop, () => (this.shown = this.perPage))
            );
        },

        /**
         * Categoría que llega en la dirección.
         *
         * El portal abre un tema ya filtrado con «/tema/{tema}/{categoría}», y
         * de ahí salen los atajos de «Población vulnerable». Aquí eso viaja como
         * `?categoria={id}`, así que hay que recogerlo al arrancar: si no, el
         * enlace llevaría al tema entero y el filtro se perdería por el camino.
         *
         * Una categoría que no existe se ignora, como hace el portal: enseña el
         * tema completo en vez de un listado vacío.
         */
        categoryFromUrl() {
            const raw = new URLSearchParams(window.location.search).get('categoria');

            if (raw === null) return;

            const id = Number(raw);

            if (this.categories.some((item) => item.id === id)) this.category = id;
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

        /**
         * Las pestañas públicas las decide el servidor, no este componente:
         * «Fecha de expedición» solo tiene sentido donde hay documentos, y eso
         * depende del tema.
         */
        get tabs() {
            const tabs = [...this.publicTabs];

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
            // El orden manual ya viene dado: no se toca.
            if (this.tab === 'manual') {
                return () => 0;
            }

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
                // Un contenido puede llevar varias categorías del tema, así que
                // se comprueba la pertenencia, no la igualdad.
                .filter((item) => this.category === 'todas' || item.categories.includes(this.category))
                .filter((item) => this.kind === 'todos' || item.kind === this.kind)
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
            this.kind = 'todos';
            this.search = '';
            this.tab = this.publicTabs.length ? this.publicTabs[0].key : 'manual';
            this.period = 'todos';
            this.shown = this.perPage;
        },
    };
}
