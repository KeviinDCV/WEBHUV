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
        'Convocation' => TopicItem::KIND_CONVOCATION,
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

    /**
     * Los tipos que de verdad hay publicados en el tema.
     *
     * No es lo mismo que `supportedKinds()`: «Planeación y presupuesto
     * participativo» declara doce tipos en el origen y solo usa tres. El
     * editor tiene que ofrecer los doce —quien publica puede escribir
     * cualquiera—, pero el filtro del listado no: ofrecer «Convocatoria» donde
     * no hay ninguna es prometer un filtro que siempre devuelve nada.
     *
     * @return list<string>
     */
    public function kindsInUse(): array
    {
        $kinds = $this->items()
            ->distinct()
            ->orderBy('kind')
            ->pluck('kind')
            ->all();

        // En el orden en que se declaran, no alfabético: es el que usa el
        // editor y el que ve quien administra.
        return array_values(array_intersect($this->supportedKinds(), $kinds));
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
            // «Clasificado» es como lo llama el portal en su propio editor, y
            // es el rótulo que ve quien filtra el listado por tipo.
            TopicItem::KIND_NOTICE => 'Clasificado',
            TopicItem::KIND_LINK => 'Link',
            TopicItem::KIND_QUESTION => 'Pregunta',
            TopicItem::KIND_CONVOCATION => 'Convocatoria',
            default => 'Noticia',
        };
    }

    /**
     * Temas que el portal publica en filas y no en tarjetas.
     *
     * Filas con una raya en medio. No se deduce de los datos: en la API,
     * «Contrataciones» y «Datos abiertos» son idénticos —los dos de tipo Link,
     * los dos con plantilla Default, mismos campos— y el portal pinta el
     * primero en filas y el segundo en tarjetas. Tampoco es cuestión de
     * volumen: «Normatividad» son ocho documentos y va en filas; «Presupuesto»
     * son ochenta y cinco y va en tarjetas. Es una decisión suya tema por tema,
     * así que aquí se escribe igual de explícita, por nombre.
     */
    private const ROW_TOPICS = ['contrataciones', 'normatividad'];

    /**
     * De esos, los que además se paginan en el servidor.
     *
     * Es cuestión de volumen, no de aspecto: «Contrataciones» son setecientos
     * registros y no se pueden imprimir todos para decidir después cuáles se
     * ven. «Normatividad» son ocho, y el portal los pagina como a cualquier
     * otro tema —de seis en seis, con «Cargar más»—, así que aquí también.
     */
    private const COMPACT_TOPICS = ['contrataciones'];

    /** Listado en filas y no en tarjetas. */
    public function isRowList(): bool
    {
        return in_array($this->slug, self::ROW_TOPICS, true);
    }

    /** Listado en filas y, además, paginado en el servidor. */
    public function isCompactList(): bool
    {
        return in_array($this->slug, self::COMPACT_TOPICS, true);
    }

    /**
     * Mapa de ubicación del tema, si lo tiene.
     *
     * En el portal es un «bloque» que se cuelga de un tema y se edita desde el
     * panel; en todo el sitio hay uno solo —«Ubicación fisica», colgado de
     * Directorio institucional—, así que aquí se queda en configuración en vez
     * de traerse un sistema de bloques entero para un único caso.
     *
     * @return array{title: string, label: string, address: string, latitude: float, longitude: float, zoom: int}|null
     */
    public function map(): ?array
    {
        return config('huv.maps.'.$this->slug);
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
