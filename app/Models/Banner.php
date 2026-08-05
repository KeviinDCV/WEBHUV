<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class Banner extends Model
{
    /** Tope de banners publicables, igual que en el portal actual. */
    public const MAX = 5;

    public const ALIGNMENTS = ['left' => 'Izquierda', 'center' => 'Centro'];

    public const FONTS = ['Montserrat', 'Work Sans'];

    /** Proporción de la imagen del banner: 3750 × 968. */
    public const IMAGE_WIDTH = 3750;

    public const IMAGE_HEIGHT = 968;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'filter_opacity' => 'integer',
            'title_bold' => 'boolean',
            'title_italic' => 'boolean',
            'subtitle_bold' => 'boolean',
            'subtitle_italic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar el registro se borra también su archivo: si no, el disco
        // acumula imágenes que ya no referencia nadie.
        static::deleted(function (self $banner): void {
            $banner->deleteImage();
        });
    }

    /** @param  Builder<self>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Dirección pública de la imagen.
     *
     * Se usa asset() y no Storage::url(): este último arma la dirección con
     * APP_URL, así que si la aplicación se sirve en otro host o puerto —el
     * :8001 del servidor de desarrollo, por ejemplo— la imagen se pide a un
     * origen equivocado y no carga. asset() sigue el host real de la petición.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public function deleteImage(): void
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
        }
    }

    /** Estilo CSS del velo que se superpone a la imagen. */
    public function filterStyle(): ?string
    {
        if ($this->filter_opacity <= 0) {
            return null;
        }

        return sprintf(
            'background-color: %s; opacity: %s',
            $this->filter_color,
            number_format($this->filter_opacity / 100, 2, '.', '')
        );
    }

    public function hasOverlayText(): bool
    {
        return filled($this->title) || filled($this->subtitle);
    }

    /**
     * Estilo en línea de un texto del banner.
     *
     * Los colores y la tipografía los elige quien edita, así que no pueden
     * expresarse con clases de utilidad y viajan como estilo en línea.
     */
    public function textStyle(string $prefix): string
    {
        $styles = [
            'color: '.$this->{$prefix.'_color'},
            "font-family: '".$this->{$prefix.'_font'}."', sans-serif",
            'font-weight: '.($this->{$prefix.'_bold'} ? '800' : '400'),
        ];

        if ($this->{$prefix.'_italic'}) {
            $styles[] = 'font-style: italic';
        }

        if (filled($this->{$prefix.'_background'})) {
            $styles[] = 'background-color: '.$this->{$prefix.'_background'};
            $styles[] = 'padding: 0.25em 0.5em';
        }

        return implode('; ', $styles);
    }
}
