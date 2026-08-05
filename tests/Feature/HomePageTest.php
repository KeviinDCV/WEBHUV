<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
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

    public function test_renderiza_todas_las_secciones_de_la_home(): void
    {
        $response = $this->get('/');

        foreach ([
            'id="inicio"',
            'id="noticias"',
            'id="transparencia"',
            'Atención y Servicios a la ciudadanía',
            'Servicios y especialidades',
            'Líneas de atención',
        ] as $needle) {
            $response->assertSee($needle, false);
        }
    }

    public function test_renderiza_todo_el_contenido_configurado(): void
    {
        $response = $this->get('/');

        foreach (config('huv.quick_access.items') as $item) {
            $response->assertSee($item['title'], false);
        }

        foreach (config('huv.transparency.items') as $item) {
            $response->assertSee($item, false);
        }

        foreach (config('huv.services.items') as $service) {
            $response->assertSee($service, false);
        }

        foreach (config('huv.news.items') as $item) {
            $response->assertSee($item['title'], false);
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
        $response = $this->get('/');

        $response->assertSee('Saltar al contenido principal', false);
        $response->assertSee('id="contenido"', false);
        $response->assertSee('Herramientas de accesibilidad', false);
        $response->assertSee('aria-roledescription="carrusel"', false);
        $response->assertSee('lang="es-CO"', false);
    }
}
