/**
 * Editor de un banner, con vista previa en vivo.
 *
 * La previsualización reproduce exactamente lo que renderiza la portada
 * —imagen, velo de color, título y subtítulo— para no tener que guardar y
 * volver a la portada en cada ajuste.
 */
export default function huvBannerForm(initial = {}) {
    return {
        imageUrl: initial.imageUrl ?? null,
        objectUrl: null,

        filterColor: initial.filterColor ?? '#000000',
        filterOpacity: Number(initial.filterOpacity ?? 0),

        title: initial.title ?? '',
        titleColor: initial.titleColor ?? '#FFFFFF',
        titleBackground: initial.titleBackground ?? '',
        titleFont: initial.titleFont ?? 'Montserrat',
        titleBold: Boolean(initial.titleBold ?? true),
        titleItalic: Boolean(initial.titleItalic ?? false),

        subtitle: initial.subtitle ?? '',
        subtitleColor: initial.subtitleColor ?? '#FFFFFF',
        subtitleBackground: initial.subtitleBackground ?? '',
        subtitleFont: initial.subtitleFont ?? 'Montserrat',
        subtitleBold: Boolean(initial.subtitleBold ?? false),
        subtitleItalic: Boolean(initial.subtitleItalic ?? false),

        alignment: initial.alignment ?? 'left',
        altText: initial.altText ?? '',

        destroy() {
            this.releaseObjectUrl();
        },

        releaseObjectUrl() {
            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }
        },

        /** Vista previa inmediata del archivo elegido, sin subirlo todavía. */
        onFileChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            this.releaseObjectUrl();
            this.objectUrl = URL.createObjectURL(file);
            this.imageUrl = this.objectUrl;
        },

        clearImage() {
            this.releaseObjectUrl();
            this.imageUrl = null;
            this.$refs.image.value = '';
        },

        get filterStyle() {
            return {
                backgroundColor: this.filterColor,
                opacity: this.filterOpacity / 100,
            };
        },

        textStyle(prefix) {
            const style = {
                color: this[`${prefix}Color`],
                fontFamily: `'${this[`${prefix}Font`]}', sans-serif`,
                fontWeight: this[`${prefix}Bold`] ? 800 : 400,
                fontStyle: this[`${prefix}Italic`] ? 'italic' : 'normal',
            };

            if (this[`${prefix}Background`]) {
                style.backgroundColor = this[`${prefix}Background`];
                style.padding = '0.25em 0.5em';
            }

            return style;
        },

        remaining(field, limit) {
            return limit - (this[field]?.length ?? 0);
        },
    };
}
