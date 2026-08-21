<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use App\Support\SiteSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El buscador.
 *
 * Busca lo mismo que el del portal actual, que se comprobó interrogando su API
 * antes de escribir nada: `/api/v1/contents?keyword=…` devuelve artículos,
 * documentos, enlaces y avisos, y encuentra por el título y por el cuerpo
 * —«Guardian Group», que solo está dentro de una noticia, la saca—. Lo que no
 * busca son los rótulos del menú ni los nombres de las categorías.
 *
 * Aquí el contenido vive en dos tablas, así que lo que hay que comprobar es que
 * las dos se consultan y que sus resultados se mezclan en un solo listado.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function tema(): Topic
    {
        return Topic::firstOrCreate(
            ['slug' => 'presupuesto'],
            ['name' => 'Presupuesto', 'legacy_content_types' => ['Document'], 'imported_at' => now()]
        );
    }

    private function documento(array $overrides = []): TopicItem
    {
        return $this->tema()->items()->create(array_merge([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Acuerdo No.023 Presupuesto 2024',
            'slug' => 'acuerdo-023-'.bin2hex(random_bytes(3)),
            'published_at' => now()->subDays(2),
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* Qué encuentra                                                       */
    /* ------------------------------------------------------------------ */

    public function test_encuentra_por_el_titulo(): void
    {
        $this->noticia();

        $this->get('/buscar?q=donaci%C3%B3n')
            ->assertOk()
            ->assertSee('Jornada de donación de sangre', false);
    }

    /**
     * Y por el cuerpo, que es la mitad del valor de un buscador.
     *
     * En el portal actual, «Guardian Group» solo aparece dentro del texto de una
     * noticia y su buscador la saca. Si aquí solo se mirara el título, quien
     * recuerda una frase del artículo no encontraría nada.
     */
    public function test_encuentra_por_el_cuerpo_y_no_solo_por_el_titulo(): void
    {
        $this->noticia([
            'title' => 'Aseguradores internacionales visitan el hospital',
            'body' => '<p>La visita se hizo en articulación con ProColombia y Guardian Group.</p>',
        ]);

        $this->get('/buscar?q=Guardian+Group')
            ->assertOk()
            ->assertSee('Aseguradores internacionales visitan el hospital', false);
    }

    /** Los documentos de un tema también, no solo las noticias. */
    public function test_encuentra_documentos_de_un_tema(): void
    {
        $this->documento();

        $this->get('/buscar?q=Acuerdo')
            ->assertOk()
            ->assertSee('Acuerdo No.023 Presupuesto 2024', false);
    }

    /** Y los mezcla en un solo listado, ordenado por fecha. */
    public function test_mezcla_noticias_y_documentos_en_un_listado(): void
    {
        $this->noticia(['title' => 'El presupuesto del hospital crece', 'published_at' => now()->subDay()]);
        $this->documento(['title' => 'Acuerdo de presupuesto 2024', 'published_at' => now()->subDays(5)]);

        $html = $this->get('/buscar?q=presupuesto')->assertOk()->getContent();

        $this->assertStringContainsString('El presupuesto del hospital crece', $html);
        $this->assertStringContainsString('Acuerdo de presupuesto 2024', $html);
        // El más reciente primero.
        $this->assertLessThan(
            strpos($html, 'Acuerdo de presupuesto 2024'),
            strpos($html, 'El presupuesto del hospital crece')
        );
    }

    /* ------------------------------------------------------------------ */
    /* Qué NO encuentra                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Todas las palabras, no cualquiera de ellas.
     *
     * Quien escribe dos palabras quiere estrechar la búsqueda. Con un `or`,
     * «acuerdo presupuesto» devolvía más resultados que «acuerdo» a secas, que
     * es justo lo contrario de lo que se pide.
     */
    public function test_exige_todas_las_palabras(): void
    {
        $this->noticia(['title' => 'Acuerdo sobre horarios de atención']);
        $this->documento(['title' => 'Acuerdo de presupuesto 2024']);

        $respuesta = $this->get('/buscar?q=acuerdo+presupuesto')->assertOk();

        $respuesta->assertSee('Acuerdo de presupuesto 2024', false);
        $respuesta->assertDontSee('Acuerdo sobre horarios de atención', false);
    }

    /**
     * Los comodines de LIKE no son comodines para quien busca.
     *
     * `%` significa «cualquier cosa» dentro de un LIKE. Sin escaparlo, buscar
     * «100%» devolvía el portal entero, y un `_` casaba con cualquier letra.
     */
    public function test_los_comodines_se_tratan_como_texto(): void
    {
        $this->noticia(['title' => 'Ocupación al 100% en urgencias']);
        $this->noticia(['title' => 'Nada que ver con la ocupación']);

        $respuesta = $this->get('/buscar?q=100%25')->assertOk();

        $respuesta->assertSee('Ocupación al 100% en urgencias', false);
        $respuesta->assertDontSee('Nada que ver con la ocupación', false);
    }

    /** Lo no publicado no se busca; con sesión iniciada sí. */
    public function test_el_visitante_no_encuentra_lo_que_no_esta_publicado(): void
    {
        $this->noticia(['title' => 'Borrador de presupuesto', 'is_active' => false]);
        $this->noticia(['title' => 'Presupuesto oculto', 'is_hidden' => true]);
        $this->documento(['title' => 'Presupuesto programado', 'published_at' => now()->addWeek()]);

        $publico = $this->get('/buscar?q=presupuesto')->assertOk();

        $publico->assertDontSee('Borrador de presupuesto', false);
        $publico->assertDontSee('Presupuesto oculto', false);
        $publico->assertDontSee('Presupuesto programado', false);

        $editor = $this->actingAs($this->editor())->get('/buscar?q=presupuesto')->assertOk();

        $editor->assertSee('Borrador de presupuesto', false);
        $editor->assertSee('Presupuesto oculto', false);
        $editor->assertSee('Presupuesto programado', false);
    }

    /* ------------------------------------------------------------------ */
    /* La pantalla                                                         */
    /* ------------------------------------------------------------------ */

    /** Sin término no se enseña un listado vacío: se dice qué hacer. */
    public function test_sin_termino_invita_a_escribir(): void
    {
        $this->noticia();

        $this->get('/buscar')
            ->assertOk()
            ->assertSee(__('paginas.buscador.vacio'), false)
            ->assertDontSee('Jornada de donación de sangre', false);
    }

    /** Una sola letra casa con medio portal: no se busca. */
    public function test_una_sola_letra_no_busca(): void
    {
        $this->noticia();

        $this->get('/buscar?q=a')
            ->assertOk()
            ->assertSee(__('paginas.buscador.corto', ['minimo' => SiteSearch::MIN_LENGTH]), false)
            ->assertDontSee('Jornada de donación de sangre', false);
    }

    public function test_sin_resultados_lo_dice_con_el_termino_buscado(): void
    {
        $this->noticia();

        $this->get('/buscar?q=zzzqqxwv')
            ->assertOk()
            ->assertSee('zzzqqxwv', false)
            ->assertDontSee('Jornada de donación de sangre', false);
    }

    /** «1 resultados» es la falta que ve cualquiera que busque algo concreto. */
    public function test_el_recuento_concuerda_en_singular_y_en_plural(): void
    {
        $this->noticia(['title' => 'Un titular irrepetible sobre hemodinamia']);

        // El número va dentro de un <strong>, así que la frase se comprueba a
        // partir de donde acaba la etiqueta.
        $this->get('/buscar?q=hemodinamia')
            ->assertOk()
            ->assertSee('>1</strong> resultado para', false)
            ->assertDontSee('resultados para', false);

        $this->noticia(['title' => 'Otro más sobre hemodinamia', 'published_at' => now()->subDays(3)]);

        $this->get('/buscar?q=hemodinamia')
            ->assertOk()
            ->assertSee('>2</strong> resultados para', false);
    }

    /** El término escrito vuelve al formulario para poder corregirlo. */
    public function test_el_formulario_conserva_lo_que_se_escribio(): void
    {
        $this->get('/buscar?q=quimioterapia')
            ->assertOk()
            ->assertSee('value="quimioterapia"', false);
    }

    /** La lupa de la cabecera lleva al buscador y no a la portada. */
    public function test_la_cabecera_apunta_al_buscador(): void
    {
        $this->get('/')->assertOk()->assertSee('action="'.route('search').'"', false);
    }

    /* ------------------------------------------------------------------ */
    /* Filtros                                                             */
    /* ------------------------------------------------------------------ */

    public function test_se_puede_filtrar_por_tipo(): void
    {
        $this->noticia(['title' => 'Noticia sobre el presupuesto']);
        $this->documento(['title' => 'Documento sobre el presupuesto']);

        $soloContenidos = $this->get('/buscar?q=presupuesto&tipo=contenidos')->assertOk();
        $soloContenidos->assertSee('Noticia sobre el presupuesto', false);
        $soloContenidos->assertDontSee('Documento sobre el presupuesto', false);

        $soloTemas = $this->get('/buscar?q=presupuesto&tipo=temas')->assertOk();
        $soloTemas->assertSee('Documento sobre el presupuesto', false);
        $soloTemas->assertDontSee('Noticia sobre el presupuesto', false);
    }

    public function test_se_puede_filtrar_por_fecha(): void
    {
        $this->noticia(['title' => 'Presupuesto de este año', 'published_at' => now()->subDays(3)]);
        $this->noticia(['title' => 'Presupuesto de hace mucho', 'published_at' => now()->subYears(3)]);

        $respuesta = $this->get('/buscar?q=presupuesto&desde='.now()->subMonth()->format('Y-m-d'))->assertOk();

        $respuesta->assertSee('Presupuesto de este año', false);
        $respuesta->assertDontSee('Presupuesto de hace mucho', false);
    }

    /**
     * Una fecha ilegible se ignora, no revienta.
     *
     * La dirección la puede escribir cualquiera, y `?desde=ayer` no puede ser un
     * error quinientos.
     */
    public function test_una_fecha_ilegible_se_ignora(): void
    {
        $this->noticia(['title' => 'Presupuesto del año']);

        $this->get('/buscar?q=presupuesto&desde=ayer&hasta=nunca')
            ->assertOk()
            ->assertSee('Presupuesto del año', false);
    }

    /* ------------------------------------------------------------------ */
    /* Paginación                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Ningún resultado sale dos veces ni se pierde entre páginas.
     *
     * Los resultados salen de dos tablas y se mezclan por fecha. Con la misma
     * fecha en varias filas y sin un criterio de desempate estable, la base
     * puede devolverlas en distinto orden en cada consulta: una fila aparecería
     * en las dos páginas y otra en ninguna.
     */
    public function test_la_paginacion_no_repite_ni_pierde_resultados(): void
    {
        $mismaFecha = now()->subDay();

        foreach (range(1, 12) as $n) {
            $this->noticia(['title' => 'Presupuesto noticia '.$n, 'published_at' => $mismaFecha]);
            $this->documento(['title' => 'Presupuesto documento '.$n, 'published_at' => $mismaFecha]);
        }

        $vistos = [];

        foreach ([1, 2, 3] as $pagina) {
            $html = $this->get('/buscar?q=presupuesto&page='.$pagina)->assertOk()->getContent();

            foreach (range(1, 12) as $n) {
                foreach (['noticia', 'documento'] as $clase) {
                    $titulo = 'Presupuesto '.$clase.' '.$n;

                    if (str_contains($html, $titulo.'<') || str_contains($html, $titulo.' ')) {
                        $vistos[] = $titulo;
                    }
                }
            }
        }

        $this->assertSame(
            count($vistos),
            count(array_unique($vistos)),
            'Un resultado apareció en más de una página: '.implode(', ', array_diff_assoc($vistos, array_unique($vistos)))
        );
        $this->assertCount(24, array_unique($vistos), 'Se perdió algún resultado entre páginas.');
    }

    /**
     * Con la misma fecha, el orden lo decide el identificador y no la tabla.
     *
     * Es la otra cara de lo anterior, y la que sí se puede comprobar: sin un
     * desempate, la mezcla conserva el orden en que se concatenan las dos
     * consultas y la primera página sale entera de la tabla de contenidos, con
     * los documentos relegados al final aunque sean del mismo día. Que no se
     * repita ningún resultado no lo detecta —la base contesta en orden estable
     * mientras nada cambie—, pero que la página venga de una sola tabla, sí.
     */
    public function test_con_la_misma_fecha_los_dos_origenes_se_intercalan(): void
    {
        $mismaFecha = now()->subDay();

        foreach (range(1, 12) as $n) {
            $this->noticia(['title' => 'Presupuesto noticia '.$n, 'published_at' => $mismaFecha]);
            $this->documento(['title' => 'Presupuesto documento '.$n, 'published_at' => $mismaFecha]);
        }

        $html = $this->get('/buscar?q=presupuesto')->assertOk()->getContent();

        $this->assertStringContainsString('Presupuesto noticia ', $html);
        $this->assertStringContainsString('Presupuesto documento ', $html);
    }
}
