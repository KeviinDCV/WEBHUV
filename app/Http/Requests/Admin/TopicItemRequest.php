<?php

namespace App\Http\Requests\Admin;

use App\Models\ContentMedia;
use App\Models\Topic;
use App\Support\CommentWall;
use App\Models\TopicItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TopicItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function topic(): Topic
    {
        return $this->route('topic');
    }

    /**
     * Un documento y un artículo se dan de alta con el mismo formulario, pero
     * no con los mismos campos: lo del otro tipo se excluye en vez de validarse
     * en balde.
     */
    public function kind(): string
    {
        // Al editar, el tipo no se cambia: mover un documento a artículo dejaría
        // su archivo huérfano y su fecha de expedición sin sitio.
        if ($item = $this->route('item')) {
            return $item->kind;
        }

        $sent = $this->input('kind');

        // No se propaga lo que llegue. Un campo vacío llega como null —el
        // middleware lo convierte— y un `kind[]` llega como arreglo; devolver
        // eso reventaría con un error del servidor desde rules(), es decir
        // antes de validar nada y sin mensaje para quien está escribiendo. Un
        // valor raro cae en el tipo por defecto y es Rule::in quien lo rechaza.
        return is_string($sent) && $sent !== ''
            ? $sent
            : $this->topic()->defaultKind();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isDocument = $this->kind() === TopicItem::KIND_DOCUMENT;
        // Un aviso es solo título y texto: ni archivo ni fotos ni vídeo.
        $isArticle = $this->kind() === TopicItem::KIND_ARTICLE;
        // Un enlace se define por su destino: sin él no lleva a ninguna parte.
        $isLink = $this->kind() === TopicItem::KIND_LINK;

        return [
            'kind' => ['nullable', Rule::in($this->topic()->supportedKinds())],

            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:120000'],

            /* --- Propio del documento --- */
            'issued_at' => [Rule::excludeIf(! $isDocument), 'nullable', 'date'],
            'link' => [
                Rule::excludeIf(! $isDocument && ! $isLink),
                Rule::requiredIf($isLink),
                'nullable', 'url:http,https', 'max:2048',
            ],
            'file' => [
                Rule::excludeIf(! $isDocument),
                // Un documento sin archivo ni enlace no lleva a ninguna parte;
                // al editar basta con que ya tuviera uno subido.
                Rule::requiredIf(fn () => $isDocument
                    && blank($this->input('link'))
                    && ! $this->route('item')?->isDownloaded()),
                'nullable', 'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.ContentMedia::MAX_FILE_KB,
            ],
            'file_alt' => [Rule::excludeIf(! $isDocument), 'nullable', 'string', 'max:250'],

            /* --- Propio del artículo --- */
            'no_end_date' => [Rule::excludeIf($isDocument), 'boolean'],
            'expires_at' => [
                Rule::excludeIf($isDocument || $this->boolean('no_end_date')),
                'nullable', 'date',
            ],
            'comment_wall' => [
                Rule::excludeIf($isDocument),
                'nullable',
                Rule::in(CommentWall::values()),
            ],

            /* --- Fotos nuevas --- */
            'photos' => [Rule::excludeIf(! $isArticle), 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:2048'],
            // La obligatoriedad de la descripción se comprueba en after():
            // `required_with` no sirve aquí porque, si no llega ningún
            // photo_alts, la regla de photo_alts.* no llega a ejecutarse.
            'photo_alts' => ['array'],
            'photo_alts.*' => ['nullable', 'string', 'max:250'],

            /* --- Medios ya guardados --- */
            'media_alt' => ['array'],
            'media_alt.*' => ['nullable', 'string', 'max:250'],
            'media_delete' => ['array'],
            'media_delete.*' => ['integer'],
            'media_main' => ['nullable'],

            /* --- Vídeo --- */
            'video_url' => [
                Rule::excludeIf(! $isArticle),
                'nullable', 'url:http,https', 'max:2048', 'regex:~(youtube\.com|youtu\.be)~i',
            ],

            /* --- Archivos adjuntos del artículo --- */
            'files' => [Rule::excludeIf(! $isArticle), 'array', 'max:10'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.ContentMedia::MAX_FILE_KB,
            ],
            'file_titles' => ['array'],
            'file_titles.*' => ['nullable', 'string', 'max:250'],

            /* --- Imágenes de la biblioteca --- */
            'library_ids' => [Rule::excludeIf(! $isArticle), 'array', 'max:20'],
            'library_ids.*' => ['integer', 'exists:library_images,id'],

            /* --- Categorías del tema --- */
            'topic_category_ids' => ['array'],
            // La categoría tiene que pertenecer al tema: si no, un elemento de
            // Presupuesto podría acabar clasificado bajo una categoría de Planes.
            'topic_category_ids.*' => [
                'integer',
                Rule::exists('topic_categories', 'id')->where('topic_id', $this->topic()->id),
            ],
            'new_category' => ['nullable', 'string', 'max:120'],

            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'show_in_feed' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'body' => 'descripción',
            'link' => 'enlace',
            'issued_at' => 'fecha de expedición',
            'expires_at' => 'fecha final de visualización',
            'file' => 'archivo',
            'file_alt' => 'descripción del archivo',
            'photos' => 'fotos',
            'files' => 'archivos',
            'video_url' => 'URL del vídeo',
            'topic_category_ids' => 'categorías',
            'new_category' => 'categoría nueva',
            'published_at' => 'fecha de publicación',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Adjunte el archivo o indique un enlace donde consultarlo.',
            'file.max' => 'El archivo puede pesar como máximo 30 MB.',
            'file.mimes' => 'Formatos admitidos: pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt y zip.',
            'files.*.max' => 'Cada archivo puede pesar como máximo 30 MB.',
            'files.*.mimes' => 'Formatos admitidos: pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt y zip.',
            'photos.*.max' => 'Cada foto puede pesar como máximo 2 MB.',
            'photos.*.image' => 'Solo se admiten imágenes en gif, jpg, jpeg, png, bmp o webp.',
            'video_url.regex' => 'El vídeo debe ser una dirección de YouTube.',
            'link.url' => 'El enlace debe empezar por http:// o https://',
            'link.required' => 'Indique la dirección a la que lleva este contenido.',
            'topic_category_ids.*.exists' => 'Esa categoría no pertenece a este tema.',
        ];
    }

    /**
     * Cada foto subida necesita su descripción.
     *
     * Sin ella el contenido es inaccesible para quien usa lector de pantalla
     * (WCAG 1.1.1), así que se comprueba foto a foto.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->file('photos', [])) as $index) {
                    if (blank($this->input("photo_alts.{$index}"))) {
                        $validator->errors()->add(
                            "photo_alts.{$index}",
                            'Cada foto necesita su descripción: sin ella, no se puede entender '
                                .'con un lector de pantalla.'
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_end_date' => $this->boolean('no_end_date'),
            'is_featured' => $this->boolean('is_featured'),
            'show_in_feed' => $this->boolean('show_in_feed'),
        ]);
    }
}
