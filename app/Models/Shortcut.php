<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Shortcut extends Model
{
    /** Iconos disponibles; las claves son las de resources/views/components/quick-icon.blade.php. */
    public const ICONS = [
        'calendar-check' => 'Calendario',
        'graduation' => 'Formación',
        'map-pin' => 'Ubicación',
        'lab' => 'Laboratorio',
        'payment' => 'Pagos',
        'inbox' => 'Buzón',
        'chart' => 'Indicadores',
        'info' => 'Información',
        'gavel' => 'Jurídico',
        'megaphone' => 'Anuncio',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @return BelongsTo<ShortcutBlock, self> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(ShortcutBlock::class, 'shortcut_block_id');
    }

    /**
     * Destino final del acceso.
     *
     * Una ruta que empieza por «/» apunta a una sección todavía no construida
     * aquí, así que se sirve desde el portal actual. Cuando esa sección exista
     * en este aplicativo, basta con vaciar huv.legacy_base para que el mismo
     * acceso pase a resolverse contra este dominio.
     */
    public function resolvedUrl(): string
    {
        if (! Str::startsWith($this->url, '/')) {
            return $this->url;
        }

        return rtrim((string) config('huv.legacy_base'), '/').$this->url;
    }

    /** Externo: sale del portal, así que se abre en otra pestaña. */
    public function isExternal(): bool
    {
        if (Str::startsWith($this->url, '/')) {
            return filled(config('huv.legacy_base'));
        }

        return ! Str::startsWith($this->url, [url('/'), config('app.url')]);
    }
}
