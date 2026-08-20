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
        // Un evento lleva dónde, quién lo convoca y cuándo empieza.
        $isEvent = $this->kind() === TopicItem::KIND_EVENT;

        // Una convocatoria abre y cierra, y lleva sus pliegos adjuntos.
        $isConvocation = $this->kind() === TopicItem::KIND_CONVOCATION;

        // Archivos y galería no van juntos. Archivos los lleva también el
        // documento y la convocatoria —el portal publica hasta veinticinco en
        // uno solo—, pero sus fichas no pintan ni fotos ni vídeo: admitirlos
        // sería guardar en disco algo que no se ve en ninguna parte.
        // El evento se publica como un artículo —con su cartel y su texto—, así
        // que lleva galería y adjuntos. Decía que sí el editor y que no la
        // validación, y en esa discordancia el cartel de un evento se subía, se
        // descartaba en silencio y la pantalla contestaba «publicado
        // correctamente».
        $hasFiles = $isArticle || $isDocument || $isConvocation || $isEvent;
        $hasGallery = $isArticle || $isEvent;

        return [
            'kind' => ['nullable', Rule::in($this->topic()->supportedKinds())],

            'title' => ['required', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:120000'],

            /* --- Propio del documento --- */
            'issued_at' => [Rule::excludeIf(! $isDocument), 'nullable', 'date'],

            /* --- Propio del evento --- */
            // Setenta caracteres, los mismos que pide el portal: los dos datos
            // se pintan en una línea bajo el título y no caben más.
            'event_host' => [Rule::excludeIf(! $isEvent), 'nullable', 'string', 'max:70'],
            'event_location' => [Rule::excludeIf(! $isEvent), 'nullable', 'string', 'max:70'],
            // Obligatorias: sin fecha, el evento desaparece del calendario y no
            // queda ni un sitio donde se note que le falta. El formulario del
            // portal también las pide.
            'event_date' => [Rule::excludeIf(! $isEvent), Rule::requiredIf($isEvent), 'date'],
            'event_time' => [Rule::excludeIf(! $isEvent), Rule::requiredIf($isEvent), 'date_format:H:i'],

            /* --- Propio de la convocatoria --- */
            'opens_at' => [Rule::excludeIf(! $isConvocation), 'nullable', 'date'],
            'closes_at' => [
                Rule::excludeIf(! $isConvocation),
                'nullable', 'date',
                // Cerrar antes de abrir no es una convocatoria, es una errata.
                'after_or_equal:opens_at',
            ],
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
                    && blank($this->file('files'))
                    && ! $this->route('item')?->isDownloaded()),
                'nullable', 'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.ContentMedia::MAX_FILE_KB,
            ],
            'file_alt' => [Rule::excludeIf(! $isDocument), 'nullable', 'string', 'max:250'],

            /* --- Propio del artículo --- */
            'no_end_date' => [Rule::excludeIf($isDocument || $isConvocation), 'boolean'],
            'expires_at' => [
                Rule::excludeIf($isDocument || $isConvocation || $this->boolean('no_end_date')),
                'nullable', 'date',
            ],
            'comment_wall' => [
                Rule::excludeIf($isDocument),
                'nullable',
                Rule::in(CommentWall::values()),
            ],

            /* --- Fotos nuevas --- */
            'photos' => [Rule::excludeIf(! $hasGallery), 'array', 'max:10'],
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
                Rule::excludeIf(! $hasGallery),
                'nullable', 'url:http,https', 'max:2048', 'regex:~(youtube\.com|youtu\.be)~i',
            ],

            /* --- Archivos adjuntos del artículo --- */
            'files' => [Rule::excludeIf(! $hasFiles), 'array', 'max:20'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.ContentMedia::MAX_FILE_KB,
            ],
            'file_titles' => ['array'],
            'file_titles.*' => ['nullable', 'string', 'max:250'],

            /* --- Imágenes de la biblioteca --- */
            'library_ids' => [Rule::excludeIf(! $hasGallery), 'array', 'max:20'],
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
            'title' => __('mensajes.campo.titulo'),
            'body' => __('mensajes.campo.descripcion'),
            'link' => __('mensajes.campo.enlace'),
            'issued_at' => __('mensajes.campo.fecha_expedicion'),
            'event_host' => __('mensajes.campo.organizador'),
            'event_location' => __('mensajes.campo.lugar'),
            'event_date' => __('mensajes.campo.fecha_inicio'),
            'event_time' => __('mensajes.campo.hora_inicio'),
            'opens_at' => __('mensajes.campo.fecha_inicio'),
            'closes_at' => __('mensajes.campo.fecha_cierre'),
            'expires_at' => __('mensajes.campo.fecha_final'),
            'file' => __('mensajes.campo.archivo'),
            'file_alt' => __('mensajes.campo.descripcion_archivo'),
            'photos' => __('mensajes.campo.fotos'),
            'files' => __('mensajes.campo.archivos'),
            'video_url' => __('mensajes.campo.url_video'),
            'topic_category_ids' => __('mensajes.campo.categorias'),
            'new_category' => __('mensajes.campo.categoria_nueva'),
            'published_at' => __('mensajes.campo.fecha_publicacion'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('mensajes.validacion.archivo_obligatorio'),
            'file.max' => __('mensajes.validacion.archivo_pesado'),
            'file.mimes' => __('mensajes.validacion.archivo_formatos'),
            'files.max' => __('mensajes.validacion.archivos_maximo'),
            'files.*.max' => __('mensajes.validacion.archivos_pesados'),
            'closes_at.after_or_equal' => __('mensajes.validacion.cierre_posterior'),
            'files.*.mimes' => __('mensajes.validacion.archivo_formatos'),
            'photos.*.max' => __('mensajes.validacion.foto_pesada'),
            'photos.*.image' => __('mensajes.validacion.foto_formato'),
            'video_url.regex' => __('mensajes.validacion.video_youtube'),
            'link.url' => __('mensajes.validacion.enlace_http'),
            'link.required' => __('mensajes.validacion.enlace_destino'),
            'topic_category_ids.*.exists' => __('mensajes.validacion.categoria_ajena'),
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
                            __('mensajes.validacion.foto_alt')
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
