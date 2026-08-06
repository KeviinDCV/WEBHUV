<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlockSection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hide_in_feed' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<ContentBlock, self> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class, 'content_block_id');
    }

    /** Ruta que se muestra en el administrador, como en el portal actual. */
    public function breadcrumb(): string
    {
        return 'Home / Infórmate / '.$this->category;
    }
}
