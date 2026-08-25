<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Una entrada del menú: la barra de la cabecera o el menú del botón ☰.
 *
 * Lo que hace barato este cambio es que `tree()` devuelve EXACTAMENTE la misma
 * forma de array que `config('huv.nav')` y `config('huv.mega_menu')`. Por eso
 * partials/nav.blade.php —trescientas sesenta líneas de desplegables, teclado y
 * estado de Alpine— no se toca, y LegacyLink tampoco: solo cambia de dónde sale
 * el array. Quien añada campos aquí tiene que respetar esa forma o irá a tocar
 * las dos vistas.
 *
 * Sin filas, el menú es el de la configuración. No es un apaño: es lo que hace
 * que el portal recién instalado tenga navegación, que una migración a medias
 * no lo deje sin ella, y que las pruebas que recorren la configuración sigan
 * valiendo. La configuración pasa a ser la semilla y la red; la base manda en
 * cuanto tiene algo que decir.
 *
 * Pendiente para cuando exista el editor: el rótulo de una entrada creada a
 * mano no se traduce. Las que vienen del portal llevan en 'i18n' la clave de su
 * traducción escrita a mano y se resuelven por ahí. Para las nuevas haría falta
 * el rasgo Translatable, y ahí hay una trampa que conviene saber de antemano:
 * ConfigLabel::marked() decide si marcar el texto como español comparando el
 * rótulo devuelto con `$entry['label']`, así que si se mete aquí el rótulo ya
 * traducido, lo marcaría como español justo cuando deja de serlo.
 */
class MenuItem extends Model
{
    /** La barra de navegación de la cabecera. */
    public const AREA_BAR = 'bar';

    /** El menú completo que abre el botón ☰. */
    public const AREA_MEGA = 'mega';

    /** @var list<string> */
    public const AREAS = [self::AREA_BAR, self::AREA_MEGA];

    /**
     * Entradas que caben en la barra en una sola línea.
     *
     * Con la séptima, la barra se parte en dos renglones y la cabecera da un
     * salto al cambiar de página. No se impone aquí —el modelo no debe decidir
     * eso— pero el editor tiene que avisar, como avisa el de accesos directos.
     */
    public const MAX_BAR_ITEMS = 6;

    protected $guarded = [];

    /** El árbol ya montado, una vez por petición y por área. */
    private static array $cache = [];

    /**
     * El menú se pinta en TODAS las páginas.
     *
     * Con la configuración no costaba nada porque era un array del fichero; con
     * la base son dos consultas por página, así que se cachea. Y por eso mismo
     * hay que vaciarla al guardar: sin esto, el editor guarda bien y la página
     * sigue enseñando lo de antes, que es la forma más rápida de que alguien
     * decida que el editor no funciona.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::flush());
        static::deleted(fn () => self::flush());
    }

    protected function casts(): array
    {
        return [
            'narrow' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'columns' => 'integer',
        ];
    }

    /** @return BelongsTo<self, self> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, self> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /* ------------------------------------------------------------------ */
    /* El árbol que leen las vistas                                        */
    /* ------------------------------------------------------------------ */

    /** La barra de la cabecera. */
    public static function bar(): array
    {
        return self::tree(self::AREA_BAR);
    }

    /** El menú del botón ☰. */
    public static function mega(): array
    {
        return self::tree(self::AREA_MEGA);
    }

    public static function tree(string $area): array
    {
        return self::$cache[$area] ??= Cache::rememberForever(
            self::cacheKey($area),
            fn (): array => self::build($area)
        );
    }

    /** Vacía el menú guardado, en la petición y entre peticiones. */
    public static function flush(): void
    {
        self::forget();

        foreach (self::AREAS as $area) {
            Cache::forget(self::cacheKey($area));
        }
    }

    private static function cacheKey(string $area): string
    {
        return 'menu.'.$area;
    }

    /**
     * Solo para las pruebas y para el editor: obliga a volver a montarlo.
     *
     * El menú se pinta en todas las páginas, así que se monta una vez por
     * petición. Dentro de una prueba, dos peticiones comparten el proceso.
     */
    public static function forget(): void
    {
        self::$cache = [];
    }

