<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La opción del menú que corresponde a la página abierta.
 *
 * El portal la resalta en azul, y era lo único que decía dónde estaba uno.
 * Aquí venía escrita a mano en la configuración —«Inicio» con `active => true`—,
 * así que salía resaltada en todas las páginas del sitio: al entrar en
 * Normatividad, el menú seguía diciendo que se estaba en la portada.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LegacyLink::forget();
    }

    private function normatividad(): Topic
    {
        return Topic::create([
            'name' => 'Normatividad',
            'slug' => 'normatividad',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);
    }

    /** Cuántas opciones del menú de escritorio salen marcadas. */
    private function marcadas(string $html): array
    {
        preg_match_all(
            '~<a[^>]*aria-current="page"[^>]*>\s*([^<]+?)\s*</a>~s',
            $html,
            $m
        );

        return array_map('trim', $m[1]);
    }

    public function test_en_la_portada_se_marca_inicio(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(['Inicio', 'Inicio'], $this->marcadas($html), 'Escritorio y móvil.');
    }

    public function test_en_un_tema_del_menu_se_marca_ese_tema_y_no_inicio(): void
    {
        $topic = $this->normatividad();

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        $marcadas = $this->marcadas($html);

        $this->assertSame(['Normatividad'], $marcadas);
        $this->assertNotContains('Inicio', $marcadas);
    }

    /**
     * Un tema que cuelga de un desplegable no marca nada.
     *
     * Comprobado en el portal: en «/tema/entidad», que se alcanza desde el menú
     * de las tres rayas, no hay ninguna opción resaltada. Marcar el padre sería
     * inventarse un comportamiento que allí no existe.
     */
    public function test_un_tema_que_no_esta_en_la_barra_no_marca_nada(): void
    {
        $topic = Topic::create([
            'name' => 'Entidad',
            'slug' => 'entidad',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        $this->assertSame([], $this->marcadas($html));
    }

    /**
     * Un destino de fuera nunca es la página abierta.
     *
     * Aunque su ruta coincida: «https://citas.huv.gov.co/login» no es nuestro
     * «/login», y compararlas sin mirar el dominio resaltaría la opción
     * equivocada.
     */
    public function test_un_enlace_externo_no_se_marca_por_coincidir_la_ruta(): void
    {
        $this->assertFalse(LegacyLink::isCurrent([
            'label' => 'Citas',
            'url' => 'https://citas.huv.gov.co'.request()->getPathInfo(),
        ]));
    }

    /**
     * Un tema que cuelga de un desplegable marca ese desplegable.
     *
     * Aquí el portal se pierde: en «Diagnóstico e Identificación de problemas»,
     * que es hija de «Participa», resalta «Inicio» —donde no estás—, y en
     * «Entidad», que cuelga del menú de las tres rayas, no resalta nada.
     * Marcar el desplegable al que pertenece la página es lo único que de
     * verdad dice dónde está uno, y es lo que se pidió al reportar el fallo.
     */
    public function test_un_tema_de_un_desplegable_marca_su_desplegable(): void
    {
        $topic = Topic::create([
            'name' => 'Diagnóstico e Identificación de problemas',
            'slug' => 'diagnostico-e-identificacion-de-problemas',
            'legacy_content_types' => ['Ad'],
            'imported_at' => now(),
        ]);

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        // El botón del desplegable, que no es un enlace: lleva `aria-current`
        // con «true» y no con «page», porque la página no es él.
        preg_match_all('~<button[^>]*aria-current="true"[^>]*>\s*([^<]+?)\s*<~s', $html, $m);

        $this->assertSame(['Participa'], array_map('trim', $m[1]));

        // Y «Inicio» sigue sin marcarse: no es donde estás.
        $this->assertNotContains('Inicio', $this->marcadas($html));
    }
}
