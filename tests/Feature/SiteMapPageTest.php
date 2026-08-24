<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El mapa del sitio, el de las personas.
 *
 * No confundir con /sitemap.xml, que es el de los buscadores. Este es la página
 * que enlaza el pie: el árbol de secciones del portal.
 *
 * Lo que se comprueba aquí es sobre todo que NO se vuelva a ir al portal
 * anterior. El enlace del pie llevaba al mapa del portal de origen, y desde ahí
 * el visitante salía de este sitio sin enterarse: acababa navegando la web
 * vieja creyendo que seguía en la nueva.
 */
class SiteMapPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los temas migrados se guardan en una estática que vive lo que la
        // petición; entre pruebas hay que vaciarla o el árbol sigue creyendo
        // que no se ha importado nada.
        LegacyLink::forget();
    }

    /* ------------------------------------------------------------------ */
    /* El enlace del pie                                                   */
    /* ------------------------------------------------------------------ */

    /** El pie lleva al mapa de este portal, no al del anterior. */
    public function test_el_pie_enlaza_el_mapa_de_este_portal(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('sitemap.page').'"', $html);
        $this->assertStringNotContainsString(
            config('huv.legacy_base').'/mapa-del-sitio',
            $html,
            'El pie sigue enviando al mapa del portal anterior.'
        );
    }

    /* ------------------------------------------------------------------ */
    /* La página                                                           */
    /* ------------------------------------------------------------------ */

    public function test_el_mapa_publica_las_secciones_de_la_barra_y_del_menu_completo(): void
    {
        $respuesta = $this->get(route('sitemap.page'))->assertOk();

        foreach (config('huv.nav') as $seccion) {
            $respuesta->assertSee($seccion['label'], false);

            foreach ($seccion['children'] ?? [] as $hijo) {
                $respuesta->assertSee($hijo['label'], false);
            }
        }

        foreach (config('huv.mega_menu') as $grupo) {
            $respuesta->assertSee($grupo['title'], false);

            foreach ($grupo['links'] as $enlace) {
                $respuesta->assertSee($enlace['label'], false);
            }
        }
    }

    /**
     * Y se mueve sola con la migración.
     *
     * El árbol sale de la misma configuración que el menú, así que un tema
     * recién importado tiene que aparecer aquí apuntando a este aplicativo sin
     * que nadie toque la página.
     */
    public function test_un_tema_importado_deja_de_enlazar_al_portal_anterior(): void
    {
        $legado = config('huv.legacy_base').'/tema/planes';

        $this->get(route('sitemap.page'))->assertOk()->assertSee($legado, false);

        Topic::create([
            'name' => 'Planes',
            'slug' => 'planes',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        LegacyLink::forget();

        $this->get(route('sitemap.page'))
            ->assertOk()
            ->assertDontSee($legado, false)
            ->assertSee(route('topics.show', 'planes'), false);
    }

    /**
     * Los nodos que solo agrupan no son enlaces.
     *
     * El portal los publica como <a href="">, que el navegador entiende como
     * un enlace a la página actual: se pulsa y la página se recarga sin más.
     * Con un lector de pantalla son, además, seis enlaces sin destino en medio
     * de una lista de ciento veintitantos.
     */
    public function test_ningun_nodo_del_arbol_es_un_enlace_sin_destino(): void
    {
        $html = $this->get(route('sitemap.page'))->assertOk()->getContent();

        $arbol = $this->arbol($html);

        $this->assertDoesNotMatchRegularExpression('~<a[^>]*href=""~', $arbol);
        $this->assertDoesNotMatchRegularExpression('~<a(?![^>]*href=)[^>]*>~', $arbol);

        // Y los seis agrupadores siguen ahí, como texto.
        foreach (['Atención y Servicios a la ciudadanía', 'Participa', 'Documentos',
            'Infórmate', 'Nosotros', 'Entidades relacionadas'] as $grupo) {
            $this->assertMatchesRegularExpression(
                '~<span[^>]*>\s*'.preg_quote($grupo, '~').'\s*</span>~u',
                $arbol,
                'El agrupador «'.$grupo.'» no aparece como texto.'
            );
        }
    }

    /** El árbol son listas anidadas de verdad, que es lo que anuncia el nivel. */
    public function test_el_arbol_tiene_sus_tres_niveles_anidados(): void
    {
        $arbol = $this->arbol($this->get(route('sitemap.page'))->assertOk()->getContent());

        // Raíz › sección › enlace de la sección.
        $this->assertMatchesRegularExpression('~<ul>.*<ul>.*<ul>~s', preg_replace('~\s+~', ' ', $arbol) ?? '');
    }

    /* ------------------------------------------------------------------ */
    /* Buscadores                                                          */
    /* ------------------------------------------------------------------ */

    /** La página existe para los buscadores igual que las demás propias. */
    public function test_el_mapa_figura_en_el_sitemap_xml(): void
    {
        $this->get(route('sitemap.section', 'temas'))
            ->assertOk()
            ->assertSee(route('sitemap.page'), false);
    }

    /* ------------------------------------------------------------------ */

    /** El árbol solo, sin la cabecera ni el pie, que también tienen enlaces. */
    private function arbol(string $html): string
    {
        preg_match('~<nav[^>]*class="[^"]*huv-tree.*?</nav>~s', $html, $c);

        $this->assertNotEmpty($c, 'No se encontró el árbol en la página.');

        return $c[0];
    }
}
