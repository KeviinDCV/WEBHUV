<?php

namespace Tests\Feature\Admin;

use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Los dos roles: operador y administrador.
 *
 * La línea que los separa es una sola: el operador administra el CONTENIDO del
 * portal y el administrador además la HERRAMIENTA —quién entra—.
 *
 * Lo que se comprueba aquí es sobre todo lo que NO puede el operador, y por la
 * puerta de atrás: no basta con que no vea el enlace, tiene que estrellarse
 * contra un 403 al escribir la dirección a mano. Esconder un botón no es un
 * permiso.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'Contrasena-Segura-2026#';

    protected function setUp(): void
    {
        parent::setUp();

        MenuItem::flush();
    }

    private function usuario(string $rol, string $correo = 'quien@huv.gov.co'): User
    {
        return User::create([
            'name' => $rol === User::ROLE_ADMIN ? 'Administradora' : 'Operadora',
            'email' => $correo,
            'password' => Hash::make(self::CLAVE),
            'role' => $rol,
        ]);
    }

    private function operadora(): User
    {
        return $this->usuario(User::ROLE_OPERATOR, 'operadora@huv.gov.co');
    }

    private function administradora(): User
    {
        return $this->usuario(User::ROLE_ADMIN, 'administradora@huv.gov.co');
    }

    /* ------------------------------------------------------------------ */
    /* El rol en sí                                                        */
    /* ------------------------------------------------------------------ */

    /** Una cuenta nueva es operadora salvo que se diga lo contrario. */
    public function test_el_permiso_menor_es_el_de_por_defecto(): void
    {
        $sinDecirlo = User::create([
            'name' => 'Sin rol declarado',
            'email' => 'sinrol@huv.gov.co',
            'password' => Hash::make(self::CLAVE),
        ]);

        $this->assertSame(User::ROLE_OPERATOR, $sinDecirlo->fresh()->role);
        $this->assertFalse($sinDecirlo->fresh()->isAdmin());
    }

    /* ------------------------------------------------------------------ */
    /* Lo que el operador SÍ puede: todo lo de antes                       */
    /* ------------------------------------------------------------------ */

    /**
     * El operador conserva lo que se podía hacer antes de que hubiera roles.
     *
     * Es la mitad que se olvida al repartir permisos: comprobar que el rol
     * nuevo no le ha quitado nada a quien ya trabajaba.
     */
    public function test_el_operador_sigue_administrando_el_contenido(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->operadora());

        foreach ([
            route('admin.menu.index'),
            route('admin.banners.index'),
            route('admin.contents.create'),
        ] as $pantalla) {
            $this->get($pantalla)->assertOk();
        }
    }

    /** Y puede seguir editando el menú, que es contenido del portal. */
    public function test_el_operador_puede_editar_el_menu(): void
    {
        $this->seed(MenuSeeder::class);

        $this->actingAs($this->operadora())
            ->post(route('admin.menu.store'), [
                'area' => MenuItem::AREA_BAR,
                'label' => 'Banco de sangre',
                'destino' => 'interno',
                'path' => '/tema/servicios',
            ])
            ->assertRedirect(route('admin.menu.index'));

        $this->assertDatabaseHas('menu_items', ['label' => 'Banco de sangre']);
    }

    /* ------------------------------------------------------------------ */
    /* Lo que el operador NO puede                                         */
    /* ------------------------------------------------------------------ */

    /** Las cuentas no son suyas: 403, no una página en blanco. */
    public function test_el_operador_no_llega_a_las_cuentas(): void
    {
        $this->actingAs($this->operadora());

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
    }

    /**
     * Y tampoco por la puerta de atrás: el POST también está cerrado.
     *
     * Es el fallo clásico de repartir permisos mirando solo las pantallas: se
     * protege el formulario y se deja abierto el sitio donde se guarda.
     */
    public function test_el_operador_no_puede_crear_una_cuenta_ni_enviando_el_formulario(): void
    {
        $this->actingAs($this->operadora());

        $this->post(route('admin.users.store'), [
            'name' => 'Colada por la puerta de atrás',
            'email' => 'colada@huv.gov.co',
            'password' => self::CLAVE,
            'password_confirmation' => self::CLAVE,
            'role' => User::ROLE_ADMIN,
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'colada@huv.gov.co']);
    }

    /** Ni ve el enlace, que es lo de menos pero también cuenta. */
    public function test_el_operador_no_ve_el_bloque_del_administrador(): void
    {
        $this->seed(MenuSeeder::class);

        $this->actingAs($this->operadora())
            ->get(route('admin.menu.index'))
            ->assertOk()
            ->assertDontSee(__('estructura.admin.solo_administrador'), false)
            ->assertDontSee(route('admin.users.index'), false);
    }

    /* ------------------------------------------------------------------ */
    /* Lo que el administrador sí puede                                    */
    /* ------------------------------------------------------------------ */

    public function test_la_administradora_ve_el_bloque_y_llega_a_las_cuentas(): void
    {
        $this->seed(MenuSeeder::class);
        $this->actingAs($this->administradora());

        $this->get(route('admin.menu.index'))
            ->assertOk()
            ->assertSee(__('estructura.admin.solo_administrador'), false)
            ->assertSee(route('admin.users.index'), false);

        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.users.create'))->assertOk();
    }

    public function test_la_administradora_crea_una_cuenta(): void
    {
        $this->actingAs($this->administradora());

        $this->post(route('admin.users.store'), [
            'name' => 'Nueva operadora',
            'email' => 'nueva@huv.gov.co',
            'password' => 'Contrasena-Nueva-2026#',
            'password_confirmation' => 'Contrasena-Nueva-2026#',
            'role' => User::ROLE_OPERATOR,
        ])->assertRedirect(route('admin.users.index'));

        $nueva = User::where('email', 'nueva@huv.gov.co')->firstOrFail();

        $this->assertSame(User::ROLE_OPERATOR, $nueva->role);
        $this->assertTrue(Hash::check('Contrasena-Nueva-2026#', $nueva->password));
    }

    /* ------------------------------------------------------------------ */
    /* Sin sesión                                                          */
    /* ------------------------------------------------------------------ */

    /** Sin entrar, ni 403 ni nada: al acceso, como el resto de la administración. */
    public function test_sin_sesion_se_va_al_acceso(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------ */
    /* Lo que el formulario no deja pasar                                  */
    /* ------------------------------------------------------------------ */

    /**
     * En la web no valen las contraseñas flojas.
     *
     * El comando de consola tiene `--simple` para las cuentas de prueba; una
     * pantalla abierta al hospital es otra cosa y aquí manda la regla larga.
     */
    public function test_la_pantalla_no_admite_una_contrasena_floja(): void
    {
        $this->actingAs($this->administradora())
            ->post(route('admin.users.store'), [
                'name' => 'Con clave floja',
                'email' => 'floja@huv.gov.co',
                'password' => '1234',
                'password_confirmation' => '1234',
                'role' => User::ROLE_OPERATOR,
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'floja@huv.gov.co']);
    }

    /** Ni una repetición que no coincide. */
    public function test_la_repeticion_tiene_que_coincidir(): void
    {
        $this->actingAs($this->administradora())
            ->post(route('admin.users.store'), [
                'name' => 'Mal repetida',
                'email' => 'mal@huv.gov.co',
                'password' => 'Contrasena-Nueva-2026#',
                'password_confirmation' => 'Otra-Cosa-2026#',
                'role' => User::ROLE_OPERATOR,
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'mal@huv.gov.co']);
    }

    /** Ni un correo que ya tiene cuenta. */
    public function test_el_correo_sigue_siendo_unico(): void
    {
        $this->actingAs($this->administradora());

        $this->post(route('admin.users.store'), [
            'name' => 'Repetida',
            'email' => 'administradora@huv.gov.co',
            'password' => 'Contrasena-Nueva-2026#',
            'password_confirmation' => 'Contrasena-Nueva-2026#',
            'role' => User::ROLE_OPERATOR,
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'administradora@huv.gov.co')->count());
    }

    /** Y un rol inventado tampoco entra. */
    public function test_un_rol_inventado_no_se_guarda(): void
    {
        $this->actingAs($this->administradora())
            ->post(route('admin.users.store'), [
                'name' => 'Con rol raro',
                'email' => 'raro@huv.gov.co',
                'password' => 'Contrasena-Nueva-2026#',
                'password_confirmation' => 'Contrasena-Nueva-2026#',
                'role' => 'superusuario',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'raro@huv.gov.co']);
    }
}
