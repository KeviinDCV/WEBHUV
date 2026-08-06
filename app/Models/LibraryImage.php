<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LibraryImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $image): void {
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }

    /** @return BelongsTo<MediaCategory, self> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'media_category_id');
    }

    public function fileUrl(): string
    {
        // asset() y no Storage::url(): este último usa APP_URL y fallaría si la
        // aplicación se sirve en otro host o puerto.
        return asset('storage/'.$this->path);
    }
}
