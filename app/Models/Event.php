<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Event extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<EventCategory, self> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Limita a unas categorías concretas.
     *
     * Una lista vacía no filtra: el bloque muestra toda la agenda.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $categories
     */
    public function scopeInCategories(Builder $query, array $categories): void
    {
        if ($categories === []) {
            return;
        }

        $query->whereHas('categories', fn (Builder $q) => $q->whereIn('event_categories.id', $categories));
    }

    public function endsAt(): Carbon
    {
        return $this->ends_at ?? $this->starts_at;
    }

    /** Ocupa el día entero: útil para pintarlo de otra forma en el calendario. */
    public function spansFullDay(): bool
    {
        return $this->starts_at->isSameDay($this->endsAt())
            && $this->starts_at->format('H:i') === '00:00'
            && $this->endsAt()->format('H:i') === '23:59';
    }

    /** Se llama link() y no url() para no chocar con el atributo `url`. */
    public function link(): ?string
    {
        return $this->url ?: null;
    }
}
