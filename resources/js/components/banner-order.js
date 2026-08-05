/**
 * Reordenación de los banners de la portada.
 *
 * El orden vive en un array de identificadores y se refleja con la propiedad
 * CSS `order`, así que las filas no se mueven en el DOM y no se pierde el foco
 * del botón que se acaba de pulsar. Los `input` ocultos que viajan al servidor
 * se generan a partir de ese mismo array.
 *
 * Sin JavaScript no se puede reordenar, pero el formulario sigue guardando la
 * duración de rotación: el servidor trata el orden como opcional.
 */
export default function huvBannerOrder(initialIds = []) {
    return {
        ids: [...initialIds],
        announcement: '',

        position(id) {
            return this.ids.indexOf(id) + 1;
        },

        isFirst(id) {
            return this.ids.indexOf(id) === 0;
        },

        isLast(id) {
            return this.ids.indexOf(id) === this.ids.length - 1;
        },

        move(id, direction) {
            const from = this.ids.indexOf(id);
            const to = from + direction;

            if (from === -1 || to < 0 || to >= this.ids.length) return;

            [this.ids[from], this.ids[to]] = [this.ids[to], this.ids[from]];

            // El cambio es puramente visual; sin anunciarlo, quien usa lector de
            // pantalla no percibe que la fila se movió.
            this.announcement = `Banner movido a la posición ${to + 1} de ${this.ids.length}.`;
        },
    };
}
