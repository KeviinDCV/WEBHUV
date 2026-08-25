<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\RecordVisit;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * El recuento de visitas y la pantalla que lo enseña.
 *
 * Lo que se pidió es un promedio de cuánta gente entra, así que eso es lo que
 * más se comprueba: que el promedio se calcule sobre TODOS los días del periodo
 * y no solo sobre los que tuvieron gente, porque de la otra forma el portal
 * parecería tener más público cuanto más vacío estuviera.
 *
 * Y se comprueba con el mismo cuidado lo que NO se cuenta: la administración,
 * quien tiene la sesión iniciada, los rastreadores y todo lo que no sea una
 * página. Una estadística que cuenta de más miente igual que una que cuenta de
 * menos, solo que da más confianza.
 */
class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private function administradora(): User
    {
        return User::create([
            'name' => 'Administradora',
            'email' => 'administradora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function operadora(): User
    {
        return User::create([
            'name' => 'Operadora',
            'email' => 'operadora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
            'role' => User::ROLE_OPERATOR,
        ]);
    }

    /** Una visita ya registrada, del día que se diga. */
    private function visita(string $visitante, string $path = '/', int $haceDias = 0): Visit
    {
        $dia = Carbon::today()->subDays($haceDias);

        return Visit::create([
            // Con md5 y no rellenando con ceros: «visitante-1» rellenado hasta
            // 32 y «visitante-10» rellenado hasta 32 son la MISMA cadena, y
            // diez visitantes se convertían en nueve sin que se notara.
            'visitor' => substr(md5($visitante), 0, 32),
            'path' => $path,
            'visited_on' => $dia->toDateString(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Qué se cuenta                                                       */
    /* ------------------------------------------------------------------ */

    /** Ver una página del portal cuenta como visita. */
    public function test_ver_una_pagina_queda_anotado(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame(1, Visit::query()->count());
        $this->assertSame('/', Visit::query()->value('path'));
    }

    /**
     * Dos páginas del mismo navegador son una sola visita.
     *
     * Es lo que separa «cuánta gente entra» de «cuántas páginas se ven», que es
     * justo lo que se pidió.
     */
    public function test_dos_paginas_del_mismo_navegador_son_una_sola_visita(): void
    {
        // Se llama al middleware con DOS peticiones que comparten sesión, en
        // vez de hacer dos $this->get(). Motivo: el cliente de pruebas no
        // guarda la cookie de una petición para la siguiente y no hay forma
        // limpia de devolvérsela —va cifrada—, así que cada get() sería un
        // navegador distinto y la prueba no probaría lo que dice probar.
        //
        // Lo que se comprueba es lo que importa: que la identidad de una visita
        // sale de la SESIÓN y no de la petición.
        $id = str_repeat('a1b2c3d4', 5);

        $sesion = $this->app['session']->driver();
        $sesion->setId($id);
        $sesion->start();

        $middleware = new RecordVisit;

        foreach (['/', '/transparencia'] as $path) {
            $peticion = Request::create($path, 'GET');
            $peticion->setLaravelSession($sesion);

            $middleware->terminate(
                $peticion,
                new Response('<html lang="es"></html>', 200, ['Content-Type' => 'text/html; charset=utf-8'])
            );
        }

        $this->assertSame(2, Visit::query()->count());
        $this->assertSame(1, Visit::query()->distinct()->count('visitor'));

        // Y ese identificador es justo el de la sesión de hoy.
        $this->assertSame(
            Visit::visitorHash($id, Carbon::today()),
            Visit::query()->value('visitor')
        );
    }

    /* ------------------------------------------------------------------ */
    /* Qué NO se cuenta                                                    */
    /* ------------------------------------------------------------------ */

    /** Quien edita el portal no es su público. */
    public function test_no_se_cuenta_a_quien_tiene_la_sesion_iniciada(): void
    {
        $this->actingAs($this->operadora())->get('/')->assertOk();

        $this->assertSame(0, Visit::query()->count());
    }

    /** Ni las páginas de administración. */
    public function test_no_se_cuenta_la_administracion(): void
    {
        $this->actingAs($this->administradora())->get(route('admin.statistics.index'))->assertOk();

        $this->assertSame(0, Visit::query()->count());
    }

    /** Ni los rastreadores, que además no devuelven la cookie. */
    public function test_no_se_cuentan_los_rastreadores(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
            ->get('/')
            ->assertOk();

        $this->assertSame(0, Visit::query()->count());
    }

    /** Ni un enlace roto: un 404 no es una visita. */
    public function test_no_se_cuenta_un_404(): void
    {
        $this->get('/esto-no-existe-en-ninguna-parte')->assertNotFound();

        $this->assertSame(0, Visit::query()->count());
    }

    /** Ni lo que no es una página, como el mapa para buscadores. */
    public function test_no_se_cuenta_lo_que_no_es_una_pagina(): void
    {
        $this->get('/robots.txt')->assertOk();
        $this->get(route('sitemap.index'))->assertOk();

        $this->assertSame(0, Visit::query()->count());
    }

    /* ------------------------------------------------------------------ */
    /* Privacidad                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * No se guarda nada que identifique a nadie, y el identificador de un día
     * no sirve para el siguiente.
     */
    public function test_no_se_puede_seguir_a_nadie_de_un_dia_para_otro(): void
    {
        $sesion = 'una-sesion-cualquiera';

        $hoy = Visit::visitorHash($sesion, Carbon::today());
        $manana = Visit::visitorHash($sesion, Carbon::tomorrow());

        $this->assertNotSame($hoy, $manana);

        // Y el mismo navegador, el mismo día, siempre cae en el mismo valor:
        // sin eso no se podrían contar personas.
        $this->assertSame($hoy, Visit::visitorHash($sesion, Carbon::today()));

        // La tabla no tiene dónde guardar una IP ni un navegador.
        $this->get('/')->assertOk();

        $columnas = array_keys(Visit::query()->firstOrFail()->getAttributes());

        foreach (['ip', 'ip_address', 'user_agent', 'agent', 'referer'] as $prohibida) {
            $this->assertNotContains($prohibida, $columnas);
        }
    }

    /* ------------------------------------------------------------------ */
    /* El promedio, que es lo que se pidió                                 */
    /* ------------------------------------------------------------------ */

    /**
     * El promedio se reparte entre TODOS los días del periodo.
     *
     * Diez visitas en un solo día, mirando la última semana, son 1,4 al día y
     * no 10: dividir solo entre los días con gente daría un promedio más alto
     * cuanto más vacío estuviera el portal, que es exactamente al revés.
     */
    public function test_el_promedio_cuenta_tambien_los_dias_sin_nadie(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->visita('visitante-'.$i, '/', haceDias: 2);
        }

        $respuesta = $this->actingAs($this->administradora())
            ->get(route('admin.statistics.index', ['dias' => 7]))
            ->assertOk();

        // 10 visitas ÷ 7 días = 1,4
        $respuesta->assertSee('1,4', false);
    }

    /** Y el total del periodo es la suma de los días. */
    public function test_las_cifras_del_periodo_cuadran(): void
    {
        $this->visita('ana', '/', haceDias: 1);
        $this->visita('ana', '/transparencia', haceDias: 1);
        $this->visita('luis', '/', haceDias: 1);
        $this->visita('sara', '/', haceDias: 40);

        $desde = Carbon::today()->subDays(6);
        $hasta = Carbon::today();

        $porDia = Visit::perDay($desde, $hasta);

        // Siete días, aunque seis estén vacíos.
        $this->assertCount(7, $porDia);
        $this->assertSame(2, $porDia->sum('visitantes'));
        $this->assertSame(3, $porDia->sum('paginas'));

        // La de hace cuarenta días queda fuera del periodo.
        $this->assertSame(1, Visit::perDay(Carbon::today()->subDays(60), $hasta)->sum('visitantes') - 2);
    }

    /** Las páginas más vistas salen ordenadas. */
    public function test_las_paginas_mas_vistas_salen_ordenadas(): void
    {
        $this->visita('ana', '/transparencia');
        $this->visita('luis', '/transparencia');
        $this->visita('sara', '/');

        $top = Visit::topPaths(Carbon::today()->subDays(6), Carbon::today());

        $this->assertSame('/transparencia', $top->first()['path']);
        $this->assertSame(2, $top->first()['paginas']);
    }

    /* ------------------------------------------------------------------ */
    /* Quién puede mirar                                                   */
    /* ------------------------------------------------------------------ */

    public function test_solo_el_administrador_ve_las_estadisticas(): void
    {
        $this->get(route('admin.statistics.index'))->assertRedirect(route('login'));

        $this->actingAs($this->operadora())
            ->get(route('admin.statistics.index'))
            ->assertForbidden();

        $this->actingAs($this->administradora())
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertSee(__('admin-estadisticas.promedio.titulo'), false);
    }

    /** Sin datos todavía, la pantalla lo dice en vez de enseñar ceros a secas. */
    public function test_sin_datos_la_pantalla_lo_explica(): void
    {
        $this->actingAs($this->administradora())
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertSee(__('admin-estadisticas.vacio.titulo'), false);
    }

    /** Un periodo inventado cae en el de por defecto, no revienta. */
    public function test_un_periodo_inventado_cae_en_el_de_por_defecto(): void
    {
        $this->actingAs($this->administradora())
            ->get(route('admin.statistics.index', ['dias' => 9999]))
            ->assertOk()
            ->assertSee(__('admin-estadisticas.promedio.pie', ['dias' => 30]), false);
    }

    /** Y la letra pequeña está siempre: sin ella, «visitas» se lee como «personas». */
    public function test_la_pantalla_explica_que_cuenta_de_verdad(): void
    {
        $this->actingAs($this->administradora())
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertSee(__('admin-estadisticas.letra_pequena.titulo'), false)
            ->assertSee(__('admin-estadisticas.letra_pequena.cookie'), false)
            ->assertSee(__('admin-estadisticas.letra_pequena.privacidad'), false);
    }

    /* ------------------------------------------------------------------ */
    /* Que no lo pueda inflar cualquiera                                   */
    /* ------------------------------------------------------------------ */

    /**
     * El recuento tiene tope por origen.
     *
     * Sin él, cualquiera desde fuera podía escribir filas en bucle: quien no
     * manda la cookie estrena sesión en cada petición, así que cada una contaba
     * como visitante nuevo, y con eso se infla el promedio y se llena la tabla.
     * Lo encontró una revisión adversarial de la propia pantalla.
     */
    public function test_el_recuento_tiene_tope_por_origen(): void
    {
        $middleware = new RecordVisit;

        $anotar = function () use ($middleware): void {
            $sesion = $this->app['session']->driver();
            $sesion->setId(str_repeat('b', 40));
            $sesion->start();

            $peticion = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.7']);
            $peticion->setLaravelSession($sesion);

            $middleware->terminate(
                $peticion,
                new Response('<html lang="es"></html>', 200, ['Content-Type' => 'text/html; charset=utf-8'])
            );
        };

        // Se agota el cubo del minuto a mano y se comprueba que deja de anotar.
        RateLimiter::increment('visitas:'.sha1('203.0.113.7'), 60, RecordVisit::MAX_POR_MINUTO);

        $anotar();

        $this->assertSame(0, Visit::query()->count(), 'Anotó pese a estar el origen en el tope.');

        // Y desde otro origen se sigue anotando: el tope no apaga el recuento.
        RateLimiter::clear('visitas:'.sha1('203.0.113.8'));

        $sesion = $this->app['session']->driver();
        $sesion->setId(str_repeat('c', 40));
        $sesion->start();

        $otra = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.8']);
        $otra->setLaravelSession($sesion);

        $middleware->terminate(
            $otra,
            new Response('<html lang="es"></html>', 200, ['Content-Type' => 'text/html; charset=utf-8'])
        );

        $this->assertSame(1, Visit::query()->count());
    }

    /** No se guarda la dirección de origen, solo se usa para el tope. */
    public function test_el_origen_se_usa_para_el_tope_pero_no_se_guarda(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/')->assertOk();

        $fila = Visit::query()->firstOrFail();

        foreach ($fila->getAttributes() as $valor) {
            $this->assertStringNotContainsString('203.0.113.9', (string) $valor);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Retención                                                           */
    /* ------------------------------------------------------------------ */

    /** Lo viejo se puede purgar sin tocar lo reciente. */
    public function test_la_purga_borra_lo_viejo_y_respeta_lo_reciente(): void
    {
        $this->visita('antigua', '/', haceDias: 600);
        $this->visita('reciente', '/', haceDias: 3);

        $this->artisan('huv:visitas-purgar', ['--dias' => 550])->assertSuccessful();

        $this->assertSame(1, Visit::query()->count());
        $this->assertSame(
            substr(md5('reciente'), 0, 32),
            Visit::query()->value('visitor')
        );
    }
}
