<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
            'file_size' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            if (blank($document->slug)) {
                $document->slug = $document->uniqueSlug($document->title);
            }
        });

        static::deleted(fn (self $document) => $document->deleteFile());
    }

    public function uniqueSlug(string $from): string
    {
        $base = Str::slug(Str::limit($from, 200, '')) ?: 'documento';
        $slug = $base;
        $suffix = 2;

        while (static::where('topic_id', $this->topic_id)
            ->where('slug', $slug)
            ->whereKeyNot($this->getKey())
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** @return BelongsTo<Topic, self> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsTo<TopicCategory, self> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TopicCategory::class, 'topic_category_id');
    }

    /* ------------------------------------------------------------------ */

    /** @param  Builder<self>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('is_hidden', false)
            // Lo programado todavía no existe para el público.
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function isScheduled(): bool
    {
        return $this->published_at?->isFuture() ?? false;
    }

    public function isPublic(): bool
    {
        return $this->is_active && ! $this->is_hidden && ! $this->isScheduled();
    }

    /**
     * Fecha con la que se ordena y se muestra: la de publicación, que es la que
     * encabeza cada ficha en el listado.
     */
    public function date(): ?Carbon
    {
        return $this->published_at ?? $this->issued_at ?? $this->created_at;
    }

    /** Resumen en texto plano, para las fichas del listado. */
    public function summary(int $characters = 160): string
    {
        return Str::limit(RichText::toPlainText($this->description), $characters);
    }

    /**
     * Dirección del archivo.
     *
     * Mientras no se haya descargado, apunta al portal de origen: así el
     * documento nunca queda inaccesible a medio migrar.
     */
    public function fileUrl(): ?string
    {
        // asset() y no Storage::url(): este último usa APP_URL y fallaría si la
        // aplicación se sirve en otro host o puerto.
        return $this->file_path
            ? asset('storage/'.$this->file_path)
            : $this->source_url;
    }

    public function isDownloaded(): bool
    {
        return filled($this->file_path);
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }

    public function humanSize(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'Kb', 'Mb', 'Gb'];
        $power = min((int) floor(log(max($this->file_size, 1), 1024)), count($units) - 1);

        return round($this->file_size / (1024 ** $power)).' '.$units[$power];
    }

    public function extension(): string
    {
        return strtoupper(ltrim((string) $this->file_extension, '.')) ?: 'PDF';
    }

    public function url(): string
    {
        return route('documents.show', [$this->topic, $this]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
