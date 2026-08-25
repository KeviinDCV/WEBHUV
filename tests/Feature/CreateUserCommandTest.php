<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

/**
 * El alta de cuentas, «huv:usuario».
 *
 * El portal no tiene registro público: este comando es la única vía de crear un
 * usuario, así que su regla de contraseña es la única que hay.
 *
 * Lo que se comprueba aquí es que la puerta de atrás —--simple, para las
 * cuentas de prueba— siga siendo una puerta de atrás: que haya que pedirla a
 * mano y que sin ella siga en pie la regla de doce caracteres con letras,
 * números y símbolos. El día que alguien la ponga por defecto, estas pruebas se
 * ponen rojas y hay que venir a mirar por qué.
 */
class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    private const CORREO = 'kevin.pruebas@correohuv.gov.co';

    /* Los rótulos de las preguntas, tal cual los escribe el comando. */
    private const NOMBRE = 'Nombre completo';
    private const BUZON = 'Correo institucional';
    private const CLAVE = 'Contraseña';
    private const REPITA = 'Repita la contraseña';
    private const PERMISO = 'Permiso';

    /**
     * El comando con sus respuestas escritas.
     *
     * Bajo pruebas, laravel/prompts no dibuja nada: cae en las preguntas de
     * Symfony, que es lo que `expectsQuestion` sabe contestar. La validación se
     * conserva igual —la aplica el propio respaldo—, con una diferencia que
     * aquí viene bien: en vez de volver a preguntar, aborta.
     *
     * De ahí que cada prueba escriba solo las preguntas que el comando llega a
     * hacer: una respuesta rechazada corta la conversación ahí mismo, y
     * declarar las siguientes haría fallar la prueba por no haberse hecho.
     *
     * @param  list<array{string, string}>  $respuestas  Pregunta y respuesta, en orden.
     */
    private function comando(array $respuestas, bool $simple = false, ?string $rol = null): PendingCommand
    {
        $comando = $this->artisan('huv:usuario', $simple ? ['--simple' => true] : []);

        foreach ($respuestas as [$pregunta, $respuesta]) {
            $comando->expectsQuestion($pregunta, $respuesta);
        }

        // El permiso solo se pregunta cuando la cuenta va a crearse de verdad,
        // así que las pruebas que se cortan antes no lo declaran.
        if ($rol !== null) {
            $comando->expectsChoice(self::PERMISO, $rol, [
                User::ROLE_OPERATOR => 'Operador — edita el portal',
                User::ROLE_ADMIN => 'Administrador — además, cuentas y estadísticas',
            ]);
        }

        return $comando;
    }

    /** Las cuatro preguntas del alta, contestadas de corrido. */
    private function alta(string $clave, ?string $repeticion = null): array
    {
        return [
            [self::NOMBRE, 'Kevin de Pruebas'],
            [self::BUZON, self::CORREO],
            [self::CLAVE, $clave],
            [self::REPITA, $repeticion ?? $clave],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* La regla de siempre                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Por defecto, una contraseña corta no crea nada.
     *
     * El comando se corta en la contraseña, así que «Repita la contraseña» no
     * llega a preguntarse.
     */
    public function test_sin_la_opcion_una_contrasena_corta_se_rechaza(): void
    {
        $this->comando([
            [self::NOMBRE, 'Kevin de Pruebas'],
            [self::BUZON, self::CORREO],
            [self::CLAVE, 'abcd'],
        ])->assertFailed()->run();

        $this->assertDatabaseCount('users', 0);
    }

    /** Ni una larga a la que le falten los símbolos. */
    public function test_sin_la_opcion_tampoco_basta_con_ser_larga(): void
    {
        $this->comando([
            [self::NOMBRE, 'Kevin de Pruebas'],
            [self::BUZON, self::CORREO],
            [self::CLAVE, 'contrasenalarguisima'],
        ])->assertFailed()->run();

        $this->assertDatabaseCount('users', 0);
    }

    /** Y una que cumple, sí. */
    public function test_sin_la_opcion_una_contrasena_que_cumple_crea_la_cuenta(): void
    {
        $this->comando($this->alta('Contrasena-2026!'), rol: User::ROLE_OPERATOR)->assertSuccessful()->run();

        $usuario = User::where('email', self::CORREO)->first();

        $this->assertNotNull($usuario);
        $this->assertSame('Kevin de Pruebas', $usuario->name);
        $this->assertTrue(Hash::check('Contrasena-2026!', $usuario->password));
    }

    /* ------------------------------------------------------------------ */
    /* La puerta de atrás                                                  */
    /* ------------------------------------------------------------------ */

    /** Con --simple valen cuatro caracteres. */
    public function test_con_la_opcion_simple_valen_cuatro_caracteres(): void
    {
        $this->comando($this->alta('1234'), simple: true, rol: User::ROLE_OPERATOR)->assertSuccessful()->run();

        $usuario = User::where('email', self::CORREO)->first();

        $this->assertNotNull($usuario);
        $this->assertTrue(Hash::check('1234', $usuario->password));
    }

    /** Pero avisa de lo que es, para que no se use sin darse cuenta. */
    public function test_la_opcion_simple_deja_aviso(): void
    {
        $this->comando($this->alta('1234'), simple: true, rol: User::ROLE_OPERATOR)
            ->expectsOutputToContain('Sin reglas de contraseña')
            ->assertSuccessful()
            ->run();
    }

    /** Y sin la opción no hay aviso que dar. */
    public function test_sin_la_opcion_no_sale_ningun_aviso(): void
    {
        $this->comando($this->alta('Contrasena-2026!'), rol: User::ROLE_OPERATOR)
            ->doesntExpectOutputToContain('Sin reglas de contraseña')
            ->assertSuccessful()
            ->run();
    }

    /* ------------------------------------------------------------------ */
    /* Lo que la opción NO relaja                                          */
    /* ------------------------------------------------------------------ */

    /** La contraseña sigue sin poder quedarse vacía. */
    public function test_con_la_opcion_simple_la_contrasena_sigue_siendo_obligatoria(): void
    {
        $this->comando([
            [self::NOMBRE, 'Kevin de Pruebas'],
            [self::BUZON, self::CORREO],
            [self::CLAVE, ''],
        ], simple: true)->assertFailed()->run();

        $this->assertDatabaseCount('users', 0);
    }

    /** Repetirla mal sigue sin crear la cuenta. */
    public function test_si_la_repeticion_no_coincide_no_se_crea_la_cuenta(): void
    {
        $this->comando($this->alta('1234', '4321'), simple: true)->assertFailed()->run();

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Y el correo repetido tampoco: es la llave con la que se entra.
     *
     * Aquí el comando se corta en el correo, antes de llegar a la contraseña.
     */
    public function test_el_correo_sigue_siendo_unico(): void
    {
        User::create([
            'name' => 'Ya existe',
            'email' => self::CORREO,
            'password' => Hash::make('Contrasena-2026!'),
        ]);

        $this->comando([
            [self::NOMBRE, 'Kevin de Pruebas'],
            [self::BUZON, self::CORREO],
        ], simple: true)->assertFailed()->run();

        $this->assertDatabaseCount('users', 1);
    }

    /* ------------------------------------------------------------------ */
    /* El permiso                                                          */
    /* ------------------------------------------------------------------ */

    /** Por consola se puede dar de alta un administrador. */
    public function test_por_consola_se_puede_crear_un_administrador(): void
    {
        $this->comando($this->alta('Contrasena-2026!'), rol: User::ROLE_ADMIN)
            ->assertSuccessful()
            ->run();

        $usuario = User::where('email', self::CORREO)->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $usuario->role);
        $this->assertTrue($usuario->isAdmin());
    }

    /** Y el permiso menor es el que sale marcado. */
    public function test_el_permiso_que_viene_marcado_es_el_de_operador(): void
    {
        $this->comando($this->alta('Contrasena-2026!'), rol: User::ROLE_OPERATOR)
            ->assertSuccessful()
            ->run();

        $this->assertFalse(User::where('email', self::CORREO)->firstOrFail()->isAdmin());
    }
}
