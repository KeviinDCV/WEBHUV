<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El esquema de encabezados y los patrones ARIA.
 *
 * Dos defectos que se ven con un lector de pantalla y con ninguna otra cosa: el
 * documento empezaba en H3 —cuatro rótulos del megamenú antes del título de la
 * página— y del H1 se saltaba directamente a los H3 de cada ficha. Y había tres
 * patrones ARIA declarados a medias, que prometen un comportamiento que el
 * código no tiene.
 */
class HeadingsAndAriaTest extends TestCase
{
    use RefreshDatabase;

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Jornada de donacion de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /**
     * Los encabezados en el orden en que aparecen en el documento.
     *
     * @return list<string>
     */
    private function encabezados(string $html): array
    {
        $html = preg_replace('~(?is)<(script|style)\b.*?</\1>~', ' ', $html) ?? '';

        preg_match_all('~(?is)<(h[1-6])[^>]*>~', $html, $c);

        return array_map('strtoupper', $c[1]);
    }

    /* ------------------------------------------------------------------ */
    /* Encabezados                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * El primer encabezado de cualquier página es su H1.
     *
     * Los rótulos de columna del megamenú eran H3 y, como el menú va arriba,
     * salían antes del título en TODAS las páginas: quien navega por
     * encabezados se encontraba cuatro entradas del menú antes de saber en qué
     * página estaba.
     */
    public function test_ninguna_pagina_empieza_por_un_encabezado_que_no_sea_el_h1(): void
    {
        $this->noticia();

        $topic = Topic::create([
            'name' => 'Planes',
            'slug' => 'planes',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Plan anual de adquisiciones',
            'slug' => 'plan-anual',
            'published_at' => now()->subDay(),
        ]);

        $paginas = ['/', route('topics.show', $topic), route('transparency'), '/buscar?q=plan', route('contact')];

        foreach ($paginas as $pagina) {
            $encabezados = $this->encabezados($this->get($pagina)->assertOk()->getContent());

            $this->assertNotEmpty($encabezados, 'Sin encabezados en '.$pagina);
            $this->assertSame('H1', $encabezados[0], 'En '.$pagina.' el primer encabezado es '.$encabezados[0]);
        }
    }

