<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * La pantalla del menú.
 *
 * Es la única de la administración que no cuelga de una página del portal: el
 * menú no es de ninguna, es de todas, y por eso se llega desde la barra azul.
 *
 * Lo que se comprueba aquí, además de que se pueda editar, es que la pantalla
 * no se pueda ver sin sesión —desde ella se cambia la navegación del sitio
 * entero— y que guardar deje el portal actualizado a la primera: el menú va
 * cacheado, y sin vaciar la caché al guardar la pantalla parecería rota.
 */
class MenuScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MenuItem::flush();
    }

    private function editora(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function grupo(string $key = 'documentos'): MenuItem
    {
        return MenuItem::query()->where('key', $key)->firstOrFail();
    }

    /* ------------------------------------------------------------------ */
    /* Quién entra                                                         */
    /* ------------------------------------------------------------------ */

    /** Sin sesión no se ve ni se toca. */
    public function test_sin_sesion_no_se_llega_a_la_pantalla(): void
    {
        $this->seed(MenuSeeder::class);

        $this->get(route('admin.menu.index'))->assertRedirect(route('login'));
        $this->post(route('admin.menu.adopt'))->assertRedirect(route('login'));
        $this->delete(route('admin.menu.destroy', $this->grupo()))->assertRedirect(route('login'));

        $this->assertDatabaseHas('menu_items', ['key' => 'documentos']);
    }

    /** Con sesión, el enlace está en la barra de administración. */
    public function test_la_barra_de_administracion_lleva_al_menu(): void
    {
        $this->actingAs($this->editora())
            ->get('/')
            ->assertOk()
            ->assertSee(route('admin.menu.index'), false);
    }

    /* ------------------------------------------------------------------ */
    /* Todavía sin editar                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Con la tabla vacía, la pantalla lo dice y ofrece copiarlo.
     *
     * Entrar a mirar no cambia nada: el volcado se pide a mano.
     */
    public function test_con_la_tabla_vacia_se_ofrece_copiar_la_configuracion(): void
    {
        $this->actingAs($this->editora())
            ->get(route('admin.menu.index'))
            ->assertOk()
            ->assertSee(__('admin-menu.sin_editar.boton'), false);

        $this->assertSame(0, MenuItem::query()->count());
    }

    /** Y al copiarlo, queda el menú entero listo para editar. */
    public function test_copiar_deja_el_menu_en_la_base(): void
    {
        $this->actingAs($this->editora())
            ->post(route('admin.menu.adopt'))
            ->assertRedirect(route('admin.menu.index'));

        $this->assertSame(count(config('huv.nav')), MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')->count());

        $this->assertSame(count(config('huv.mega_menu')), MenuItem::query()
            ->where('area', MenuItem::AREA_MEGA)->whereNull('parent_id')->count());
    }

    /* ------------------------------------------------------------------ */
    /* Editar                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Una entrada nueva sale en el portal en la misma respuesta.
     *
     * Es la prueba del vaciado de caché: el menú se guarda entre peticiones, y
     * sin vaciarlo al escribir la entrada nueva no se vería hasta que la caché
     * caducara. Nadie ata eso a «el editor no funciona», y sin embargo es eso.
     */
    public function test_una_entrada_nueva_se_ve_en_el_portal_al_momento(): void
    {
        $this->seed(MenuSeeder::class);

        $this->actingAs($this->editora());

        // Se pinta la portada primero: así el menú queda cacheado, que es la
        // situación real de cualquier portal en marcha.
        $this->get('/')->assertOk()->assertDontSee('Banco de sangre', false);

        $this->post(route('admin.menu.store'), [
            'area' => MenuItem::AREA_BAR,
            'label' => 'Banco de sangre',
            'destino' => 'interno',
            'path' => '/tema/servicios',
        ])->assertRedirect(route('admin.menu.index'));

        $this->get('/')->assertOk()->assertSee('Banco de sangre', false);
    }

    /** Y al borrarla, desaparece igual de rápido. */
    public function test_borrar_una_entrada_la_quita_del_portal_al_momento(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $entrada = MenuItem::query()->where('label', 'Voluntariados')->firstOrFail();

        $this->get('/')->assertOk()->assertSee('Voluntariados', false);

        // Siguiendo la redirección a propósito: el aviso de «se borró
        // Voluntariados» lleva el rótulo dentro y, si se queda en la sesión,
        // aparece en la siguiente página y la comprobación de abajo daría un
        // falso negativo. Se consume aquí, que además es lo que hace un
        // navegador de verdad.
        $this->followingRedirects()
            ->delete(route('admin.menu.destroy', $entrada))
            ->assertOk();

        $this->get('/')->assertOk()->assertDontSee('Voluntariados', false);
    }

    /** Ocultar no borra: sale del portal y sigue en la pantalla. */
    public function test_ocultar_saca_del_portal_pero_no_de_la_base(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $entrada = MenuItem::query()->where('label', 'Voluntariados')->firstOrFail();

        // Siguiendo la redirección: el aviso lleva el rótulo dentro. Ver la
        // nota de la prueba de borrado.
        $this->followingRedirects()
            ->post(route('admin.menu.toggle', $entrada))
            ->assertOk();

        $this->get('/')->assertOk()->assertDontSee('Voluntariados', false);
        $this->assertDatabaseHas('menu_items', ['id' => $entrada->id, 'is_active' => false]);

        // Y volver a pulsar la devuelve.
        $this->followingRedirects()->post(route('admin.menu.toggle', $entrada))->assertOk();
        $this->get('/')->assertOk()->assertSee('Voluntariados', false);
    }

    /** Subir y bajar intercambian el orden con la vecina. */
    public function test_subir_una_entrada_la_intercambia_con_su_vecina(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $raices = MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')
            ->orderBy('position')->get();

        [$primera, $segunda] = [$raices[0], $raices[1]];

        $this->post(route('admin.menu.move', $segunda), ['direccion' => 'arriba']);

        $this->assertSame($segunda->id, MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')
            ->orderBy('position')->first()?->id);

        $this->assertSame(
            [$primera->position, $segunda->position],
            [$segunda->fresh()->position, $primera->fresh()->position]
        );
    }

    /** La primera no sube más: no hay vecina con la que cambiarse. */
    public function test_la_primera_no_puede_subir_mas(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $primera = MenuItem::query()
            ->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')
            ->orderBy('position')->firstOrFail();

        $antes = MenuItem::query()->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')
            ->orderBy('position')->pluck('id')->all();

        $this->post(route('admin.menu.move', $primera), ['direccion' => 'arriba']);

        $this->assertSame($antes, MenuItem::query()->where('area', MenuItem::AREA_BAR)->whereNull('parent_id')
            ->orderBy('position')->pluck('id')->all());
    }

    /* ------------------------------------------------------------------ */
    /* Lo que el formulario no deja pasar                                  */
    /* ------------------------------------------------------------------ */

    /** Una ruta interna tiene que empezar por «/». */
    public function test_una_ruta_sin_barra_no_se_guarda(): void
    {
        $this->seed(MenuSeeder::class);

        $this->actingAs($this->editora())
            ->post(route('admin.menu.store'), [
                'area' => MenuItem::AREA_BAR,
                'label' => 'Banco de sangre',
                'destino' => 'interno',
                'path' => 'tema/servicios',
            ])
            ->assertSessionHasErrors('path');

        $this->assertDatabaseMissing('menu_items', ['label' => 'Banco de sangre']);
    }

    /** Y una externa tiene que ser una dirección de verdad. */
    public function test_una_direccion_externa_invalida_no_se_guarda(): void
    {
        $this->seed(MenuSeeder::class);

        $this->actingAs($this->editora())
            ->post(route('admin.menu.store'), [
                'area' => MenuItem::AREA_BAR,
                'label' => 'Citas',
                'destino' => 'externo',
                'url' => 'no es una direccion',
            ])
            ->assertSessionHasErrors('url');
    }

    /**
     * Al cambiar de destino, el anterior se vacía.
     *
     * Si no, una entrada que pasa de externa a interna conservaría su 'url', y
     * en el árbol gana la externa: el enlace seguiría llevando al sitio viejo.
     */
    public function test_cambiar_de_destino_borra_el_anterior(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $citas = MenuItem::query()->where('label', 'Citas')->firstOrFail();

        $this->assertNotNull($citas->url);

        // Se mandan LOS DOS campos a propósito, que es lo que hace el
        // formulario: los dos existen siempre en el HTML y Alpine solo los
        // esconde, así que el navegador envía también el destino que se
        // abandona. Mandando solo el nuevo, esta prueba pasaba aunque el
        // controlador no vaciara nada.
        $this->put(route('admin.menu.update', $citas), [
            'label' => 'Citas',
            'destino' => 'interno',
            'path' => '/tema/servicios',
            'url' => 'https://citas.huv.gov.co/login',
        ])->assertRedirect(route('admin.menu.index'));

        $citas->refresh();

        $this->assertNull($citas->url);
        $this->assertSame('/tema/servicios', $citas->path);
    }

    /** El «key» de un grupo no cambia aunque se le cambie el rótulo. */
    public function test_renombrar_un_grupo_no_le_cambia_el_key(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $grupo = $this->grupo();

        $this->put(route('admin.menu.update', $grupo), [
            'label' => 'Documentación institucional',
            'destino' => 'ninguno',
        ])->assertRedirect(route('admin.menu.index'));

        $grupo->refresh();

        $this->assertSame('documentos', $grupo->key);
        $this->assertSame('Documentación institucional', $grupo->label);
    }

    /** Un grupo nuevo recibe un «key» libre, sin chocar con los que hay. */
    public function test_un_grupo_nuevo_recibe_un_key_libre(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $this->post(route('admin.menu.store'), [
            'area' => MenuItem::AREA_MEGA,
            'label' => 'Documentos',
            'destino' => 'ninguno',
        ])->assertRedirect(route('admin.menu.index'));

        $nuevo = MenuItem::query()->where('area', MenuItem::AREA_MEGA)
            ->whereNull('parent_id')->orderByDesc('id')->firstOrFail();

        $this->assertSame('documentos-2', $nuevo->key);
    }

    /** Una entrada dentro de un grupo no lleva «key»: no lo necesita. */
    public function test_una_entrada_dentro_de_un_grupo_no_lleva_key(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $this->post(route('admin.menu.store'), [
            'padre' => $this->grupo()->id,
            'label' => 'Circulares',
            'destino' => 'interno',
            'path' => '/tema/otros',
        ])->assertRedirect(route('admin.menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'label' => 'Circulares',
            'key' => null,
            'parent_id' => $this->grupo()->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* El formulario                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * El formulario de alta se pinta, en sus dos formas.
     *
     * Una sección nueva y una entrada dentro de un grupo no enseñan lo mismo:
     * solo la sección puede no llevar a ninguna parte, y solo ella tiene ajuste
     * de ancho o de columnas.
     */
    public function test_el_formulario_de_alta_se_pinta(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        // Una sección de la barra: con «a ningún sitio» y con el ancho.
        $this->get(route('admin.menu.create', ['area' => MenuItem::AREA_BAR]))
            ->assertOk()
            ->assertSee(__('admin-menu.form.nueva'), false)
            ->assertSee(__('admin-menu.destino.ninguno'), false)
            ->assertSee(__('admin-menu.campo.estrecho'), false);

        // Un grupo del menú completo: con las columnas en vez del ancho.
        $this->get(route('admin.menu.create', ['area' => MenuItem::AREA_MEGA]))
            ->assertOk()
            ->assertSee(__('admin-menu.campo.columnas'), false)
            ->assertDontSee(__('admin-menu.campo.estrecho'), false);

        // Una entrada dentro de un grupo: sin «a ningún sitio», que ahí sería
        // un rótulo muerto en medio del desplegable.
        $this->get(route('admin.menu.create', ['padre' => $this->grupo()->id]))
            ->assertOk()
            ->assertSee(__('admin-menu.form.nueva_en', ['grupo' => 'Documentos']), false)
            ->assertDontSee(__('admin-menu.destino.ninguno'), false);
    }

    /** Un área que no existe no abre formulario ninguno. */
    public function test_un_area_inventada_no_abre_formulario(): void
    {
        $this->actingAs($this->editora())
            ->get(route('admin.menu.create', ['area' => 'inventada']))
            ->assertNotFound();
    }

    /** Y el de edición llega con lo que la entrada ya tiene. */
    public function test_el_formulario_de_edicion_trae_lo_que_hay(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->editora());

        $citas = MenuItem::query()->where('label', 'Citas')->firstOrFail();

        $this->get(route('admin.menu.edit', $citas))
            ->assertOk()
            ->assertSee(__('admin-menu.form.editar', ['rotulo' => 'Citas']), false)
            ->assertSee('value="https://citas.huv.gov.co/login"', false)
            // Con «a otro sitio web» marcado, que es lo que es.
            ->assertSee('value="externo"', false);
    }
}
