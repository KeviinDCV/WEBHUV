<?php

namespace App\Models;

use App\Support\CommentWall;
use App\Support\FileSize;
use App\Support\LegacyLink;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Un elemento dentro de un tema: un documento, un artículo…
 *
 * El tipo va en la fila y no en el tema porque los temas del portal los mezclan:
 * «CIAU» tiene documentos, artículos, enlaces y avisos en el mismo listado, y
 * «Notificaciones judiciales» mezcla documentos y artículos entre sus más de
 * cuatrocientos elementos.
 *
 * Los métodos de medios —images(), mainImage(), video(), files(), imageUrl()—
 * llevan a propósito las mismas firmas que Content: de eso depende que el editor
 * de contenidos y su biblioteca de imágenes se reutilicen sin tocar una línea.
 */
class TopicItem extends Model
{
    public const KIND_DOCUMENT = 'documento';

    public const KIND_ARTICLE = 'articulo';

    /** Aviso: solo título y texto, sin archivo ni imagen. */
    public const KIND_NOTICE = 'aviso';

    /**
     * Pregunta frecuente: el título es la pregunta y el cuerpo la respuesta.
     *
     * Se comporta como un aviso —ni archivo, ni fotos, ni fecha de expedición—,
     * pero se guarda aparte para que el editor diga «Pregunta» y no «Noticia»
     * al crear una, que es lo único que las distingue.
     */
    public const KIND_QUESTION = 'pregunta';

    /** Enlace: una ficha breve cuyo detalle completo vive fuera del portal. */
    public const KIND_LINK = 'enlace';

    /**
     * Convocatoria: un proceso con fecha de apertura y de cierre.
     *
     * Cerrada se sigue leyendo —el portal publica las de 2023 junto a las de
     * 2026—, así que su cierre vive en `closes_at` y no en `expires_at`.
     */
    public const KIND_CONVOCATION = 'convocatoria';

    protected $guarded = [];