    /**
     * No se salta ningún nivel.
     *
     * Los listados iban del H1 de la página a los H3 de cada ficha, sin nada
     * en medio que dijera qué es esa lista.
     */
    public function test_no_se_salta_ningun_nivel_de_encabezado(): void
    {
        $this->noticia();

        // Un tema que publica CONTENIDOS y otro que publica ARTICULOS: cada uno
        // usa una plantilla distinta, y el salto de nivel estaba en las dos.
        $noticias = Topic::create([
            'name' => 'Noticias',
            'slug' => 'noticias',
            'content_category' => Content::NEWS_CATEGORY,
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $planes = Topic::create([
            'name' => 'Planes',
            'slug' => 'planes',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $planes->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Plan anual de adquisiciones',
            'slug' => 'plan-anual',
            'published_at' => now()->subDay(),
        ]);

        // Sin tilde: la busqueda distingue acentos en SQLite, y con «donacion»
        // no habria resultados, no habria listado y la prueba no miraria nada.
        foreach (['/', route('topics.show', $noticias), route('topics.show', $planes), '/buscar?q=sangre'] as $pagina) {
            $encabezados = $this->encabezados($this->get($pagina)->assertOk()->getContent());

            $previo = 0;

            foreach ($encabezados as $nivel) {
                $n = (int) mb_substr($nivel, 1);

                if ($previo > 0) {
                    $this->assertLessThanOrEqual(
                        $previo + 1,
                        $n,
                        'En '.$pagina.' se salta de H'.$previo.' a '.$nivel.': '.implode(' ', $encabezados)
                    );
                }

                $previo = $n;
            }
        }
    }

    /** Y solo hay un H1 por página. */
    public function test_cada_pagina_tiene_exactamente_un_h1(): void
    {
        $this->noticia();

        foreach (['/', route('transparency'), '/buscar?q=donacion', route('branches')] as $pagina) {
            $encabezados = $this->encabezados($this->get($pagina)->assertOk()->getContent());

            $this->assertSame(
                1,
                count(array_filter($encabezados, fn (string $h): bool => $h === 'H1')),
                'En '.$pagina
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* ARIA                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Nada declara ser una pestaña sin tener su panel.
     *
     * `role="tab"` promete un panel asociado, navegación por flechas y un
     * `aria-controls`. Los botones de orden del muro no tienen nada de eso: son
     * botones de dos estados, y como tales se declaran.
     *
     * El menú de navegación sí implementa el patrón entero —con `tabpanel`,
     * `aria-controls` y teclado—, así que ese se queda.
     */
    public function test_ninguna_pestana_se_declara_sin_su_panel(): void
    {
        $this->noticia();

        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('~role="tab"~', $html, $pestanas);
        preg_match_all('~role="tabpanel"~', $html, $paneles);

        $this->assertLessThanOrEqual(
            count($paneles[0]),
            count($pestanas[0]),
            'Hay más pestañas declaradas que paneles a los que apunten.'
        );
    }

    /** Los botones de orden del muro son botones de dos estados. */
    public function test_el_orden_del_muro_usa_botones_de_dos_estados(): void
    {
        $this->noticia();

        $this->get('/')
            ->assertOk()
            ->assertSee('role="group" aria-labelledby="huv-orden"', false)
            ->assertSee(':aria-pressed="tab === option.key', false);
    }

    /**
     * La barra de accesibilidad tampoco promete lo que no cumple.
     *
     * `role="toolbar"` compromete a mover el foco con las flechas y a que el
     * grupo entero sea una sola parada de tabulación; la barra no hace ni una
     * cosa ni la otra. `aria-orientation` sí se sigue usando —y bien— en el
     * menú de navegación, que implementa el patrón completo, así que no se
     * comprueba sobre la página entera sino sobre la barra.
     */
    public function test_la_barra_de_accesibilidad_no_se_declara_como_toolbar(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('role="toolbar"', $html);

        preg_match('~<div x-data="huvA11y".*?>~s', $html, $barra);

        $this->assertNotEmpty($barra, 'No se encontró la barra de accesibilidad.');
        $this->assertStringContainsString('role="group"', $barra[0]);
        $this->assertStringNotContainsString('aria-orientation', $barra[0]);
    }

    /**
     * El selector de la agenda no recarga al cambiar de opción.
     *
     * Recorrerlo con las flechas disparaba el envío en la primera opción
     * intermedia y devolvía el foco al principio del documento: no había forma
     * de llegar a la que se quería (WCAG 3.2.2).
     */
    public function test_la_agenda_no_recarga_al_cambiar_de_opcion(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('requestSubmit', $html);
        // Y el botón de confirmar se ve siempre, no solo sin JavaScript.
        $this->assertStringNotContainsString('<noscript>', $html);
        $this->assertStringContainsString(__('portada.eventos.aplicar'), $html);
    }

    /* ------------------------------------------------------------------ */
    /* Páginas de error                                                    */
    /* ------------------------------------------------------------------ */

    /** El 404 lleva la cara del portal, no la pantalla de Laravel. */
    public function test_el_404_es_una_pagina_del_portal(): void
    {
        $respuesta = $this->get('/tema/no-existe-xyz')->assertNotFound();

        $respuesta->assertSee(__('paginas.error.404.titulo'), false);
        $respuesta->assertSee('<h1', false);
        // Con salida: el buscador y los enlaces de vuelta.
        $respuesta->assertSee(route('search'), false);
        $respuesta->assertSee('noindex', false);
    }

    /** También una dirección que no casa con ninguna ruta. */
    public function test_una_direccion_desconocida_tambien_lo_es(): void
    {
        $this->get('/esto-no-existe-en-ninguna-parte')
            ->assertNotFound()
            ->assertSee(__('paginas.error.404.titulo'), false);
    }

    /**
     * Y respeta el idioma pedido.
     *
     * El middleware del idioma iba al final del grupo, después de resolver los
     * modelos de la ruta: un enlace roto lanzaba su 404 antes de que llegara a
     * ejecutarse, y la pantalla salía siempre en español.
     */
    public function test_la_pagina_de_error_respeta_el_idioma(): void
    {
        $this->get('/tema/no-existe-xyz?idioma=en')
            ->assertNotFound()
            ->assertSee('This page does not exist', false)
            ->assertSee('<html lang="en"', false);
    }

    /**
     * Y también una dirección que no casa con ninguna ruta.
     *
     * Va en su propia prueba y no junto a la anterior a propósito: dentro de un
     * mismo método, la aplicación y las cookies se comparten entre peticiones,
     * así que el idioma que fija la primera llega a la segunda y la
     * comprobación pasaría aunque la segunda no lo fijara por su cuenta. Con
     * dos métodos son dos aplicaciones, como en un navegador.
     */
    public function test_una_direccion_desconocida_tambien_respeta_el_idioma(): void
    {
        $this->get('/esto-no-existe-en-ninguna-parte?idioma=en')
            ->assertNotFound()
            ->assertSee('This page does not exist', false)
            ->assertSee('<html lang="en"', false);
    }
}
