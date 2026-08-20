<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Support\ConfigLabel;
use App\Support\PortalLang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * El sitio en inglés con el contenido en español.
 *
 * El hospital publica en español y va a seguir publicando en español: lo que se
 * ofrece en inglés es la interfaz. Eso deja páginas <html lang="en"> con
 * párrafos españoles dentro, y hay que declararlo en el marcado o un lector de
 * pantalla en inglés los leerá con fonética inglesa —criterio 3.1.2 de WCAG,
 * «idioma de las partes»— y el traductor del navegador los dará por inglés y no
 * los traducirá, que es justo lo que se le está pidiendo.
 *
 * Antes de esto solo iban marcados los títulos: el cuerpo de una noticia, su
 * resumen, el nombre del tema y los cien nombres de entidades del menú salían
 * sin decir en qué idioma estaban.
 */
class LanguageTest extends TestCase
{
    use RefreshDatabase;

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
    /* El componente de marcado                                            */
    /* ------------------------------------------------------------------ */

    public function test_el_contenido_se_marca_en_espanol_cuando_la_pagina_va_en_ingles(): void
    {
        app()->setLocale('en');

        $this->assertSame(
            '<span lang="es">Rendición de cuentas</span>',
            trim(Blade::render('<x-texto-del-portal>{{ $t }}</x-texto-del-portal>', ['t' => 'Rendición de cuentas']))
        );
    }

    /**
     * En español no se marca nada.
     *
     * Repetir `lang="es"` dentro de un documento que ya es español es ruido, y
     * con la marca puesta siempre no habría forma de distinguir lo que de
     * verdad cambia de idioma.
     */
    public function test_en_espanol_el_contenido_no_lleva_marca(): void
    {
        app()->setLocale('es');

        $this->assertSame(
            '<span>Rendición de cuentas</span>',
            trim(str_replace('<span >', '<span>', Blade::render(
                '<x-texto-del-portal>{{ $t }}</x-texto-del-portal>',
                ['t' => 'Rendición de cuentas']
            )))
        );
    }

    /**
     * El texto no se escapa dos veces.
     *
     * La ranura llega ya compuesta por Blade. Volver a escaparla convertía
     * «Salud & Bienestar» en «Salud &amp;amp; Bienestar», que es lo que se
     * habría visto en pantalla en cuanto se marcara el primer resumen.
     */
    public function test_el_texto_marcado_no_se_escapa_dos_veces(): void
    {
        app()->setLocale('en');

        $salida = Blade::render(
            '<x-texto-del-portal>{{ $t }}</x-texto-del-portal>',
            ['t' => 'Salud & Bienestar <b>2026</b>']
        );

        $this->assertStringContainsString('Salud &amp; Bienestar &lt;b&gt;2026&lt;/b&gt;', $salida);
        $this->assertStringNotContainsString('&amp;amp;', $salida);
    }

    /** Un cuerpo ya saneado conserva su HTML. */
    public function test_el_cuerpo_marcado_conserva_su_formato(): void
    {
        app()->setLocale('en');

        $salida = Blade::render(
            '<x-texto-del-portal tag="div">{!! $t !!}</x-texto-del-portal>',
            ['t' => '<p>Un párrafo</p>']
        );

        $this->assertStringContainsString('<div lang="es"><p>Un párrafo</p></div>', $salida);
    }

    /* ------------------------------------------------------------------ */
    /* Los atributos y las frases mixtas                                   */
    /* ------------------------------------------------------------------ */

    /**
     * El idioma de un `alt` es el del elemento que lo lleva.
     *
     * No hay forma de marcar un atributo por dentro, así que la marca va en la
     * etiqueta.
     */
    public function test_el_atributo_de_idioma_solo_aparece_fuera_del_espanol(): void
    {
        app()->setLocale('en');
        $this->assertSame(' lang="es"', (string) PortalLang::attribute());

        app()->setLocale('es');
        $this->assertSame('', (string) PortalLang::attribute());
    }

    /**
     * Un nombre español dentro de una frase inglesa se marca solo él.
     *
     * Y se escapa: el título de una noticia llega de la base de datos y podría
     * traer cualquier cosa.
     */
    public function test_el_nombre_incrustado_se_marca_y_se_escapa(): void
    {
        app()->setLocale('en');

        $this->assertSame(
            '<span lang="es">Salud &amp; Bienestar</span>',
            (string) PortalLang::wrap('Salud & Bienestar')
        );

        app()->setLocale('es');

        $this->assertSame('Salud &amp; Bienestar', (string) PortalLang::wrap('Salud & Bienestar'));
    }

