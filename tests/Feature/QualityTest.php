<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Los arreglos de la auditoría de calidad.
 *
 * Todos venían de defectos medidos sobre el sitio corriendo, y ninguno estaba
 * cubierto: la suite entera seguía en verde con la portada sin encabezado
 * principal, con las setenta y una páginas del listado de contrataciones
 * declarándose duplicado de la primera, y con el buscador abierto sin tope.
 */
class QualityTest extends TestCase
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

    private function tema(string $slug, string $nombre, ?string $descripcion = null): Topic
    {
        return Topic::create([
            'name' => $nombre,
            'slug' => $slug,
            'description' => $descripcion,
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Encabezados                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * La portada declara de qué trata.
     *
     * Era la única de las quince páginas del sitio sin <h1>, y justo la que más
     * peso tiene. Va oculto a la vista porque el portal no lo dibuja.
     */
    public function test_la_portada_tiene_un_unico_encabezado_principal(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'La portada debe tener exactamente un <h1>.');
        $this->assertStringContainsString(config('huv.institution.name_plain'), $html);
    }

    /** Y en inglés sigue marcado como español, que es lo que es. */
    public function test_el_encabezado_de_la_portada_declara_su_idioma_en_ingles(): void
    {
        $this->get('/?idioma=en')
            ->assertOk()
            ->assertSee('<h1 lang="es" class="sr-only">', false);
    }

    /**
     * El panel no imprime un encabezado vacío.
     *
     * La carga masiva trae su propio <h1> y no define la sección del layout, así
     * que salían dos encabezados de nivel uno y el primero sin texto.
     */
    public function test_la_carga_masiva_no_imprime_un_encabezado_vacio(): void
    {
        $topic = $this->tema('planes', 'Planes');

        $html = $this->actingAs($this->editor())
            ->get(route('admin.topics.bulk.create', $topic))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'Solo debe haber un <h1>.');
        $this->assertDoesNotMatchRegularExpression('~<h1[^>]*>\s*</h1>~', $html);
    }

    /* ------------------------------------------------------------------ */
    /* Metadatos                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * La canónica conserva la página y descarta lo demás.
     *
     * Sin el número de página, las setenta y una del listado de contrataciones
     * se declaraban duplicado de la primera y los cientos de registros que solo
     * viven de la segunda en adelante quedaban fuera del índice. Los filtros sí
     * deben colapsar: son recortes del mismo listado.
     */
    public function test_la_canonica_conserva_la_pagina_pero_no_los_filtros(): void
    {
        $topic = $this->tema('contrataciones', 'Contrataciones');

        foreach (range(1, 3) as $n) {
            $topic->items()->create([
                'kind' => TopicItem::KIND_LINK,
                'title' => 'Contrato '.$n,
                'slug' => 'contrato-'.$n,
                'published_at' => now()->subDay(),
            ]);
        }

        $limpia = route('topics.show', $topic);

        $this->get($limpia.'?page=3')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$limpia.'?page=3"', false);

        // Un filtro no crea una página nueva: colapsa a la dirección limpia.
        $this->get($limpia.'?orden=az')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$limpia.'"', false);

        // Y la primera página tampoco lleva sufijo.
        $this->get($limpia.'?page=1')
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$limpia.'"', false);
    }

    /**
     * Sin <meta keywords>.
     *
     * Google la ignora desde 2009 y Bing la trata como señal de spam cuando es
     * idéntica en las más de dos mil páginas del sitio.
     */
    public function test_no_se_emite_la_etiqueta_de_palabras_clave(): void
    {
        $this->get('/')->assertOk()->assertDontSee('name="keywords"', false);
    }

    /** La descripción cabe en lo que enseña un buscador. */
    public function test_la_meta_descripcion_no_pasa_de_155_caracteres(): void
    {
        foreach (['es', 'en'] as $idioma) {
            $html = $this->get('/?idioma='.$idioma)->assertOk()->getContent();

            preg_match('~<meta name="description" content="([^"]*)">~', $html, $c);

            $this->assertNotEmpty($c, 'Falta la meta descripción en '.$idioma);
            $this->assertLessThanOrEqual(
                155,
                mb_strlen(html_entity_decode($c[1])),
                'La meta descripción en '.$idioma.' se recortaría en el resultado.'
            );
        }
    }

    /**
     * Una descripción de tres palabras no vale como meta descripción.
     *
     * «Notificaciones Judiciales» trae veinticinco caracteres del portal: en la
     * página de resultados no dice nada, y sale mejor la frase armada con el
     * nombre del tema y el del hospital.
     */
    public function test_una_descripcion_corta_cede_al_texto_de_reserva(): void
    {
        $corta = $this->tema('avisos', 'Avisos', 'Avisos del hospital.');
        $larga = $this->tema('planes', 'Planes', str_repeat('Planes institucionales del hospital. ', 3));

        $this->assertNull($corta->metaDescription());
        $this->assertNotNull($larga->metaDescription());

        $this->get(route('topics.show', $corta))
            ->assertOk()
            ->assertDontSee('content="Avisos del hospital."', false);

        $this->get(route('topics.show', $larga))
            ->assertOk()
            ->assertSee('Planes institucionales del hospital.', false);
    }

    /* ------------------------------------------------------------------ */
    /* Contraste                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Los dos tonos de texto llegan a 4,5:1 sobre las tres superficies claras.
     *
     * No es preferencia estética: la Resolución 1519 de 2020 obliga a las
     * entidades del Estado colombiano a cumplir WCAG 2.1 AA, y la pauta 1.4.3
     * pide ese ratio para el texto normal. Se calcula aquí y no se confía en el
     * ojo, que es como llegaron a colarse #8a90a0 y #2676d2.
     */
    public function test_los_tonos_de_texto_cumplen_el_contraste_minimo(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Solo el bloque claro: el de alto contraste invierte los papeles.
        $claro = substr($css, 0, strpos($css, 'huv-contrast') ?: strlen($css));

        $superficies = ['#ffffff', '#f6f7f9', '#f2f5fa'];

        foreach (['--color-faint', '--color-link'] as $token) {
            preg_match('~'.preg_quote($token, '~').':\s*(#[0-9a-f]{6})~i', $claro, $c);

            $this->assertNotEmpty($c, 'No se encontró '.$token);

            foreach ($superficies as $fondo) {
                $this->assertGreaterThanOrEqual(
                    4.5,
                    $this->contraste($c[1], $fondo),
                    $token.' ('.$c[1].') no llega a 4,5:1 sobre '.$fondo
                );
            }
        }
    }

    /** Ratio de contraste de la WCAG entre dos colores. */
    private function contraste(string $a, string $b): float
    {
        $luz = function (string $hex): float {
            $hex = ltrim($hex, '#');
            $canal = function (int $v): float {
                $v /= 255;

                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            };

            return 0.2126 * $canal((int) hexdec(substr($hex, 0, 2)))
                + 0.7152 * $canal((int) hexdec(substr($hex, 2, 2)))
                + 0.0722 * $canal((int) hexdec(substr($hex, 4, 2)));
        };

        [$x, $y] = [$luz($a), $luz($b)];

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    /* ------------------------------------------------------------------ */
    /* Buscador                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * El buscador tiene tope de peticiones.
     *
     * Es la única ruta pública que recorre el cuerpo entero de dos tablas por
     * cada término: sin tope, era la forma de tumbar el portal desde fuera sin
     * necesidad de credenciales.
     */
    public function test_el_buscador_limita_las_peticiones(): void
    {
        Content::create([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ]);

        for ($i = 0; $i < 30; $i++) {
            $this->get('/buscar?q=donacion')->assertOk();
        }

        $this->get('/buscar?q=donacion')->assertStatus(429);
    }

    /** Las dos regiones de búsqueda de la página de resultados se distinguen. */
    public function test_las_dos_regiones_de_busqueda_tienen_nombre_propio(): void
    {
        $html = $this->get('/buscar?q=presupuesto')->assertOk()->getContent();

        preg_match_all('~role="search"[^>]*~', $html, $regiones);

        $this->assertCount(2, $regiones[0], 'Deben ser dos: la de la cabecera y la de la página.');

        foreach ($regiones[0] as $region) {
            $this->assertStringContainsString('aria-label', $region);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Importación                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * La importación no publica direcciones que no sean http o https.
     *
     * `source_url` se pinta en el `href` de la ficha. Las otras dos entradas al
     * campo —el formulario del panel y la carga masiva— ya comprobaban el
     * esquema; esta, la que trae lo que diga la API del portal anterior, no
     * comprobaba nada.
     */
    public function test_el_importador_descarta_direcciones_de_esquema_extrano(): void
    {
        $metodo = new \ReflectionMethod(\App\Console\Commands\ImportTopic::class, 'externalUrl');
        $metodo->setAccessible(true);
        $comando = new \App\Console\Commands\ImportTopic;

        foreach (['javascript:alert(1)', 'data:text/html,<script>', 'file:///etc/passwd', 'no es una url', ''] as $malo) {
            $this->assertNull($metodo->invoke($comando, $malo), $malo.' no debería publicarse');
        }

        foreach (['https://www.minsalud.gov.co/doc.pdf', 'http://gaceta.valledelcauca.gov.co/'] as $bueno) {
            $this->assertSame($bueno, $metodo->invoke($comando, $bueno));
        }
    }
}
