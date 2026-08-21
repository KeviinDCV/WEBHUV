<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * El mapa del sitio y los datos estructurados.
 *
 * El portal publica más de dos mil direcciones, y muchas quedan a varios saltos
 * de la portada: sin mapa, un rastreador tarda meses en encontrarlas. Para una
 * entidad del Estado eso significa que contrataciones y actos administrativos
 * publicados no salen cuando la ciudadanía los busca.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El mapa se cachea seis horas: sin vaciar, una prueba vería lo que
        // dejó la anterior.
        Cache::flush();
    }

    private function tema(string $slug, string $nombre): Topic
    {
        return Topic::create([
            'name' => $nombre,
            'slug' => $slug,
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);
    }

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Jornada de donación de sangre',
            'category' => Content::NEWS_CATEGORY,
            'excerpt' => 'El Banco de Sangre atenderá el sábado en la sede principal.',
            'is_active' => true,
            'show_in_feed' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* robots.txt                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * robots.txt declara el mapa con su dirección absoluta.
     *
     * Se sirve por ruta y no como fichero estático justamente por esto: en un
     * fichero habría que escribir el dominio a mano y quedaría mal al desplegar.
     */
    public function test_robots_declara_el_mapa_del_sitio(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap.index'), false)
            ->assertSee('Disallow: /administracion/', false);
    }

    /* ------------------------------------------------------------------ */
    /* El mapa                                                             */
    /* ------------------------------------------------------------------ */

    public function test_el_indice_del_mapa_lista_las_tres_secciones(): void
    {
        $respuesta = $this->get('/sitemap.xml')->assertOk();

        $respuesta->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        foreach (['temas', 'contenidos', 'articulos'] as $seccion) {
            $respuesta->assertSee(route('sitemap.section', $seccion), false);
        }
    }

    public function test_una_seccion_inventada_no_existe(): void
    {
        $this->get('/sitemap-inventada.xml')->assertNotFound();
    }

    /** El XML tiene que estar bien formado, o el rastreador lo descarta entero. */
    public function test_el_mapa_es_xml_bien_formado(): void
    {
        $this->noticia();
        $this->tema('planes', 'Planes');

        foreach (['temas', 'contenidos', 'articulos'] as $seccion) {
            $xml = $this->get('/sitemap-'.$seccion.'.xml')->assertOk()->getContent();

            $anterior = libxml_use_internal_errors(true);
            $documento = simplexml_load_string($xml);
            libxml_use_internal_errors($anterior);

            $this->assertNotFalse($documento, 'El mapa de «'.$seccion.'» no es XML válido.');
        }
    }

    public function test_el_mapa_publica_los_temas_y_las_paginas_propias(): void
    {
        $topic = $this->tema('planes', 'Planes');

        $this->get('/sitemap-temas.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('topics.show', $topic).'</loc>', false)
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertSee('<loc>'.route('transparency').'</loc>', false);
    }

    /**
     * Lo que no está publicado no entra en el mapa.
     *
     * Anunciarle a un buscador una página que le va a devolver un contenido
     * oculto —o que ni siquiera existe todavía— es gastar su presupuesto de
     * rastreo en nada.
     */
    public function test_el_mapa_no_publica_lo_que_el_visitante_no_ve(): void
    {
        $this->noticia(['title' => 'Publicada', 'slug' => 'publicada']);
        $this->noticia(['title' => 'Inactiva', 'slug' => 'inactiva', 'is_active' => false]);
        $this->noticia(['title' => 'Oculta', 'slug' => 'oculta', 'is_hidden' => true]);
        $this->noticia(['title' => 'Programada', 'slug' => 'programada', 'published_at' => now()->addWeek()]);

        $respuesta = $this->get('/sitemap-contenidos.xml')->assertOk();

        $respuesta->assertSee('/contenidos/publicada</loc>', false);
        $respuesta->assertDontSee('/contenidos/inactiva', false);
        $respuesta->assertDontSee('/contenidos/oculta', false);
        $respuesta->assertDontSee('/contenidos/programada', false);
    }

    /**
     * Un contenido que enlaza fuera no tiene página propia.
     *
     * Su dirección es la de otro sitio, y anunciar en nuestro mapa una página
     * ajena es decirle al buscador algo que no es nuestro.
     */
    public function test_el_mapa_no_publica_contenidos_que_llevan_fuera(): void
    {
        $this->noticia(['title' => 'Propia', 'slug' => 'propia']);
        $this->noticia(['title' => 'Ajena', 'slug' => 'ajena', 'link' => 'https://www.minsalud.gov.co/']);

        $this->get('/sitemap-contenidos.xml')
            ->assertOk()
            ->assertSee('/contenidos/propia</loc>', false)
            ->assertDontSee('minsalud.gov.co', false)
            ->assertDontSee('/contenidos/ajena', false);
    }

    /**
     * Ninguna dirección aparece dos veces.
     *
     * Los enlaces importados del portal anterior los reescribe LegacyLink
     * contra este, y unos cuantos apuntaban a PÁGINAS DE TEMA: colados así, el
     * mapa anunciaba trece direcciones repetidas y encima como si fueran fichas
     * de artículo.
     */
    public function test_el_mapa_no_repite_ninguna_direccion(): void
    {
        $topic = $this->tema('control-ciudadano', 'Control ciudadano');

        $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Artículo con página propia',
            'slug' => 'articulo-propio',
            'published_at' => now()->subDay(),
        ]);

        // Un enlace que apunta al mismo tema en el portal anterior: al
        // reescribirlo, su dirección es la del tema, no una ficha suya.
        $topic->items()->create([
            'kind' => TopicItem::KIND_LINK,
            'title' => 'Enlace al propio tema',
            'slug' => 'enlace-al-tema',
            'source_url' => rtrim((string) config('huv.legacy_base'), '/').'/tema/control-ciudadano',
            'published_at' => now()->subDay(),
        ]);

        $todas = [];

        foreach (['temas', 'contenidos', 'articulos'] as $seccion) {
            preg_match_all(
                '~<loc>(.*?)</loc>~',
                $this->get('/sitemap-'.$seccion.'.xml')->assertOk()->getContent(),
                $c
            );
            $todas = array_merge($todas, $c[1]);
        }

        $this->assertSame(
            array_values(array_unique($todas)),
            $todas,
            'Hay direcciones repetidas: '.implode(', ', array_diff_assoc($todas, array_unique($todas)))
        );
    }

    /* ------------------------------------------------------------------ */
    /* Datos estructurados                                                 */
    /* ------------------------------------------------------------------ */

    /** La organización, en todas las páginas y con los datos que ya existían. */
    public function test_la_organizacion_declara_perfiles_horario_y_ambito(): void
    {
        $bloques = $this->jsonLd($this->get('/')->assertOk()->getContent());

        $this->assertCount(1, $bloques);

        $organizacion = $bloques[0];

        $this->assertContains('Hospital', (array) $organizacion['@type']);
        $this->assertNotEmpty($organizacion['sameAs']);
        $this->assertCount(2, $organizacion['openingHoursSpecification']);
        $this->assertSame('Valle del Cauca', $organizacion['areaServed']['name']);
        $this->assertSame(url('/').'#organizacion', $organizacion['@id']);
    }

    /** Una noticia se declara como tal, y remite a la organización por su id. */
    public function test_la_ficha_de_una_noticia_emite_su_bloque_y_sus_migas(): void
    {
        $noticia = $this->noticia();

        $tipos = collect($this->jsonLd($this->get($noticia->url())->assertOk()->getContent()))
            ->pluck('@type')
            ->map(fn ($t) => is_array($t) ? implode('/', $t) : $t);

        $this->assertContains('NewsArticle', $tipos);
        $this->assertContains('BreadcrumbList', $tipos);
    }

    /**
     * Un comunicado no es una noticia.
     *
     * Declararlo como `NewsArticle` para arañar un resultado enriquecido es lo
     * que las directrices llaman marcado engañoso.
     */
    public function test_un_comunicado_no_se_declara_como_noticia(): void
    {
        $comunicado = $this->noticia([
            'title' => 'Comunicado de prensa',
            'slug' => 'comunicado',
            'category' => 'Comunicados',
        ]);

        $tipos = collect($this->jsonLd($this->get($comunicado->url())->assertOk()->getContent()))
            ->pluck('@type')
            ->map(fn ($t) => is_array($t) ? implode('/', $t) : $t);

        $this->assertContains('Article', $tipos);
        $this->assertNotContains('NewsArticle', $tipos);
    }

    /**
     * Un documento no es un artículo.
     *
     * Es un PDF con una portada, no una pieza escrita: solo lleva sus migas.
     */
    public function test_un_documento_no_se_declara_como_articulo(): void
    {
        $topic = $this->tema('presupuesto', 'Presupuesto');

        $documento = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Acuerdo No.023 Presupuesto 2024',
            'slug' => 'acuerdo-023',
            'published_at' => now()->subDay(),
        ]);

        $tipos = collect($this->jsonLd($this->get(route('topics.items.show', [$topic, $documento]))->assertOk()->getContent()))
            ->pluck('@type')
            ->map(fn ($t) => is_array($t) ? implode('/', $t) : $t);

        $this->assertContains('BreadcrumbList', $tipos);
        $this->assertNotContains('Article', $tipos);
    }

    /** Las migas declaradas son las que se pintan, en el mismo orden. */
    public function test_las_migas_declaradas_coinciden_con_las_visibles(): void
    {
        $topic = $this->tema('presupuesto', 'Presupuesto');

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Ejecución presupuestal',
            'slug' => 'ejecucion',
            'published_at' => now()->subDay(),
        ]);

        $migas = collect($this->jsonLd($this->get(route('topics.items.show', [$topic, $item]))->assertOk()->getContent()))
            ->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($migas);
        $this->assertSame(
            [__('paginas.ruta.inicio'), 'Presupuesto', 'Ejecución presupuestal'],
            array_column($migas['itemListElement'], 'name')
        );
    }

    /** Todo bloque emitido tiene que ser JSON válido, o el buscador lo tira. */
    public function test_todos_los_bloques_son_json_valido(): void
    {
        $noticia = $this->noticia();

        foreach (['/', $noticia->url()] as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();

            preg_match_all('~<script type="application/ld\+json">\s*(.*?)\s*</script>~s', $html, $c);

            $this->assertNotEmpty($c[1], 'Sin datos estructurados en '.$ruta);

            foreach ($c[1] as $crudo) {
                $this->assertNotNull(json_decode($crudo, true), 'JSON inválido en '.$ruta.': '.mb_substr($crudo, 0, 80));
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLd(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">\s*(.*?)\s*</script>~s', $html, $c);

        return array_values(array_filter(array_map(
            fn (string $crudo): mixed => json_decode($crudo, true),
            $c[1]
        )));
    }
}
