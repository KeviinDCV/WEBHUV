<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    // La portada lee los banners de la base de datos.
    use RefreshDatabase;

    /**
     * La portada se encabeza con la más reciente y deja cuatro titulares al
     * lado, como el portal. Sin pedir una noticia de más, la columna se
     * quedaba en tres en cuanto no había ninguna destacada a mano.
     */
    public function test_el_bloque_de_noticias_lleva_una_destacada_y_cuatro_titulares(): void
    {
        foreach (range(1, 8) as $i) {
            Content::create([
                'title' => 'Noticia número '.$i,
                'category' => Content::NEWS_CATEGORY,
                'published_at' => now()->subDays($i),
            ]);
        }

        $response = $this->get('/')->assertOk();

        $grupo = $response->viewData('newsBlock')['groups']->first();

        $this->assertSame('Noticia número 1', $grupo['featured']->title);
        $this->assertCount(4, $grupo['items']);
        $this->assertSame('Noticia número 2', $grupo['items']->first()->title);
    }

    public function test_la_pagina_de_inicio_responde_correctamente(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_muestra_la_identidad_institucional(): void
    {
        $response = $this->get('/');

        $response->assertSee(config('huv.institution.name'), false);
        $response->assertSee('img/logo-huv.png', false);

        // El NIT ya no aparece en el pie —el diseño institucional no lo lleva—
        // pero sigue publicado en los datos estructurados.
        $response->assertSee('"taxID":"'.config('huv.institution.nit').'"', false);
    }

    public function test_el_pie_muestra_los_datos_de_contacto_institucionales(): void
    {
        $response = $this->get('/');

        foreach (config('huv.footer.contact') as $row) {
            $response->assertSee($row['label'].':', false);
            $response->assertSee($row['value'], false);
        }

        foreach (config('huv.footer.social') as $account) {
            $response->assertSee($account['handle'], false);
            $response->assertSee($account['url'], false);
        }

        foreach (config('huv.footer.legal_links') as $link) {
            $response->assertSee($link['label'], false);
        }

        $response->assertSee('Última modificación', false);
    }

    public function test_el_reloj_de_hora_legal_parte_de_la_hora_del_servidor(): void
    {
        $response = $this->get('/');

        $response->assertSee('huvClock(', false);
        $response->assertSee('America/Bogota', false);
        $response->assertSee('Hora legal', false);

        // Un reloj anclado al navegador mostraría la hora del visitante; el
        // servidor debe inyectar su propia marca de tiempo.
        $this->assertMatchesRegularExpression(
            "/huvClock\(\d{13},\s*(?:'|&#039;)America\/Bogota(?:'|&#039;)\)/",
            $response->getContent()
        );
    }

    public function test_incluye_el_boton_volver_arriba(): void
    {
        $response = $this->get('/');

        $response->assertSee('Volver arriba', false);
        $response->assertSee('huvBackToTop', false);
        $response->assertSee('id="huv-inicio-pagina"', false);
    }

    public function test_el_logotipo_declara_su_proporcion_real(): void
    {
        [$width, $height] = getimagesize(public_path('img/logo-huv.png'));

        $html = $this->get('/')->getContent();

        preg_match_all('/<img[^>]+logo-huv\.png[^>]*>/', $html, $tags);
        $this->assertNotEmpty($tags[0], 'No se encontró ninguna etiqueta del logotipo.');

        // Declarar una proporción distinta de la real deforma el logotipo
        // mientras carga y provoca un salto de maquetación.
        foreach ($tags[0] as $tag) {
            preg_match('/width="(\d+)"/', $tag, $w);
            preg_match('/height="(\d+)"/', $tag, $h);

            $this->assertNotEmpty($w, "Falta el atributo width en: {$tag}");
            $this->assertNotEmpty($h, "Falta el atributo height en: {$tag}");
            $this->assertEqualsWithDelta(
                $width / $height,
                $w[1] / $h[1],
                0.01,
                "La proporción declarada no coincide con la real del archivo ({$width}×{$height})."
            );
        }
    }

    public function test_renderiza_las_secciones_de_la_home_en_orden(): void
    {
        $html = $this->get('/')->getContent();

        // El orden importa: reproduce el del portal institucional.
        $sections = ['id="inicio"', 'id="noticias"', 'id="huv-accesos"', 'id="huv-contenidos"',
            'id="eventos"', 'id="huv-boletines"', 'id="huv-entidades"'];

        $positions = array_map(function (string $needle) use ($html): int {
            $at = strpos($html, $needle);
            $this->assertNotFalse($at, "No se encontró la sección {$needle}.");

            return $at;
        }, $sections);

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Las secciones no aparecen en el orden esperado.');
    }

    public function test_renderiza_todo_el_contenido_configurado(): void
    {
        $response = $this->get('/');

        foreach (config('huv.bulletins.items') as $item) {
            $response->assertSee($item['title'], false);
        }

        foreach (config('huv.partners.items') as $entity) {
            $response->assertSee($entity['name'], false);
        }
    }

    public function test_el_listado_de_contenidos_se_publica_entero_en_el_html(): void
    {
        // Se renderizan todas las tarjetas aunque solo se muestren seis: el
        // filtrado es cosa del cliente, pero los buscadores y quien navega sin
        // JavaScript deben ver el listado completo.
        $titulos = collect(range(1, 8))->map(function (int $i): string {
            $titulo = "Contenido de prueba {$i}";

            \App\Models\Content::create([
                'title' => $titulo,
                'category' => \App\Models\Content::NEWS_CATEGORY,
                'published_at' => now()->subDays($i),
            ]);

            return $titulo;
        });

        $response = $this->get('/');
        $html = $response->getContent();

        foreach ($titulos as $titulo) {
            $response->assertSee($titulo, false);
        }

        $this->assertStringContainsString('huvContentFeed(', $html);
        $this->assertStringContainsString('Cargar más contenidos', $html);
    }

    public function test_la_agenda_navega_entre_periodos_por_la_url(): void
    {
        // Semana actual: debe contener el evento programado para hoy.
        $this->get('/')->assertOk()->assertSee('Eventos', false);

        // La navegación viaja en la URL, así que funciona sin JavaScript.
        $this->get('/?vista=mes&periodo=0')->assertOk();
        $this->get('/?vista=semana&periodo=-1')->assertOk();
        $this->get('/?vista=semana&periodo=1')->assertOk();

        // Valores fuera de rango o inventados no deben romper la página.
        $this->get('/?vista=trimestre&periodo=99999')->assertOk();
        $this->get('/?vista[]=x&periodo=abc')->assertOk();
    }

    public function test_el_menu_completo_publica_sus_cuatro_categorias(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        foreach (config('huv.mega_menu') as $column) {
            $response->assertSee($column['title'], false);

            foreach ($column['links'] as $link) {
                $response->assertSee($link['label'], false);

                // Cada enlace lleva adonde diga el resolutor: a este aplicativo
                // si la sección ya se migró —«/sucursales» es una página propia
                // desde que existe—, al portal anterior si todavía no, y a su
                // dominio si es de otra entidad.
                $this->assertStringContainsString(e(LegacyLink::resolve($link)['href']), $html);
            }
        }

        // Patrón de pestañas verticales de WAI-ARIA.
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertSame(
            count(config('huv.mega_menu')),
            substr_count($html, 'role="tabpanel"'),
            'Debe haber un panel por categoría.'
        );
    }

    public function test_los_enlaces_externos_del_menu_se_abren_de_forma_segura(): void
    {
        $html = $this->get('/')->getContent();

        // Todo target="_blank" necesita rel="noopener" para no exponer
        // window.opener al sitio de destino.
        preg_match_all('/<a\b[^>]*target="_blank"[^>]*>/', $html, $tags);
        $this->assertNotEmpty($tags[0]);

        foreach ($tags[0] as $tag) {
            $this->assertStringContainsString('rel="noopener noreferrer"', $tag);
        }
    }

    public function test_los_menus_desplegables_muestran_todos_sus_items(): void
    {
        $response = $this->get('/');

        foreach (config('huv.nav') as $item) {
            foreach ($item['children'] ?? [] as $child) {
                $response->assertSee($child['label'], false);
            }
        }
    }

    public function test_los_desplegables_largos_se_reparten_en_columnas_sin_scroll(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        // «Atención y Servicios a la ciudadanía» tiene 16 ítems: debe fluir a
        // dos columnas de 8 filas.
        $this->assertStringContainsString('grid-auto-flow: column', $html);
        $this->assertStringContainsString('repeat(8, auto)', $html);

        // El panel crece hacia los lados; el scroll interno quedaría feo.
        // (El cajón móvil sí desplaza, por eso se acota al desplegable.)
        preg_match('/<div[^>]*id="huv-menu-atencion".*?>/s', $html, $panel);
        $this->assertNotEmpty($panel, 'No se encontró el panel del desplegable.');
        $this->assertStringNotContainsString('overflow-y-auto', $panel[0]);
        $this->assertStringNotContainsString('max-h-', $panel[0]);
    }

    public function test_la_barra_superior_azul_fue_retirada(): void
    {
        $this->assertNull(config('huv.topbar'));
        $this->assertFileDoesNotExist(resource_path('views/partials/topbar.blade.php'));

        $this->get('/')->assertDontSee('Empresa Social del Estado · Secretaría de Salud', false);
    }

    public function test_el_alto_contraste_es_un_tema_de_color_y_no_un_filtro(): void
    {
        $css = collect(glob(public_path('build/assets/app-*.css')))
            ->map(fn (string $file): string => (string) file_get_contents($file))
            ->implode('');

        $this->assertNotSame('', $css, 'No hay CSS compilado: ejecuta «npm run build».');

        // El tema vive en tokens redefinidos, no en un filter sobre <body>.
        // El minificador elimina las comillas del selector de atributo.
        $this->assertMatchesRegularExpression('/html\[data-huv-contrast=[\'"]?on[\'"]?\]/', $css);
        $this->assertStringNotContainsString('invert(1) hue-rotate(180deg)', $css);

        // La paleta de contraste redefine los tokens en lugar de invertir la página.
        foreach (['--color-page:#000', '--color-heading:#ff0', '--color-on-accent:#000'] as $token) {
            $this->assertStringContainsString($token, $css);
        }

        // Modo de contraste forzado del sistema operativo.
        $this->assertStringContainsString('forced-colors', $css);
    }

    public function test_incluye_metadatos_de_seo_y_datos_estructurados(): void
    {
        $response = $this->get('/');

        $response->assertSee('<meta name="description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":["Hospital","GovernmentOrganization"]', false);
    }

    public function test_incluye_ayudas_de_accesibilidad(): void
    {
        // El carrusel solo se anuncia cuando hay banners que rotar.
        \App\Models\Banner::create([
            'position' => 1,
            'image_path' => 'banners/ejemplo.jpg',
            'alt_text' => 'Banner de ejemplo',
        ]);

        $response = $this->get('/');

        $response->assertSee('Saltar al contenido principal', false);
        $response->assertSee('id="contenido"', false);
        $response->assertSee('Herramientas de accesibilidad', false);
        $response->assertSee('aria-roledescription="carrusel"', false);
        $response->assertSee('lang="es-CO"', false);
    }

    /**
     * El muro de la portada no puede imprimirlo todo.
     *
     * Se imprime entero en el HTML y filtra en el navegador, así que cada
     * contenido pesa. Al importar las 425 notificaciones judiciales la portada
     * pasó de 150 KB a 1,17 MB de una sola vez.
     */
    public function test_el_muro_de_la_portada_esta_acotado(): void
    {
        config(['huv.content_feed.max_items' => 10]);

        foreach (range(1, 25) as $i) {
            Content::create([
                'title' => 'Contenido '.$i,
                'slug' => 'contenido-'.$i,
                'category' => 'Noticias',
                'body' => '<p>Texto.</p>',
                'published_at' => now()->subDays($i),
                'show_in_feed' => true,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('feed', fn ($feed) => $feed->count() === 10)
            // Y son los más recientes, no diez cualesquiera.
            ->assertSee('Contenido 1')
            ->assertDontSee('Contenido 25');
    }
}
