<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentBlock;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * La interfaz en inglés.
 *
 * Lo que se traduce es el andamiaje: menús, botones, rótulos de campo, filtros,
 * mensajes. Aquí se comprueban los sitios donde el texto NO salía de un fichero
 * de idioma y por eso se quedaba en español aunque el visitante pidiera inglés:
 * los rótulos escritos a mano en una plantilla, los que viven en una constante
 * de PHP y —sobre todo— los que estaban dentro del JavaScript, donde no existe
 * `__()` y no hay forma de traducir nada.
 */
class InterfaceLanguageTest extends TestCase
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

    private function noticia(): Content
    {
        return Content::create([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Las pestañas, que vivían dentro del JavaScript                      */
    /* ------------------------------------------------------------------ */

    /**
     * Las pestañas de orden del muro de la portada.
     *
     * Las pinta Alpine con `x-text="option.label"`, así que el rótulo tiene que
     * viajar traducido desde el servidor. Escrito dentro de content-feed.js se
     * quedaba en «Recientes» y «Destacados» al lado de un «Sort by:» inglés.
     */
    public function test_las_pestanas_del_muro_llegan_traducidas_del_servidor(): void
    {
        $this->noticia();

        $this->get('/?idioma=en')
            ->assertOk()
            ->assertSee('"label":"Most recent"', false)
            ->assertSee('"label":"Featured"', false)
            ->assertDontSee('"label":"Recientes"', false);
    }

    /** Y en español siguen siendo las de siempre. */
    public function test_las_pestanas_del_muro_en_espanol(): void
    {
        $this->noticia();

        $this->get('/?idioma=es')
            ->assertOk()
            ->assertSee('"label":"Recientes"', false)
            ->assertDontSee('"label":"Most recent"', false);
    }

    /** Las de moderación solo se montan con sesión iniciada, y también viajan. */
    public function test_las_pestanas_de_moderacion_llegan_traducidas(): void
    {
        $this->noticia();

        $this->actingAs($this->editor())
            ->get('/?idioma=en')
            ->assertOk()
            ->assertSee('"label":"Inactive"', false)
            ->assertSee('"label":"Hidden"', false)
            ->assertDontSee('"label":"Inactivos"', false);
    }

    /* ------------------------------------------------------------------ */
    /* Los rótulos escritos a mano                                         */
    /* ------------------------------------------------------------------ */

    /**
     * El filtro de categorías de la portada.
     *
     * Imprimía el valor crudo de la constante, así que el visitante inglés veía
     * «All / Noticias / Comunicados» y, dos centímetros más abajo, las mismas
     * categorías rotuladas «News» y «Press releases» en cada tarjeta.
     */
    public function test_el_filtro_del_muro_usa_el_rotulo_traducido(): void
    {
        $this->noticia();

        $html = $this->get('/?idioma=en')->assertOk()->getContent();

        // El filtro pasó de categoría a TIPO, que es por lo que filtra el
        // portal de origen. El valor sigue crudo —con él compara Alpine— y lo
        // que se traduce es lo que se lee.
        $this->assertStringContainsString('<option value="Noticia">News</option>', $html);
        $this->assertStringNotContainsString('<option value="Noticia">Noticia</option>', $html);
    }

    /** La ruta de la sección en el editor de bloques estaba escrita a mano. */
    public function test_la_ruta_de_la_seccion_del_bloque_se_traduce(): void
    {
        $bloque = ContentBlock::news();

        $this->actingAs($this->editor())
            ->get(route('admin.blocks.edit', $bloque).'?idioma=en')
            ->assertOk()
            ->assertSee('Home / Get informed / News', false)
            ->assertDontSee('Home / Infórmate /', false);
    }

    /* ------------------------------------------------------------------ */
    /* La sección de la agenda                                             */
    /* ------------------------------------------------------------------ */

    /**
     * El selector nombra los temas con el nombre que tienen en la base.
     *
     * Salían de una constante en español. Ahora salen del tema, que es lo que
     * el visitante ve rotulado así en su propia página, y con la marca de
     * idioma que le corresponde por ser contenido.
     */
    public function test_el_selector_de_la_agenda_nombra_los_temas_de_la_base(): void
    {
        Topic::create([
            'name' => 'Calendario de actividades del hospital',
            'slug' => 'calendario-de-actividades',
            'legacy_content_types' => ['Event'],
            'imported_at' => now(),
        ]);

        $this->actingAs($this->editor())
            ->get('/administracion/eventos/bloque?idioma=en')
            ->assertOk()
            ->assertSee('Calendario de actividades del hospital', false)
            ->assertSee('lang="es"', false);
    }

    /** Un tema que todavía no se ha importado conserva su rótulo de reserva. */
    public function test_un_tema_sin_importar_conserva_el_rotulo_de_reserva(): void
    {
        $this->actingAs($this->editor())
            ->get('/administracion/eventos/bloque')
            ->assertOk()
            ->assertSee(ContentBlock::EVENT_SOURCES['calendario-de-actividades'], false);
    }

    /* ------------------------------------------------------------------ */
    /* Números y contadores                                                */
    /* ------------------------------------------------------------------ */

    /**
     * El separador de millares depende del idioma.
     *
     * «Contrataciones» pasa de setecientas filas y se pagina en el servidor:
     * con el separador fijo en español, el recuento inglés decía «1.234
     * results», que en inglés se lee como un decimal.
     */
    public function test_el_recuento_usa_el_separador_del_idioma(): void
    {
        $topic = Topic::create([
            'name' => 'Contrataciones',
            'slug' => 'contrataciones',
            'legacy_content_types' => ['Link'],
            'imported_at' => now(),
        ]);

        // Mil filas y pico, que es el orden de magnitud real del tema. Con tres
        // el recuento sale «3» en los dos idiomas y la comprobación no podría
        // fallar nunca aunque el separador volviera a estar fijo. Se insertan
        // de golpe: creadas una a una tardan más que toda la suite junta.
        $ahora = now();
        $filas = [];

        foreach (range(1, 1234) as $n) {
            $filas[] = [
                'topic_id' => $topic->id,
                'kind' => TopicItem::KIND_LINK,
                'title' => 'Contrato '.$n,
                'slug' => 'contrato-'.$n,
                'published_at' => $ahora->copy()->subDay(),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        foreach (array_chunk($filas, 400) as $lote) {
            DB::table('topic_items')->insert($lote);
        }

        $this->get(route('topics.show', $topic).'?idioma=en')
            ->assertOk()
            ->assertSee('1,234', false)
            ->assertDontSee('1.234', false);

        $this->get(route('topics.show', $topic).'?idioma=es')
            ->assertOk()
            ->assertSee('1.234', false)
            ->assertDontSee('1,234', false);
    }

    /**
     * El contador de caracteres no puede decir «1 characters left».
     *
     * Lo pinta Alpine sustituyendo `:n` en la cadena, así que una regla de
     * plural de Laravel no llegaría a aplicarse nunca. La frase se redactó de
     * modo que valga para cualquier cifra en los dos idiomas.
     */
    public function test_el_contador_de_caracteres_vale_para_cualquier_cifra(): void
    {
        foreach (['admin-contenidos', 'admin-temas'] as $fichero) {
            foreach (['es' => 'Caracteres restantes: :n', 'en' => 'Characters remaining: :n'] as $idioma => $esperado) {
                app()->setLocale($idioma);
                $this->assertSame($esperado, __($fichero.'.titulo.restantes'));
            }
        }
    }
}
