<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class TopicCategory extends Model
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

    /** @return BelongsTo<Topic, self> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsToMany<TopicItem, self> */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(TopicItem::class, 'topic_category_topic_item');
    }
}