    /* ------------------------------------------------------------------ */

    private static function build(string $area): array
    {
        // Una sola consulta con el área entera —ciento treinta filas contando
        // las sesenta y seis entidades— y el árbol se arma en memoria. Traer
        // también las ocultas es a propósito: son la diferencia entre «no hay
        // menú en la base» y «está todo apagado», y no se puede distinguir
        // filtrando en la consulta.
        $todas = self::query()
            ->where('area', $area)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($todas->isEmpty()) {
            return self::fromConfig($area);
        }

        $porPadre = $todas
            ->where('is_active', true)
            ->groupBy(fn (self $item): int => (int) $item->parent_id);

        return $porPadre
            ->get(0, new Collection)
            ->map(fn (self $item): array => self::node(
                $item,
                $area,
                $porPadre->get($item->id, new Collection)
            ))
            ->values()
            ->all();
    }

    /**
     * Una entrada con la forma que espera la vista.
     *
     * Las claves ausentes importan tanto como las presentes: LegacyLink decide
     * con `isset($link['url'])` y la vista con `! empty($item['narrow'])`, así
     * que un 'url' => null no es lo mismo que no tener 'url'. Por eso se añaden
     * una a una y no de golpe.
     *
     * @param  Collection<int, self>  $hijos
     */
    private static function node(self $item, string $area, Collection $hijos): array
    {
        $esGrupo = $item->parent_id === null;
        $nodo = [];

        if (filled($item->i18n)) {
            $nodo['i18n'] = $item->i18n;
        }

        // Los grupos del menú completo llaman 'title' a su rótulo y 'links' a
        // sus hijos; todo lo demás, 'label' y 'children'. Es la forma que ya
        // tenía la configuración y que la vista da por hecha.
        $enMega = $esGrupo && $area === self::AREA_MEGA;

        $nodo[$enMega ? 'title' : 'label'] = $item->label;

        if (filled($item->key)) {
            $nodo['key'] = $item->key;
        }

        if (filled($item->url)) {
            $nodo['url'] = $item->url;
        } elseif (filled($item->path)) {
            $nodo['path'] = $item->path;
        }

        if ($item->narrow) {
            $nodo['narrow'] = true;
        }

        if ($item->columns !== null) {
            $nodo['columns'] = $item->columns;
        }

        if ($enMega || $hijos->isNotEmpty()) {
            $nodo[$enMega ? 'links' : 'children'] = $hijos
                ->map(fn (self $hijo): array => self::node($hijo, $area, new Collection))
                ->values()
                ->all();
        }

        return $nodo;
    }

    /* ------------------------------------------------------------------ */
    /* Ayudas del editor                                                   */
    /* ------------------------------------------------------------------ */

    /** ¿El menú se está sirviendo todavía de la configuración? */
    public static function isEmpty(string $area): bool
    {
        return ! self::query()->where('area', $area)->exists();
    }

    /**
     * Un «key» libre a partir del rótulo.
     *
     * Solo lo llevan los grupos: de él cuelgan los ids del DOM y el estado de
     * Alpine. Se calcula una vez, al crear, y ya no se vuelve a tocar aunque
     * después se renombre el grupo, porque cambiarlo rompería el aria-controls
     * del desplegable y el del mapa del sitio.
     */
    public static function freeKey(string $label): string
    {
        $base = Str::slug($label) ?: 'grupo';
        $key = $base;
        $n = 2;

        while (self::query()->where('key', $key)->exists()) {
            $key = $base.'-'.$n++;
        }

        return $key;
    }

    /** ¿Es un grupo, o sea, algo de lo que cuelgan entradas? */
    public function isGroup(): bool
    {
        return $this->parent_id === null;
    }

    /** El destino tal como lo verá el visitante. */
    public function destination(): ?string
    {
        return $this->url ?: $this->path;
    }

    private static function fromConfig(string $area): array
    {
        return $area === self::AREA_BAR
            ? (array) config('huv.nav')
            : (array) config('huv.mega_menu');
    }
}
