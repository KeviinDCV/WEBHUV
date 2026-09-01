<?php

namespace App\Models;

use App\Support\Themes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentBlock extends Model
{
    /** Bloque de Noticias de la portada. */
    public const NEWS_KEY = 'noticias';

    /** Bloque de la agenda de eventos. */
    public const EVENTS_KEY = 'eventos';

    /**
     * Secciones de origen que ofrece el bloque de eventos, tal como el portal
     * actual: el calendario de actividades y las etapas de participación.
     *
     * La clave es el tema y el valor su rótulo. Se guarda la clave, no el
     * rótulo: hay DOS temas llamados «Rendición de cuentas» —el de slug
     * «control», que solo admite documentos, y el de slug
     * «rendicion-de-cuentas», que es el bueno— y buscar por nombre devolvía el
     * primero que apareciera. El bloque quedaba leyendo un tema sin eventos y
     * el calendario salía vacío para siempre, sin que nada lo explicara.
     *
     * Además el nombre lo reescribe la importación en cada pasada: si el portal
     * lo cambia, un bloque configurado por nombre se queda sin tema.
     */
    public const EVENT_SOURCES = [
        'calendario-de-actividades' => 'Calendario de actividades',
        'colaboracion-e-innovacion' => 'Colaboración e innovación',
        'consulta-ciudadana' => 'Consulta ciudadana',
        'control-ciudadano' => 'Control ciudadano',
        'diplomados-y-cursos' => 'Oficina Coordinadora Académica',
        'planeacion-presupuesto-participativo' => 'Planeación y presupuesto participativo',
        'rendicion-de-cuentas' => 'Rendición de cuentas',
    ];

    /** El tema que alimenta la agenda por omisión. */
    public const DEFAULT_EVENT_SOURCE = 'calendario-de-actividades';

    /** Secciones que puede combinar un bloque. */
    public const MAX_SECTIONS = 3;

    public const SORTS = [
        'recent' => 'Más reciente',
        'oldest' => 'Más antiguo',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'show_title' => 'boolean',
            'position' => 'integer',
            'options' => 'array',
        ];
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options, $key, $default);
    }

    /**
     * El slug del tema que alimenta la agenda, siempre uno válido.
     *
     * Existe porque un valor equivocado aquí no da error: deja el calendario
     * vacío y sin una sola pista de por qué. Pasó de verdad —la fila guardaba
     * «Calendario de actividades», el NOMBRE del tema, y la agenda busca por
     * slug— y costó un rato entender que había 141 eventos importados y
     * ninguno saliendo.
     *
     * El formulario y su validación son correctos y solo guardan slugs, así
     * que ese valor entró por fuera de la aplicación. Aun así se tolera: si lo
     * que hay es el nombre de un tema conocido, se traduce a su slug; si no se
     * reconoce, se cae al tema por omisión en vez de a la nada.
     */
    public function eventSource(): string
    {
        $guardado = $this->option('source');

        if (is_string($guardado) && array_key_exists($guardado, self::EVENT_SOURCES)) {
            return $guardado;
        }

        // ¿Es el rótulo en vez de la clave?
        $porNombre = array_search($guardado, self::EVENT_SOURCES, true);

        return is_string($porNombre) ? $porNombre : self::DEFAULT_EVENT_SOURCE;
    }

    /** Bloque de la agenda, creándolo con valores por defecto si no existe. */
    public static function events(): self
    {
        return static::firstOrCreate(
            ['key' => self::EVENTS_KEY],
            [
                'name' => 'Eventos',
                'sort' => 'recent',
                'show_title' => true,
                'theme' => 'navy',
                'position' => 2,
                'options' => ['source' => self::DEFAULT_EVENT_SOURCE, 'categories' => []],
            ]
        );
    }

    /** @return HasMany<ContentBlockSection, self> */
    public function sections(): HasMany
    {
        return $this->hasMany(ContentBlockSection::class)->orderBy('position')->orderBy('id');
    }

    public function themeColor(): string
    {
        return Themes::color($this->theme);
    }

    public function isDescending(): bool
    {
        return $this->sort !== 'oldest';
    }

    /**
     * Bloque de Noticias, creándolo con valores por defecto si aún no existe.
     *
     * Así la portada nunca depende de que alguien haya pasado por la pantalla
     * de configuración.
     */
    public static function news(): self
    {
        $block = static::with('sections')->firstOrCreate(
            ['key' => self::NEWS_KEY],
            ['name' => 'Noticias', 'sort' => 'recent', 'show_title' => true, 'theme' => 'navy', 'position' => 1]
        );

        if ($block->sections->isEmpty()) {
            $block->sections()->create([
                'category' => Content::NEWS_CATEGORY,
                'title' => 'Noticias',
                'position' => 1,
            ]);

            $block->load('sections');
        }

        return $block;
    }
}
