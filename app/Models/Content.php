<?php

namespace App\Models;

use App\Support\FeedType;

use App\Support\CommentWall;
use App\Support\RichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\Concerns\Translatable;

class Content extends Model
{
    use Translatable;

    /** Campos con texto de lectura que se sirven traducidos. */
    protected array $translatable = ['title', 'excerpt', 'body'];

    /** Categorías disponibles. */
    public const CATEGORIES = [
        'Noticias',
        'Notificaciones Judiciales',
        'Comunicados',
    ];

    /**
     * Cómo se lee la categoría en pantalla.
     *
     * El valor guardado no se traduce: es el dato con el que se consulta la
     * tabla y con el que la importación decide dónde va cada contenido. Lo que
     * se traduce es el rótulo, y si falta la traducción se cae al propio valor,
     * que en español ya es el texto bueno.
     */
    public static function categoryLabel(?string $category): string
    {
        if (blank($category)) {
            return '';
        }

        $clave = 'mensajes.categorias.'.Str::slug($category, '_');

        return __($clave) === $clave ? $category : __($clave);
    }

    /** Categoría que alimenta el bloque de Noticias de la portada. */
    public const NEWS_CATEGORY = 'Noticias';

    /** Titulares compactos junto a la nota destacada. */
    public const NEWS_SIDEBAR_LIMIT = 4;

    public const IMAGE_WIDTH = 1000;

    public const IMAGE_HEIGHT = 500;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_hidden' => 'boolean',
            'show_in_feed' => 'boolean',
            'comment_wall' => 'integer',
        ];
    }

    /**
     * El botón «Participa» del portal, que aparece en todo lo que tiene muro de
     * comentarios, sea público o privado.
     */
    public function invitesParticipation(): bool
    {
        return CommentWall::invites($this->comment_wall);
    }

    protected static function booted(): void
    {
        static::saving(function (self $content): void {
            if (blank($content->slug)) {
                $content->slug = $content->uniqueSlug($content->title);
            }
        });

        // El borrado en cascada de la base de datos no dispara los eventos de
        // Eloquent, así que los medios se eliminan uno a uno para que cada uno
        // borre su archivo del disco.
        static::deleting(function (self $content): void {
            $content->media->each->delete();
        });
    }

    public function uniqueSlug(string $from): string
    {
        $base = Str::slug(Str::limit($from, 120, '')) ?: 'contenido';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->whereKeyNot($this->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /* ------------------------------------------------------------------ */
    /* Consultas */
    /* ------------------------------------------------------------------ */

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Dentro de su ventana de publicación: ya empezó y todavía no ha caducado.
     */
    public function scopePublished(Builder $query): void
    {
        $query
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /** Caducado: tenía fecha final y ya pasó. */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Visible para el público: activa, publicada y no ocultada. */
    public function scopeOnHome(Builder $query): void
    {
        $query->active()->published()->where('is_hidden', false);
    }

    /**
     * Más reciente primero.
     *
     * Un contenido «sin fecha de visualización» no tiene published_at, así que
     * se ordena por su fecha de creación para que no caiga siempre al final.
     */
    public function scopeRecent(Builder $query, bool $descending = true): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $query->orderByRaw('COALESCE(published_at, created_at) '.$direction)->orderBy('id', $direction);
    }

    /** @param  Builder<self>  $query */
    public function scopeNews(Builder $query): void
    {
        $query->where('category', self::NEWS_CATEGORY);
    }

    /* ------------------------------------------------------------------ */
    /* Presentación */
    /* ------------------------------------------------------------------ */

    /* ------------------------------------------------------------------ */
    /* Medios */
    /* ------------------------------------------------------------------ */

    /** @return HasMany<ContentMedia, self> */
    public function media(): HasMany
    {
        return $this->hasMany(ContentMedia::class)->orderByDesc('is_main')->orderBy('position')->orderBy('id');
    }

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

    public function files(): Collection
    {
        return $this->media->where('type', ContentMedia::TYPE_FILE)->values();
    }

    public function imageUrl(): ?string
    {
        return $this->mainImage()?->fileUrl();
    }

    /**
     * Etiquetas del tema «Noticias».
     *
     * Son las mismas categorías que usan los temas documentales, porque en el
     * portal de origen es la misma cosa: una etiqueta colgada de un tema.
     *
     * @return BelongsToMany<TopicCategory, self>
     */
    public function topicCategories(): BelongsToMany
    {
        return $this->belongsToMany(TopicCategory::class, 'content_topic_category')->orderBy('name');
    }

    /* ------------------------------------------------------------------ */
    /* Fechas y enlaces */
    /* ------------------------------------------------------------------ */

    /** Fecha que se muestra; nula si se publicó «sin fecha de visualización». */
    public function displayDate(): ?Carbon
    {
        return $this->published_at;
    }

    /* ------------------------------------------------------------------ */
    /* El muro de la portada                                               */
    /* ------------------------------------------------------------------ */

    /**
     * El tipo con el que este contenido se filtra en el muro.
     *
     * El muro mezcla contenidos y elementos de tema, igual que el portal de
     * origen, y su filtro es por TIPO y no por categoría. Todo lo que vive en
     * `contents` vino de un «Article» del origen —noticias, comunicados y
     * notificaciones judiciales son la misma cosa con etiqueta distinta—, así
     * que aquí todos son «Noticia». La categoría se sigue viendo en la tarjeta.
     */
    public function feedType(): string
    {
        return FeedType::NEWS;
    }

    /**
     * Clave única dentro del muro.
     *
     * Hace falta porque el muro mezcla dos tablas y los identificadores se
     * repiten: el contenido 5 y el elemento de tema 5 existen los dos.
     */
    public function feedKey(): string
    {
        return 'c-'.$this->id;
    }

    /** El rótulo que la tarjeta pinta encima del título. */
    public function feedLabel(): string
    {
        return self::categoryLabel($this->category);
    }

    /** Programado: tiene fecha, pero todavía no ha llegado. */
    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Destino del contenido: su propia página, salvo que apunte fuera.
     */
    public function url(): string
    {
        return $this->link ?: route('contents.show', $this->slug);
    }

    public function isExternal(): bool
    {
        return filled($this->link);
    }

    /**
     * Resumen para los listados: el propio o el cuerpo recortado.
     *
     * El recorte es el del portal —doscientos caracteres, por palabra y sin
     * puntos suspensivos—, igual que en TopicItem::summary(), donde está
     * explicado de dónde sale el número.
     */
    public function summary(int $limit = 200): string
    {
        if (filled($this->excerpt)) {
            return $this->excerpt;
        }

        return RichText::excerpt($this->body, $limit);
    }
}
