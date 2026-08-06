<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class EventCategory extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name) ?: 'categoria';
            }
        });
    }

    /** @return BelongsToMany<Event, self> */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
}
