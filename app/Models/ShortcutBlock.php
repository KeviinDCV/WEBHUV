<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortcutBlock extends Model
{
    /** Accesos por barra, igual que en el portal actual. */
    public const MAX_SHORTCUTS = 5;

    /**
     * Mínimo para que la barra se publique.
     *
     * Con uno o dos accesos la rejilla queda descompensada y el bloque no
     * comunica que es una barra de atajos.
     */
    public const MIN_TO_PUBLISH = 3;

    /**
     * Temas de color disponibles.
     *
     * El color se aplica al icono. El rótulo conserva el color institucional
     * a propósito: con diecinueve tonos disponibles, varios de ellos —amarillo,
     * cian, verde lima— no alcanzan el contraste mínimo sobre fondo blanco, y
     * el texto es lo que de verdad hay que poder leer.
     */
    public const THEMES = [
        'navy' => '#2b3b80',
        'azure' => '#2676d2',
        'sky' => '#00a8e8',
        'teal' => '#00857a',
        'green' => '#1f8a4c',
        'olive' => '#6c7a1e',
        'lime' => '#4f7a12',
        'amber' => '#a15c00',
        'orange' => '#c05600',
        'red' => '#b3261e',
        'crimson' => '#a2003e',
        'magenta' => '#a3007a',
        'pink' => '#c2185b',
        'purple' => '#6a1b9a',
        'violet' => '#4527a0',
        'indigo' => '#283593',
        'slate' => '#3d4552',
        'graphite' => '#14275e',
        'ink' => '#33383f',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (self $block) => $block->shortcuts()->delete());
    }

    /** @return HasMany<Shortcut, self> */
    public function shortcuts(): HasMany
    {
        return $this->hasMany(Shortcut::class)->orderBy('position')->orderBy('id');
    }

    /** @param  Builder<self>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Solo se publica con accesos suficientes. */
    public function isPublishable(): bool
    {
        return $this->shortcuts->count() >= self::MIN_TO_PUBLISH;
    }

    public function hasRoom(): bool
    {
        return $this->shortcuts()->count() < self::MAX_SHORTCUTS;
    }

    public function themeColor(): string
    {
        return self::THEMES[$this->theme] ?? self::THEMES['navy'];
    }
}
