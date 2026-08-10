<?php

namespace App\Http\Requests\Admin;

use App\Models\Content;
use App\Models\ContentMedia;
use App\Support\CommentWall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(Content::CATEGORIES)],

            'no_date' => ['boolean'],
            'published_at' => [
                Rule::excludeIf($this->boolean('no_date')),
                'nullable', 'date',
            ],

            'no_end_date' => ['boolean'],
            'expires_at' => [
                Rule::excludeIf($this->boolean('no_end_date')),
                'nullable', 'date',
                // Caducar antes de publicarse dejaría el contenido invisible
                // desde el primer momento.
                Rule::when(
                    ! $this->boolean('no_date') && $this->filled('published_at'),
                    ['after:published_at']
                ),
            ],

            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:120000'],
            'link' => ['nullable', 'url:http,https', 'max:2048'],

            /* --- Fotos nuevas --- */
            'photos' => ['array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:2048'],
            // La obligatoriedad de la descripción se comprueba en after():
            // `required_with` no sirve aquí porque, si no llega ningún
            // photo_alts, la regla de photo_alts.* no llega a ejecutarse.
            'photo_alts' => ['array'],
            'photo_alts.*' => ['nullable', 'string', 'max:250'],

            /* --- Fotos ya guardadas --- */
            'media_alt' => ['array'],
            'media_alt.*' => ['nullable', 'string', 'max:250'],
            'media_delete' => ['array'],
            'media_delete.*' => ['integer'],
            'media_main' => ['nullable'],

            /* --- Vídeo --- */
            'video_url' => ['nullable', 'url:http,https', 'max:2048', 'regex:~(youtube\.com|youtu\.be)~i'],

            /* --- Archivos --- */
            'files' => ['array', 'max:10'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.ContentMedia::MAX_FILE_KB,
            ],
            'file_titles' => ['array'],
            'file_titles.*' => ['nullable', 'string', 'max:250'],

            /* --- Imágenes de la biblioteca --- */
            'library_ids' => ['array', 'max:20'],
            'library_ids.*' => ['integer', 'exists:library_images,id'],

            'comment_wall' => ['nullable', Rule::in(CommentWall::values())],

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
            'category' => 'categoría',
            'published_at' => 'fecha de visualización',
            'excerpt' => 'resumen',
            'body' => 'descripción',
            'photos' => 'fotos',
            'video_url' => 'URL del vídeo',
            'files' => 'archivos',
            'link' => 'enlace',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.*.max' => 'Cada foto puede pesar como máximo 2 MB.',
            'photos.*.image' => 'Solo se admiten imágenes en gif, jpg, jpeg, png, bmp o webp.',
            'photo_alts.*.required_with' => 'Cada foto necesita su descripción: sin ella, no se '
                .'puede entender con un lector de pantalla.',
            'video_url.regex' => 'El vídeo debe ser una dirección de YouTube.',
            'files.*.max' => 'Cada archivo puede pesar como máximo 30 MB.',
            'files.*.mimes' => 'Formatos admitidos: pdf, doc, docx, xls, xlsx, ppt, pptx, csv, txt y zip.',
            'link.url' => 'El enlace debe empezar por http:// o https://',
            'expires_at.after' => 'La fecha final debe ser posterior a la de publicación.',
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
            function (\Illuminate\Validation\Validator $validator): void {
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
            'no_date' => $this->boolean('no_date'),
            'no_end_date' => $this->boolean('no_end_date'),
            'is_featured' => $this->boolean('is_featured'),
            'show_in_feed' => $this->boolean('show_in_feed'),
        ]);
    }
}
