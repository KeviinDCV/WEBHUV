<?php

namespace Tests\Feature;

use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La página de Estadísticas, que está en blanco.
 *
 * Existe por el enlace del pie, que llevaba al portal anterior: quien lo
 * pulsaba salía de este sitio sin enterarse. Y está vacía porque este
 * aplicativo no mide el uso —no hay tabla, ni contador, ni analítica— y el
 * portal de origen tampoco enseña nada.
 *
 * Estas pruebas dicen que sigue siendo así a propósito. Si algún día la página
 * tiene cifras, fallarán las dos últimas y ahí se decidirá qué hacer con ellas;
 * eso es justo lo que se quiere de una decisión escrita en una prueba.
 */
class StatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        LegacyLink::forget();
    }

    /** El pie lleva a nuestra página, no a la del portal anterior. */
    public function test_el_pie_enlaza_las_estadisticas_de_este_portal(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('statistics').'"', $html);
        $this->assertStringNotContainsString(
            config('huv.legacy_base').'/estadisticas',
            $html,
            'El pie sigue enviando a las estadísticas del portal anterior.'
        );
    }

    /** La página responde y se identifica, con su título y su ruta. */
    public function test_la_pagina_existe_con_su_titulo_y_su_ruta(): void
    {
        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee(__('paginas.estadisticas.titulo'), false)
            ->assertSee('<h1', false)
            ->assertSee(__('paginas.ruta.etiqueta'), false);
    }

    /**
     * Y no se ofrece a los buscadores.
     *
     * Una página en blanco dentro del índice le dice al buscador que el sitio
     * publica páginas vacías, y eso no se paga solo en esta dirección. Se
     * marca «noindex, follow»: no la indexa, pero sigue los enlaces de la
     * cabecera y del pie, que llevan a todo lo demás.
     */
    public function test_la_pagina_vacia_no_se_ofrece_a_los_buscadores(): void
    {
        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get(route('sitemap.section', 'temas'))
            ->assertOk()
            ->assertDontSee(route('statistics'), false);
    }
}