    /* ------------------------------------------------------------------ */
    /* Los rótulos de la configuración                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Un rótulo sin traducir se marca; uno traducido no.
     *
     * Se decide donde se sabe si lo que sale es la traducción o el original, de
     * modo que una entrada nueva sin traducir queda marcada sola. Los cien
     * nombres de colegios y personerías del menú no se traducen —son nombres
     * propios— y por eso mismo hay que declarar su idioma.
     */
    public function test_un_rotulo_de_configuracion_sin_traducir_se_marca(): void
    {
        app()->setLocale('en');

        $sinClave = ['label' => 'Personería Municipal de Tuluá'];
        $conClave = ['i18n' => 'menu.nav.inicio', 'label' => 'Inicio'];

        $this->assertSame(
            '<span lang="es">Personería Municipal de Tuluá</span>',
            (string) ConfigLabel::marked($sinClave)
        );
        $this->assertSame('Home', (string) ConfigLabel::marked($conClave));
    }

    /* ------------------------------------------------------------------ */
    /* Las páginas                                                         */
    /* ------------------------------------------------------------------ */

    /** El resumen de una tarjeta va marcado, no solo su título. */
    public function test_la_portada_en_ingles_marca_titular_y_resumen(): void
    {
        $this->noticia();

        $html = $this->get('/?idioma=en')->assertOk()->getContent();

        $this->assertStringContainsString('<span lang="es">Jornada de donación de sangre</span>', $html);
        $this->assertStringContainsString(
            'El Banco de Sangre atenderá el sábado en la sede principal.',
            $html
        );
        $this->assertMatchesRegularExpression(
            '~<p lang="es"[^>]*>El Banco de Sangre atenderá el sábado en la sede principal\.</p>~',
            $html
        );
    }

    /** El cuerpo de una ficha —el bloque de texto más largo del sitio—. */
    public function test_la_ficha_en_ingles_marca_el_titulo_y_el_cuerpo(): void
    {
        $noticia = $this->noticia(['body' => '<p>El hospital informa a la opinión pública.</p>']);

        $html = $this->get($noticia->url().'?idioma=en')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '~<h1 lang="es"[^>]*>\s*Jornada de donación de sangre\s*</h1>~',
            $html
        );
        $this->assertMatchesRegularExpression(
            '~<div lang="es"[^>]*><p>El hospital informa a la opinión pública\.</p></div>~',
            $html
        );
    }

    /**
     * Y en español la misma ficha no marca su contenido.
     *
     * No se comprueba que no haya ni un `lang="es"` en toda la página: el
     * interruptor de idioma lleva el suyo a propósito —el enlace «ES» rotula la
     * opción española y está en español lo esté o no la página—, y una
     * comprobación así de amplia lo daría por defecto.
     */
    public function test_la_misma_ficha_en_espanol_no_marca_su_contenido(): void
    {
        $noticia = $this->noticia(['body' => '<p>El hospital informa a la opinión pública.</p>']);

        $html = $this->get($noticia->url().'?idioma=es')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '~<h1 class="[^"]*"[^>]*>\s*Jornada de donación de sangre\s*</h1>~',
            $html
        );
        $this->assertStringContainsString(
            '<div class="huv-prose mt-8"><p>El hospital informa a la opinión pública.</p></div>',
            $html
        );
        $this->assertStringNotContainsString('lang="es"><p>El hospital', $html);
    }

    /** El nombre y la descripción de un tema. */
    public function test_la_pagina_de_un_tema_marca_su_nombre_y_su_descripcion(): void
    {
        $topic = Topic::create([
            'name' => 'Rendición de cuentas',
            'slug' => 'rendicion-de-cuentas',
            'description' => 'Informes de gestión de la institución.',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe de gestión 2025',
            'slug' => 'informe-2025',
            'published_at' => now()->subDay(),
        ]);

        $html = $this->get(route('topics.show', $topic).'?idioma=en')->assertOk()->getContent();

        $this->assertStringContainsString('<span lang="es">Rendición de cuentas</span>', $html);
        $this->assertMatchesRegularExpression(
            '~<p lang="es"[^>]*>Informes de gestión de la institución\.</p>~',
            $html
        );
    }

    /**
     * Los nombres de entidades del menú.
     *
     * Son un centenar de colegios, personerías y entidades territoriales que no
     * se traducen porque son nombres propios.
     */
    public function test_el_menu_marca_los_nombres_de_las_entidades(): void
    {
        $html = $this->get('/?idioma=en')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<span lang="es">Personería Municipal de Tuluá en Valle del Cauca</span>',
            $html
        );
    }
}
