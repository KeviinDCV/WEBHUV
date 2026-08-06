<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaCategory extends Model
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

    /** @return HasMany<LibraryImage, self> */
    public function images(): HasMany
    {
        return $this->hasMany(LibraryImage::class);
    }
}
