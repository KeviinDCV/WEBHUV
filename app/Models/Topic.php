<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Topic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $topic): void {
            if (blank($topic->slug)) {
                $topic->slug = Str::slug($topic->name) ?: 'tema';
            }
        });
    }

    /** @return HasMany<TopicCategory, self> */
    public function categories(): HasMany
    {
        return $this->hasMany(TopicCategory::class)->orderBy('name');
    }

    /** @return HasMany<Document, self> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
