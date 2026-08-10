<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    /**
     * El saneador descarta la etiqueta que no conoce JUNTO CON SU CONTENIDO: no
     * la desenvuelve. Esta prueba deja constancia de ello, porque es la razón de
     * que exista normalizeLegacy() y no es un comportamiento evidente.
     */
    public function test_una_etiqueta_no_permitida_se_lleva_el_texto_por_delante(): void
    {
        $this->assertNull(RichText::clean('<div><p>Texto <b>en negrita</b></p></div>'));
    }

    public function test_normalizar_antes_de_depurar_conserva_todo_el_texto(): void
    {
        $clean = RichText::clean(RichText::normalizeLegacy('<div><p>Texto <b>en negrita</b></p></div>'));

        $this->assertStringContainsString('Texto', $clean);
        $this->assertStringContainsString('<strong>en negrita</strong>', $clean);
        $this->assertStringNotContainsString('<div', $clean);
    }

    /** El origen trae «<b >» y etiquetas con atributos. */
    public function test_se_normalizan_las_etiquetas_con_atributos_y_con_espacios(): void
    {
        $html = RichText::normalizeLegacy('<DIV class="x"><b >negrita</b><i style="a">cursiva</I></div >');

        $this->assertStringNotContainsString('<b', $html);
        $this->assertStringNotContainsString('<i ', $html);
        $this->assertStringContainsString('<strong>negrita</strong>', $html);
        $this->assertStringContainsString('<em>cursiva</em>', $html);
    }

    /**
     * El editor del portal anterior deja encabezados sin texto. Quien navega
     * con lector de pantalla salta de encabezado en encabezado y aterrizaría en
     * uno que no anuncia nada.
     */
    public function test_los_encabezados_sin_texto_se_retiran(): void
    {
        $clean = RichText::clean('<h3><strong><br></strong></h3><p>Misión</p><h3>&nbsp;</h3><h2>Visión</h2>');

        $this->assertStringNotContainsString('<h3><strong>', $clean);
        $this->assertStringNotContainsString('<h3>&nbsp;</h3>', $clean);
        $this->assertStringContainsString('Misión', $clean);
        $this->assertStringContainsString('<h2>Visión</h2>', $clean);
    }

    public function test_un_cuerpo_vacio_no_es_contenido(): void
    {
        $this->assertNull(RichText::clean('<p><br></p>'));
        $this->assertNull(RichText::normalizeLegacy(''));
    }

    public function test_el_texto_plano_colapsa_los_espacios(): void
    {
        $this->assertSame(
            'Uno dos tres',
            RichText::toPlainText("<p>Uno\n  dos</p>\n<p>tres</p>")
        );
    }
}
