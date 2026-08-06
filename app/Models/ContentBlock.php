<?php

namespace App\Models;

use App\Support\Themes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBlock extends Model
{
    /** Bloque de Noticias de la portada. */
    public const NEWS_KEY = 'noticias';

    /** Bloque de la agenda de eventos. */
    public const EVENTS_KEY = 'eventos';

    /**
     * Secciones de origen que ofrece el bloque de eventos, tal como el portal
     * actual: el calendario de actividades y las etapas de participación.
     */
    public const EVENT_SOURCES = [
        'Calendario de actividades',
        'Colaboración e innovación',
        'Consulta ciudadana',
        'Control ciudadano',
        'Oficina Coordinadora Académica',
        'Planeación y presupuesto participativo',
        'Rendición de cuentas',
    ];

    /** Secciones que puede combinar un bloque. */
    public const MAX_SECTIONS = 3;

    public const SORTS = [
        'recent' => 'Más reciente',
        'oldest' => 'Más antiguo',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'show_title' => 'boolean',
            'position' => 'integer',
            'options' => 'array',
        ];
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options, $key, $default);
    }

    /** Bloque de la agenda, creándolo con valores por defecto si no existe. */
    public static function events(): self
    {
        return static::firstOrCreate(
            ['key' => self::EVENTS_KEY],
            [
                'name' => 'Eventos',
                'sort' => 'recent',
                'show_title' => true,
                'theme' => 'navy',
                'position' => 2,
                'options' => ['source' => self::EVENT_SOURCES[0], 'categories' => []],
            ]
        );
    }

    /** @return HasMany<ContentBlockSection, self> */
    public function sections(): HasMany
    {
        return $this->hasMany(ContentBlockSection::class)->orderBy('position')->orderBy('id');
    }

    public function themeColor(): string
    {
        return Themes::color($this->theme);
    }

    public function isDescending(): bool
    {
        return $this->sort !== 'oldest';
    }

    /**
     * Bloque de Noticias, creándolo con valores por defecto si aún no existe.
     *
     * Así la portada nunca depende de que alguien haya pasado por la pantalla
     * de configuración.
     */
    public static function news(): self
    {
        $block = static::with('sections')->firstOrCreate(
            ['key' => self::NEWS_KEY],
            ['name' => 'Noticias', 'sort' => 'recent', 'show_title' => true, 'theme' => 'navy', 'position' => 1]
        );

        if ($block->sections->isEmpty()) {
            $block->sections()->create([
                'category' => Content::NEWS_CATEGORY,
                'title' => 'Noticias',
                'position' => 1,
            ]);

            $block->load('sections');
        }

        return $block;
    }
}
