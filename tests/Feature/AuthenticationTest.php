<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $password = 'Contrasena-Segura-2026#'): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make($password),
        ]);
    }

    public function test_la_pantalla_de_acceso_se_muestra(): void
    {
        $this->get('/ingresar')
            ->assertOk()
            ->assertSee('Iniciar sesión', false)
            ->assertSee('Correo institucional', false)
            // Una pantalla de acceso no debe indexarse.
            ->assertSee('noindex', false);
    }

    public function test_la_cabecera_enlaza_con_la_pantalla_de_acceso(): void
    {
        $this->get('/')
            ->assertSee('Inicia sesión', false)
            ->assertSee(route('login'), false);
    }

    public function test_se_puede_iniciar_sesion_con_credenciales_correctas(): void
    {
        $user = $this->usuario();

        $this->post('/ingresar', [
            'email' => $user->email,
            'password' => 'Contrasena-Segura-2026#',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_no_se_inicia_sesion_con_contrasena_incorrecta(): void
    {
        $user = $this->usuario();

        $this->post('/ingresar', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_el_mensaje_de_error_no_revela_si_el_correo_existe(): void
    {
        $this->usuario();

        $conCuenta = $this->from('/ingresar')->post('/ingresar', [
            'email' => 'editora@huv.gov.co',
            'password' => 'incorrecta',
        ])->getSession()->get('errors')->first('email');

        $sinCuenta = $this->from('/ingresar')->post('/ingresar', [
            'email' => 'nadie@huv.gov.co',
            'password' => 'incorrecta',
        ])->getSession()->get('errors')->first('email');

        $this->assertSame($conCuenta, $sinCuenta);
    }

    public function test_los_intentos_fallidos_se_limitan(): void
    {
        RateLimiter::clear('editora@huv.gov.co|127.0.0.1');
        $this->usuario();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/ingresar', ['email' => 'editora@huv.gov.co', 'password' => 'mal']);
        }

        $response = $this->post('/ingresar', [
            'email' => 'editora@huv.gov.co',
            'password' => 'Contrasena-Segura-2026#',
        ]);

        // Incluso con la contraseña correcta, el sexto intento queda bloqueado.
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Demasiados intentos',
            (string) session('errors')->first('email')
        );
    }

    public function test_se_puede_cerrar_sesion(): void
    {
        $this->actingAs($this->usuario())
            ->post('/salir')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_no_existe_registro_publico(): void
    {
        $this->get('/registro')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_quien_ya_entro_no_vuelve_a_la_pantalla_de_acceso(): void
    {
        $this->actingAs($this->usuario())
            ->get('/ingresar')
            ->assertRedirect();
    }

    public function test_los_controles_de_edicion_solo_existen_con_sesion_iniciada(): void
    {
        // Visitante anónimo: ni barra de administración ni controles.
        $this->get('/')
            ->assertDontSee('Controles de edición', false)
            ->assertDontSee('data-huv-edit', false)
            ->assertDontSee('Cerrar sesión', false);

        $response = $this->actingAs($this->usuario())->get('/');

        $response->assertSee('Sesión iniciada como', false)
            ->assertSee('Editora del portal', false)
            ->assertSee('Controles de edición', false)
            ->assertSee('Cerrar sesión', false);

        // Un control por cada sección editable de la portada.
        foreach (['banner', 'noticias', 'accesos', 'contenidos', 'eventos', 'boletines', 'entidades'] as $section) {
            $response->assertSee('data-huv-edit="'.$section.'"', false);
        }
    }
}
