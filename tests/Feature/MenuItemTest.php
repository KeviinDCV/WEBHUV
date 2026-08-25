<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use Database\Seeders\MenuSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El menú, ahora que se puede editar sin tocar código.
 *
 * La prueba que sostiene todo el cambio es la tercera: el árbol que sale de la
 * base tiene que ser IDÉNTICO al que salía de la configuración. Mientras eso se
 * cumpla, partials/nav.blade.php —trescientas setenta líneas de desplegables,
 * teclado y estado de Alpine— y LegacyLink siguen valiendo sin tocarlos, que es
 * lo único que hace este cambio asumible. El día que alguien añada un campo con
 * otra forma, se entera aquí y no en producción.
 */
class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El árbol se monta una vez por petición y se guarda en estática;
        // dentro de una prueba, todas comparten proceso.
        MenuItem::forget();
    }

    /**
     * Las claves ordenadas en todos los niveles.
     *
     * La configuración escribe cada entrada en el orden que le vino bien
     * —«key» antes de «i18n» en el menú completo, después en la barra— y eso no
     * significa nada para nadie. Ordenando se puede comparar con assertSame, y
     * assertSame además compara los tipos: un `columns` que llegara como '3' en
     * vez de 3 no pasaría, y con assertEquals sí pasaría.
     */
    private function ordenado(array $arbol): array
    {
        foreach ($arbol as &$valor) {
            if (is_array($valor)) {
                $valor = $this->ordenado($valor);
            }
        }

        unset($valor);
        ksort($arbol);

        return $arbol;
    }

    /* ------------------------------------------------------------------ */
    /* La red: sin filas, manda la configuración                           */
    /* ------------------------------------------------------------------ */

    /**
     * Sin menú en la base, el portal usa el de la configuración.
     *
     * No es un apaño: es lo que hace que un portal recién instalado tenga
     * navegación y que una migración a medias no lo deje sin ella.
     */
    public function test_sin_filas_el_menu_sale_de_la_configuracion(): void
    {
        $this->assertSame(0, MenuItem::query()->count());

        $this->assertSame(config('huv.nav'), MenuItem::bar());
        $this->assertSame(config('huv.mega_menu'), MenuItem::mega());
    }

    /** Y la página se pinta igual. */
    public function test_sin_filas_la_pagina_sigue_teniendo_menu(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Atención y Servicios a la ciudadanía', false)
            ->assertSee('Entidades relacionadas', false);
    }

    /* ------------------------------------------------------------------ */
    /* El árbol de la base es el mismo                                     */
    /* ------------------------------------------------------------------ */

    /** Sembrado, el árbol de la base es el de la configuración, clavado. */
    public function test_el_arbol_de_la_base_es_identico_al_de_la_configuracion(): void
    {
        $this->seed(MenuSeeder::class);
        MenuItem::forget();

        $this->assertSame(
            $this->ordenado(config('huv.nav')),
            $this->ordenado(MenuItem::bar()),
            'La barra que sale de la base ya no es la de la configuración.'
        );

        $this->assertSame(
            $this->ordenado(config('huv.mega_menu')),
            $this->ordenado(MenuItem::mega()),
            'El menú completo que sale de la base ya no es el de la configuración.'
        );
    }

    /** Sembrar dos veces no duplica ni pisa lo ya editado. */
    public function test_sembrar_dos_veces_no_duplica_ni_pisa(): void
    {
        $this->seed(MenuSeeder::class);
        $cuantas = MenuItem::query()->count();

        MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)
            ->whereNull('parent_id')
            ->orderByDesc('position')
            ->first()
            ?->update(['label' => 'Renombrado a mano']);

        $this->seed(MenuSeeder::class);

        $this->assertSame($cuantas, MenuItem::query()->count());
        $this->assertDatabaseHas('menu_items', ['label' => 'Renombrado a mano']);
    }

    /* ------------------------------------------------------------------ */
    /* Editar de verdad                                                    */
    /* ------------------------------------------------------------------ */

    /** Una entrada nueva sale en la barra sin tocar una línea de código. */
    public function test_una_entrada_nueva_aparece_en_la_barra_y_en_el_mapa(): void
    {
        $this->seed(MenuSeeder::class);

        MenuItem::create([
            'area' => MenuItem::AREA_BAR,
            'label' => 'Banco de sangre',
            'path' => '/tema/servicios',
            'position' => 99,
        ]);

        MenuItem::forget();

        $this->get('/')->assertOk()->assertSee('Banco de sangre', false);
        $this->get(route('sitemap.page'))->assertOk()->assertSee('Banco de sangre', false);
    }

    /** Y una apagada desaparece de la página sin borrarla. */
    public function test_una_entrada_apagada_desaparece_de_la_pagina(): void
    {
        $this->seed(MenuSeeder::class);

        MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)
            ->whereNull('parent_id')
            ->where('label', 'Normatividad')
            ->update(['is_active' => false]);

        MenuItem::forget();

        $barra = collect(MenuItem::bar());

        $this->assertNull($barra->firstWhere('label', 'Normatividad'));
        $this->assertDatabaseHas('menu_items', ['label' => 'Normatividad', 'is_active' => false]);
    }

    /** Apagar un grupo esconde también lo que cuelga de él. */
    public function test_apagar_un_grupo_esconde_tambien_lo_que_cuelga(): void
    {
        $this->seed(MenuSeeder::class);

        MenuItem::query()->where('key', 'documentos')->update(['is_active' => false]);
        MenuItem::forget();

        $mega = collect(MenuItem::mega());

        $this->assertNull($mega->firstWhere('key', 'documentos'));
        $this->assertSame(count(config('huv.mega_menu')) - 1, $mega->count());
    }

    /** Borrar un grupo borra sus entradas: sueltas no significan nada. */
    public function test_borrar_un_grupo_borra_sus_entradas(): void
    {
        $this->seed(MenuSeeder::class);

        $grupo = MenuItem::query()->where('key', 'documentos')->firstOrFail();

        $this->assertGreaterThan(0, $grupo->children()->count());

        $grupo->delete();

        $this->assertSame(0, MenuItem::query()->where('parent_id', $grupo->id)->count());
    }

    /* ------------------------------------------------------------------ */
    /* Lo que no puede pasar                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Dos entradas no pueden compartir «key».
     *
     * La usan los ids del DOM y el estado de Alpine: con dos iguales, abrir un
     * desplegable abriría los dos y el aria-controls apuntaría a un id
     * repetido.
     */
    public function test_dos_entradas_no_pueden_compartir_key(): void
    {
        $this->seed(MenuSeeder::class);

        $this->expectException(QueryException::class);

        MenuItem::create([
            'area' => MenuItem::AREA_MEGA,
            'key' => 'documentos',
            'label' => 'Otro documentos',
            'position' => 99,
        ]);
    }

    /** El destino externo se sirve como externo, igual que en la configuración. */
    public function test_una_entrada_externa_se_sirve_como_externa(): void
    {
        $this->seed(MenuSeeder::class);
        MenuItem::forget();

        $atencion = collect(MenuItem::bar())->firstWhere('key', 'atencion');
        $citas = collect($atencion['children'])->firstWhere('label', 'Citas');

        $this->assertSame('https://citas.huv.gov.co/login', $citas['url']);
        $this->assertArrayNotHasKey('path', $citas);
    }
}
