<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Content extends Model
{
    /** Categorías disponibles. */
    public const CATEGORIES = [
        'Noticias',
        'Notificaciones Judiciales',
        'Comunicados',
    ];

    /** Categoría que alimenta el bloque de Noticias de la portada. */
    public const NEWS_CATEGORY = 'Noticias';

    /**
     * Etapas de participación ciudadana (Ley 1757 de 2015), las mismas del
     * menú «Participa». Vacío: contenido sin participación.
     */
    public const PARTICIPATION_STAGES = [
        'Diagnóstico e identificación de problemas',
        'Planeación y presupuesto participativo',
        'Consulta ciudadana',
        'Colaboración e innovación',
        'Rendición de cuentas',
        'Control ciudadano',
    ];

    /** Titulares compactos junto a la nota destacada. */
    public const NEWS_SIDEBAR_LIMIT = 4;

    public const IMAGE_WIDTH = 1000;

    public const IMAGE_HEIGHT = 500;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_hidden' => 'boolean',
            'show_in_feed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $content): void {
            if (blank($content->slug)) {
                $content->slug = $content->uniqueSlug($content->title);
            }
        });

        // El borrado en cascada de la base de datos no dispara los eventos de
        // Eloquent, así que los medios se eliminan uno a uno para que cada uno
        // borre su archivo del disco.
        static::deleting(function (self $content): void {
            $content->media->each->delete();
        });
    }

    public function uniqueSlug(string $from): string
    {
        $base = Str::slug(Str::limit($from, 120, '')) ?: 'contenido';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->whereKeyNot($this->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /* ------------------------------------------------------------------ */
    /* Consultas                                                           */
    /* ------------------------------------------------------------------ */

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Ya publicada: sin fecha, o con una fecha que ya llegó. */
    public function scopePublished(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
    }

    /** Visible para el público: activa, publicada y no ocultada. */
    public function scopeOnHome(Builder $query): void
    {
        $query->active()->published()->where('is_hidden', false);
    }

    /**
     * Más reciente primero.
     *
     * Un contenido «sin fecha de visualización» no tiene published_at, así que
     * se ordena por su fecha de creación para que no caiga siempre al final.
     */
    public function scopeRecent(Builder $query, bool $descending = true): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $query->orderByRaw('COALESCE(published_at, created_at) '.$direction)->orderBy('id', $direction);
    }

    /** @param  Builder<self>  $query */
    public function scopeNews(Builder $query): void
    {
        $query->where('category', self::NEWS_CATEGORY);
    }

    /* ------------------------------------------------------------------ */
    /* Presentación                                                        */
    /* ------------------------------------------------------------------ */

    /* ------------------------------------------------------------------ */
    /* Medios                                                              */
    /* ------------------------------------------------------------------ */

    /** @return HasMany<ContentMedia, self> */
    public function media(): HasMany
    {
        return $this->hasMany(ContentMedia::class)->orderByDesc('is_main')->orderBy('position')->orderBy('id');
    }

    public function images(): Collection
    {
        return $this->media->where('type', ContentMedia::TYPE_IMAGE)->values();
    }

    public function mainImage(): ?ContentMedia
    {
        return $this->images()->firstWhere('is_main', true) ?? $this->images()->first();
    }

    public function video(): ?ContentMedia
    {
        return $this->media->firstWhere('type', ContentMedia::TYPE_VIDEO);
    }

    public function files(): Collection
    {
        return $this->media->where('type', ContentMedia::TYPE_FILE)->values();
    }

    public function imageUrl(): ?string
    {
        return $this->mainImage()?->fileUrl();
    }

    /* ------------------------------------------------------------------ */
    /* Fechas y enlaces                                                    */
    /* ------------------------------------------------------------------ */

    /** Fecha que se muestra; nula si se publicó «sin fecha de visualización». */
    public function displayDate(): ?Carbon
    {
        return $this->published_at;
    }

    /** Programado: tiene fecha, pero todavía no ha llegado. */
    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Destino del contenido: su propia página, salvo que apunte fuera.
     */
    public function url(): string
    {
        return $this->link ?: route('contents.show', $this->slug);
    }

    public function isExternal(): bool
    {
        return filled($this->link);
    }

    /** Resumen para los listados: el propio o las primeras líneas del cuerpo. */
    public function summary(int $limit = 180): string
    {
        if (filled($this->excerpt)) {
            return $this->excerpt;
        }

        return Str::limit(trim(html_entity_decode(strip_tags((string) $this->body))), $limit);
    }
}