    /**
     * Dirección que tenía el archivo antes de refrescar los metadatos.
     *
     * Solo la usa la importación, y no se guarda: es una propiedad de verdad y
     * no un atributo, para que Eloquent no intente escribirla en la tabla.
     */
    public ?string $previousFileUrl = null;

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
            'expires_at' => 'datetime',
            'file_size' => 'integer',
            'comment_wall' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_hidden' => 'boolean',
            'legacy_show_on_home' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if (blank($item->slug)) {
                $item->slug = $item->uniqueSlug($item->title);
            }
        });

        // Uno a uno y no en cascada: el borrado en cascada de la base de datos
        // no dispara los eventos de Eloquent y cada medio dejaría su archivo
        // ocupando disco para siempre.
        static::deleting(function (self $item): void {
            $item->media->each->delete();
            $item->deleteFile();
        });
    }

    /** El slug es único dentro del tema, no en todo el portal. */
    public function uniqueSlug(string $from): string
    {
        $base = Str::slug(Str::limit($from, 200, '')) ?: 'contenido';
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

    /* ------------------------------------------------------------------ */
    /* Relaciones */
    /* ------------------------------------------------------------------ */

    /** @return BelongsTo<Topic, self> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsToMany<TopicCategory, self> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TopicCategory::class, 'topic_category_topic_item')->orderBy('name');
    }

    /** @return HasMany<ContentMedia, self> */
    public function media(): HasMany
    {
        return $this->hasMany(ContentMedia::class, 'topic_item_id')
            ->orderByDesc('is_main')
            ->orderBy('position')
            ->orderBy('id');
    }

    /* ------------------------------------------------------------------ */
    /* Tipo */
    /* ------------------------------------------------------------------ */

    public function isDocument(): bool
    {
        return $this->kind === self::KIND_DOCUMENT;
    }

    public function isArticle(): bool
    {
        return $this->kind === self::KIND_ARTICLE;
    }

    public function isNotice(): bool
    {
        return $this->kind === self::KIND_NOTICE;
    }

    public function isLink(): bool
    {
        return $this->kind === self::KIND_LINK;
    }

    public function isQuestion(): bool
    {
        return $this->kind === self::KIND_QUESTION;
    }

    public function isConvocation(): bool
    {
        return $this->kind === self::KIND_CONVOCATION;
    }

    /* ------------------------------------------------------------------ */
    /* Consultas */
    /* ------------------------------------------------------------------ */

    /** @param  Builder<self>  $query */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('is_hidden', false)
            // Lo programado todavía no existe para el público.
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()))
            // Los documentos no llevan caducidad: nula no excluye nada.
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()));
    }

    public function isScheduled(): bool
    {
        return $this->published_at?->isFuture() ?? false;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPublic(): bool
    {
        return $this->is_active
            && ! $this->is_hidden
            && ! $this->isScheduled()
            && ! $this->hasExpired();
    }

    /* ------------------------------------------------------------------ */
    /* Presentación */
    /* ------------------------------------------------------------------ */

    /**
     * Fecha que encabeza la ficha en el listado.
     *
     * La de la última modificación, como en el portal: corregir un documento y
     * que siga anunciando la fecha en que se subió por primera vez engaña
     * —«Informe de Austeridad» se rehízo dos días después de crearse y el
     * portal muestra el día de la corrección—. La de creación se lee en la
     * ficha, junto a esta.
     */
    public function date(): ?Carbon
    {
        return $this->modified_at ?? $this->published_at ?? $this->issued_at ?? $this->created_at;
    }

    /** Resumen en texto plano, para las fichas del listado. */
    /**
     * Resumen para los listados: el cuerpo recortado como lo recorta el portal.
     *
     * Doscientos caracteres, cortando por palabra y sin puntos suspensivos. No
     * es un número elegido a ojo: el portal guarda ese recorte en su
     * `metaDescription` y es lo que pinta en cada tarjeta. Medidos cincuenta
     * contenidos suyos, cuarenta y ocho son prefijo exacto del cuerpo y ninguno
     * pasa de ciento noventa y ocho; los dos que se quedan en ciento cincuenta
     * son cortes ante una palabra larga.
     *
     * Se calcula en cada lectura en vez de guardarse: es texto derivado, y
     * guardarlo dejaría el resumen viejo colgando de un cuerpo ya corregido.
     */
    public function summary(int $characters = 200): string
    {
        return RichText::excerpt($this->body, $characters);
    }

    /**
     * El botón «Participa» del portal, que aparece en todo lo que tiene muro de
     * comentarios, sea público o privado.
     */
    public function invitesParticipation(): bool
    {
        return CommentWall::invites($this->comment_wall);
    }

    /**
     * Adónde lleva la ficha desde el listado.
     *
     * Un enlace en tarjeta es un atajo, no un contenido: en «Población
     * vulnerable» y en «Datos abiertos» la tarjeta entera lleva derecha a su
     * destino, como en el portal. Darle ficha propia sería un clic de más para
     * leer el mismo párrafo que ya se lee en la tarjeta.
     *
     * La excepción es el listado compacto: allí el portal sí le da ficha a cada
     * enlace —«Contrataciones» abre el expediente desde ella— porque la fila no
     * enseña bastante para decidir sin entrar.
     */
    public function url(): string
    {
        if ($this->isLink() && ! $this->topic->isCompactList() && filled($this->source_url)) {
            return LegacyLink::rewrite($this->source_url);
        }

        return route('topics.items.show', [$this->topic, $this]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ------------------------------------------------------------------ */
    /* Medios: mismas firmas que Content */
    /* ------------------------------------------------------------------ */

    /** @return Collection<int, ContentMedia> */
    public function images(): Collection
    {
        return $this->media->where('type', ContentMedia::TYPE_IMAGE)->values();
    }

    public function mainImage(): ?ContentMedia
    {
        return $this->images()->firstWhere('is_main', true) ?? $this->images()->first();
    }

    public function video(): ?ContentMedia
    {
        return $this->media->firstWhere('type', ContentMedia::TYPE_VIDEO);
    }

    /** @return Collection<int, ContentMedia> */
    public function files(): Collection
    {
        return $this->media->where('type', ContentMedia::TYPE_FILE)->values();
    }

    public function imageUrl(): ?string
    {
        return $this->mainImage()?->fileUrl();
    }

    /* ------------------------------------------------------------------ */
    /* Archivo propio del documento */
    /* ------------------------------------------------------------------ */

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
        return FileSize::human($this->file_size);
    }

    public function extension(): string
    {
        return strtoupper(ltrim((string) $this->file_extension, '.')) ?: 'PDF';
    }

    /**
     * Todo lo descargable de un documento, en el orden del origen.
     *
     * El portal publica los archivos de un documento en una sola rejilla, tenga
     * uno o veinticinco. Aquí el primero vive en columnas propias y el resto en
     * `content_media`, pero esa costura es cosa nuestra y no debe asomar en la
     * ficha: se devuelven todos juntos y con la misma forma.
     *
     * @return Collection<int, array{url: string, name: string, extension: string, size: ?string, downloaded: bool}>
     */
    public function attachments(): Collection
    {
        $attachments = $this->files()->map(fn (ContentMedia $file) => [
            'url' => $file->fileUrl(),
            'name' => $file->alt ?: $file->original_name,
            'extension' => $file->extension(),
            'size' => $file->humanSize(),
            'downloaded' => true,
        ]);

        if ($this->isDocument() && filled($this->fileUrl())) {
            $attachments->prepend([
                'url' => $this->fileUrl(),
                'name' => $this->file_name ?: $this->title,
                'extension' => $this->extension(),
                'size' => $this->humanSize(),
                'downloaded' => $this->isDownloaded(),
            ]);
        }

        return $attachments->values();
    }
}
