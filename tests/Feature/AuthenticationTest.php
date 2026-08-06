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
        foreach (['banner', 'noticias', 'contenidos', 'eventos', 'boletines', 'entidades'] as $section) {
            $response->assertSee('data-huv-edit="'.$section.'"', false);
        }
    }

    public function test_todo_control_de_edicion_queda_dentro_del_alcance_de_alpine(): void
    {
        \App\Models\Content::create([
            'title' => 'Noticia de ejemplo',
            'category' => \App\Models\Content::NEWS_CATEGORY,
            'published_at' => now()->subHour(),
        ]);

        $html = $this->actingAs($this->usuario())->get('/')->getContent();

        // Alpine solo inicializa los árboles que cuelgan de un x-data. Sin uno
        // en el <body>, los `x-show` que viven fuera de un componente —los
        // botones de edición de cada sección— se quedan ocultos por su x-cloak
        // y no hay forma de mostrarlos.
        $this->assertMatchesRegularExpression('/<body[^>]*\sx-data(\s|=|>)/', $html);

        // Y ninguno debe quedar antes de la apertura del <body>.
        $bodyAt = strpos($html, '<body');
        preg_match_all('/x-show="\$store\.huvUi/', substr($html, 0, $bodyAt), $orphans);
        $this->assertEmpty($orphans[0]);
    }

    public function test_cada_barra_de_accesos_tiene_su_propio_control(): void
    {
        $block = \App\Models\ShortcutBlock::create(['name' => 'Barra de prueba', 'position' => 1]);

        // Una barra vacía no se pinta, así que necesita al menos un acceso.
        $block->shortcuts()->create([
            'label' => 'Citas', 'icon' => 'calendar-check',
            'url' => 'https://citas.huv.gov.co/login', 'position' => 1,
        ]);

        $this->get('/')->assertDontSee('data-huv-edit="accesos-', false);

        $this->actingAs($this->usuario())->get('/')
            ->assertSee('data-huv-edit="accesos-'.$block->id.'"', false);
    }
}
