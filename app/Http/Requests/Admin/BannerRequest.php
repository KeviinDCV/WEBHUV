<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
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
        $creating = $this->route('banner') === null;

        return [
            'media_type' => ['required', Rule::in(['image'])],

            // Al crear, la imagen es obligatoria; al editar solo si se reemplaza.
            'image' => [
                $creating ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif,bmp,webp',
                'max:2048', // 2 MB, igual que el portal actual
                'dimensions:min_width=1200,min_height=310',
            ],

            'filter_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'filter_opacity' => ['required', 'integer', 'between:0,100'],

            'title' => ['nullable', 'string', 'max:90'],
            'title_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'title_background' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'title_font' => ['required', Rule::in(Banner::FONTS)],
            'title_bold' => ['boolean'],
            'title_italic' => ['boolean'],

            'subtitle' => ['nullable', 'string', 'max:140'],
            'subtitle_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'subtitle_background' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'subtitle_font' => ['required', Rule::in(Banner::FONTS)],
            'subtitle_bold' => ['boolean'],
            'subtitle_italic' => ['boolean'],

            'alignment' => ['required', Rule::in(array_keys(Banner::ALIGNMENTS))],

            'alt_text' => ['required', 'string', 'max:250'],

            // `active_url` no: muchos enlaces internos del hospital no resuelven
            // desde el servidor. Basta con exigir un http(s) bien formado.
            'link' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image' => 'imagen de fondo',
            'filter_color' => 'color del filtro',
            'filter_opacity' => 'opacidad del filtro',
            'title' => 'título',
            'title_color' => 'color del título',
            'title_background' => 'color de fondo del título',
            'title_font' => 'tipografía del título',
            'subtitle' => 'subtítulo',
            'subtitle_color' => 'color del subtítulo',
            'subtitle_background' => 'color de fondo del subtítulo',
            'subtitle_font' => 'tipografía del subtítulo',
            'alignment' => 'justificación',
            'alt_text' => 'texto descriptivo para accesibilidad',
            'link' => 'enlace',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.max' => 'La imagen no puede pesar más de 2 MB.',
            'image.dimensions' => 'La imagen es demasiado pequeña. Se recomienda 3750 × 968 píxeles.',
            'link.url' => 'El enlace debe empezar por http:// o https://',
            'alt_text.required' => 'El texto descriptivo es obligatorio: sin él, el banner no se '
                .'puede entender con un lector de pantalla.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Las casillas sin marcar no llegan en la petición.
        $this->merge([
            'title_bold' => $this->boolean('title_bold'),
            'title_italic' => $this->boolean('title_italic'),
            'subtitle_bold' => $this->boolean('subtitle_bold'),
            'subtitle_italic' => $this->boolean('subtitle_italic'),
            'title_background' => $this->filled('title_background') ? $this->input('title_background') : null,
            'subtitle_background' => $this->filled('subtitle_background') ? $this->input('subtitle_background') : null,
        ]);
    }
}
