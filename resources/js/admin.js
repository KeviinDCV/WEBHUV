/**
 * Editor de texto enriquecido de la administración.
 *
 * Va en un paquete aparte del sitio público: Quill pesa unos 40 KB comprimidos
 * y no tiene por qué descargarlo quien solo viene a leer una noticia. Solo lo
 * carga layouts/admin.blade.php.
 *
 * El HTML resultante se depura en el servidor antes de guardarlo
 * (App\Support\RichText), nunca se confía en lo que llega del navegador.
 */
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const TOOLBAR = [
    [{ header: [2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'link'],
    ['clean'],
];

document.querySelectorAll('[data-huv-editor]').forEach((holder) => {
    const input = document.querySelector(holder.dataset.huvEditor);
    if (!input) return;

    const editor = new Quill(holder, {
        theme: 'snow',
        modules: { toolbar: TOOLBAR },
        placeholder: holder.dataset.huvEditorPlaceholder ?? '',
    });

    editor.clipboard.dangerouslyPasteHTML(input.value ?? '');

    // El campo real es el input oculto: se sincroniza en cada cambio para que
    // el formulario funcione con un envío normal, sin JavaScript de por medio
    // en el momento de guardar.
    const sync = () => {
        const html = editor.getSemanticHTML().trim();

        input.value = editor.getText().trim() === '' ? '' : html;
    };

    editor.on('text-change', sync);
    input.form?.addEventListener('submit', sync);
    sync();

    // El área de escritura debe anunciarse como tal y quedar asociada a su
    // etiqueta; Quill no lo hace por su cuenta.
    const area = holder.querySelector('.ql-editor');
    if (area) {
        area.setAttribute('role', 'textbox');
        area.setAttribute('aria-multiline', 'true');
        if (holder.dataset.huvEditorLabel) {
            area.setAttribute('aria-label', holder.dataset.huvEditorLabel);
        }
    }
});
