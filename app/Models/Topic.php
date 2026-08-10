<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Topic extends Model
{
    /**
     * Tipos del portal de origen que este aplicativo sabe publicar.
     *
     * Allí existen además Link, Ad, Faq, Event, Poll… Entrarán con su propio
     * `kind` cuando toque; mientras tanto el importador los cuenta y avisa, en
     * lugar de guardarlos como lo que no son.
     */
    private const KIND_BY_LEGACY_TYPE = [
        'Document' => TopicItem::KIND_DOCUMENT,
        'Article' => TopicItem::KIND_ARTICLE,
        'Ad' => TopicItem::KIND_NOTICE,
        'Link' => TopicItem::KIND_LINK,
        'Faq' => TopicItem::KIND_QUESTION,
    ];

    /**
     * Qué es aquí un tipo del portal de origen. Null si no lo publicamos.
     *
     * Único sitio donde vive esta correspondencia. La tuvo también el
     * importador, en un `match` aparte, y al añadir «Faq» se actualizó una de
     * las dos: el tema decía admitir preguntas y el importador se saltaba las
     * once por no conocerlas.
     */
    public static function kindForLegacyType(?string $type): ?string
    {
        return self::KIND_BY_LEGACY_TYPE[$type] ?? null;
    }

    /** Plantilla del portal en la que el orden lo pone quien edita. */
    public const TEMPLATE_SORTABLE = 'Sortable';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'legacy_content_types' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $topic): void {
            if (blank($topic->slug)) {
                $topic->slug = Str::slug($topic->name) ?: 'tema';
            }
        });

        // La cascada de la base de datos no dispara eventos de Eloquent: sin
        // esto, borrar un tema dejaría en disco los archivos y las imágenes de
        // todos sus elementos.
        static::deleting(function (self $topic): void {
            $topic->items->each->delete();
        });
    }

    /** @return HasMany<TopicCategory, self> */
    public function categories(): HasMany
    {
        return $this->hasMany(TopicCategory::class)->orderBy('name');
    }

    /** @return HasMany<TopicItem, self> */
    public function items(): HasMany
    {
        return $this->hasMany(TopicItem::class);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Qué se puede publicar en este tema.
     *
     * Sin declaración del origen se asume documental: es lo único que existía
     * antes de admitir artículos, y así un tema que no diga nada no acaba
     * clasificando sus documentos como otra cosa.
     *
     * @return list<string>
     */
    public function supportedKinds(): array
    {
        $kinds = array_values(array_intersect_key(
            self::KIND_BY_LEGACY_TYPE,
            array_flip((array) ($this->legacy_content_types ?: []))
        ));

        return $kinds ?: [TopicItem::KIND_DOCUMENT];
    }

    public function defaultKind(): string
    {
        return in_array(TopicItem::KIND_DOCUMENT, $this->supportedKinds(), true)
            ? TopicItem::KIND_DOCUMENT
            : $this->supportedKinds()[0];
    }

    /**
     * El portal de origen solo ofrece ordenar por fecha de expedición donde hay
     * documentos: es un dato que los artículos no tienen.
     */
    public function sortsByIssueDate(): bool
    {
        return in_array(TopicItem::KIND_DOCUMENT, $this->supportedKinds(), true);
    }

    /**
     * Tema con orden manual.
     *
     * Quien edita coloca los contenidos en el orden que quiere, y por eso el
     * listado no ofrece ordenar por fecha: haría desaparecer ese trabajo.
     */
    public function isSortable(): bool
    {
        return $this->legacy_template_type === self::TEMPLATE_SORTABLE;
    }

    /**
     * Tema servido por la tabla de contenidos.
     *
     * «Noticias» es el mismo material que el bloque de la portada: una sola
     * copia de cada noticia, dos sitios donde se lee.
     */
    public function isContentBacked(): bool
    {
        return filled($this->content_category);
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Content> */
    public function contents()
    {
        return Content::where('category', $this->content_category);
    }

    /** Cómo se llama en el editor lo que se está creando. */
    public function itemNoun(?string $kind = null): string
    {
        return match ($kind ?? $this->defaultKind()) {
            TopicItem::KIND_DOCUMENT => 'Documento',
            TopicItem::KIND_NOTICE => 'Aviso',
            TopicItem::KIND_LINK => 'Link',
            TopicItem::KIND_QUESTION => 'Pregunta',
            default => 'Noticia',
        };
    }

    /**
     * Tema de enlaces: listado compacto y paginado.
     *
     * El portal les da otra plantilla —filas con una raya en medio y páginas
     * numeradas en vez de «Cargar más»—, y con setecientas contrataciones no es
     * un capricho: imprimirlas todas de golpe haría una página imposible.
     *
     * Queda fuera el tema de enlaces de orden manual, que no es un archivo sino
     * un puñado de atajos colocados a mano: «Población vulnerable» son cuatro
     * tarjetas hacia otros temas. El portal de origen las publica con la misma
     * plantilla de tarjetas que los demás temas, y la fila de filas no le
     * pega ni tendría qué paginar.
     */
    public function isLinkList(): bool
    {
        return $this->supportedKinds() === [TopicItem::KIND_LINK] && ! $this->isSortable();
    }

    /** Cómo se cuentan en el pie del listado. */
    public function itemsNoun(): string
    {
        return $this->supportedKinds() === [TopicItem::KIND_DOCUMENT] ? 'documentos' : 'contenidos';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
