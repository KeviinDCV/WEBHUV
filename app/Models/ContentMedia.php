<?php

namespace App\Models;

use App\Support\FileSize;
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
        static::saving(function (self $media): void {
            // Un medio pertenece a un contenido o a un elemento de tema, nunca a
            // los dos ni a ninguno. Si tuviera dos dueños, borrar uno dejaría el
            // archivo colgando del otro; sin dueño, nadie llegaría a borrarlo.
            // La comprobación vive aquí y no en la base de datos porque SQLite
            // acepta un CHECK sin aplicarlo y las pruebas pasarían donde
            // producción falla.
            if (($media->content_id === null) === ($media->topic_item_id === null)) {
                throw new \LogicException('Un medio necesita exactamente un dueño.');
            }
        });

        static::deleted(function (self $media): void {
            $media->deleteFile();
        });
    }

    /** @return BelongsTo<Content, self> */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /** @return BelongsTo<TopicItem, self> */
    public function topicItem(): BelongsTo
    {
        return $this->belongsTo(TopicItem::class);
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
        return FileSize::human($this->size);
    }

    public function extension(): string
    {
        return strtoupper(pathinfo((string) $this->original_name, PATHINFO_EXTENSION)) ?: 'Archivo';
    }
}
