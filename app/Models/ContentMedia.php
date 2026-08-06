<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContentMedia extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_FILE = 'file';

    /** 30 MB, el mismo tope del portal actual. */
    public const MAX_FILE_KB = 30720;

    public const IMAGE_WIDTH = 1200;

    public const IMAGE_HEIGHT = 768;

    protected $table = 'content_media';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'position' => 'integer',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $media): void {
            $media->deleteFile();
        });
    }

    /** @return BelongsTo<Content, self> */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * Dirección pública del archivo en disco.
     *
     * asset() y no Storage::url(): este último arma la dirección con APP_URL y
     * fallaría si la aplicación se sirve en otro host o puerto.
     */
    public function fileUrl(): ?string
    {
        return $this->path ? asset('storage/'.$this->path) : null;
    }

    /**
     * Borra el archivo del disco, salvo que venga de la biblioteca.
     *
     * Una imagen de la biblioteca puede estar en varios contenidos: borrarla
     * al desvincularla de uno dejaría rota a los demás.
     */
    public function deleteFile(): void
    {
        if ($this->library_image_id !== null) {
            return;
        }

        if ($this->path && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }

    /** Identificador del vídeo dentro de una URL de YouTube. */
    public function youtubeId(): ?string
    {
        if ($this->type !== self::TYPE_VIDEO || blank($this->url)) {
            return null;
        }

        preg_match(
            '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~',
            $this->url,
            $matches
        );

        return $matches[1] ?? null;
    }

    public function humanSize(): ?string
    {
        if (! $this->size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log(max($this->size, 1), 1024)), count($units) - 1);

        return round($this->size / (1024 ** $power), $power > 1 ? 1 : 0).' '.$units[$power];
    }

    public function extension(): string
    {
        return strtoupper(pathinfo((string) $this->original_name, PATHINFO_EXTENSION)) ?: 'Archivo';
    }
}
