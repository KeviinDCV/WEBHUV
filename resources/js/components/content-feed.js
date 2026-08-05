/**
 * Listado general de contenidos: orden, filtros y «cargar más».
 *
 * Las tarjetas se renderizan enteras en el servidor —bien para buscadores y
 * para quien navega sin JavaScript— y este componente solo decide cuáles se
 * muestran y en qué posición, mediante `x-show` y la propiedad CSS `order`.
 * Sin JavaScript el listado sigue siendo legible: aparece completo y ordenado
 * de más reciente a más antiguo.
 */
export default function huvContentFeed({ meta = [], perPage = 6 } = {}) {
    return {
        meta,
        perPage,
        order: 'recientes',
        period: 'todos',
        category: 'todos',
        shown: perPage,

        init() {
            // Cualquier cambio de criterio reinicia la paginación: mantener el
            // «cargar más» anterior mostraría un recuento incoherente.
            this.$watch('order', () => (this.shown = this.perPage));
            this.$watch('period', () => (this.shown = this.perPage));
            this.$watch('category', () => (this.shown = this.perPage));
        },

        get matching() {
            const now = Date.now();
            const days = this.period === 'todos' ? null : Number(this.period);

            return this.meta
                .filter((item) => this.category === 'todos' || item.category === this.category)
                .filter((item) => days === null || now - item.timestamp <= days * 86400000)
                .sort((a, b) =>
                    this.order === 'recientes'
                        ? b.timestamp - a.timestamp
                        : a.timestamp - b.timestamp
                );
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
            this.order = 'recientes';
            this.period = 'todos';
            this.category = 'todos';
            this.shown = this.perPage;
        },
    };
}
