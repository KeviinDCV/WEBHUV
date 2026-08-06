<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    /** Tamaño máximo del archivo, en kilobytes. */
    public const MAX_FILE_KB = 30720;

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
            'link' => ['nullable', 'url:http,https', 'max:2048'],
            'issued_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:120000'],

            'file' => [
                // Un documento sin archivo ni enlace no lleva a ninguna parte;
                // al editar basta con que ya tuviera uno subido.
                Rule::requiredIf(fn () => blank($this->input('link'))
                    && ! $this->route('document')?->isDownloaded()),
                'nullable', 'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip',
                'max:'.self::MAX_FILE_KB,
            ],
            'file_alt' => ['nullable', 'string', 'max:250'],

            'topic_category_id' => [
                'nullable',
                // La categoría tiene que pertenecer al tema: si no, un
                // documento de Presupuesto podría acabar clasificado bajo una
                // categoría de Planes.
                Rule::exists('topic_categories', 'id')
                    ->where('topic_id', $this->route('topic')->id),
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
            'link' => 'enlace',
            'issued_at' => 'fecha de expedición',
            'description' => 'descripción',
            'file' => 'archivo',
            'file_alt' => 'descripción del archivo',
            'topic_category_id' => 'categoría',
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
            'link.url' => 'El enlace debe empezar por http:// o https://',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'show_in_feed' => $this->boolean('show_in_feed'),
        ]);
    }
}
