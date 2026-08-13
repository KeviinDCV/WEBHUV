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

    /**
     * El texto plano conserva la forma, no la aplasta.
     *
     * Colapsa los espacios, pero cada bloque que cierra deja su salto. Sin eso,
     * `strip_tags` pegaba «…Evaristo García E.S.E.</p><p>Dirección:» en
     * «E.S.E.Dirección» —dos palabras hechas una en 52 resúmenes— y los bloques
     * de dirección del «Directorio de entidades» salían en un solo renglón.
     */
    public function test_el_texto_plano_colapsa_los_espacios_y_conserva_los_saltos(): void
    {
        // El salto entre «</p>» y «<p>» es sangrado del HTML y no cuenta; el
        // que hay entre «Uno» y «dos» está dentro del texto y sí.
        $this->assertSame(
            "Uno\ndos\ntres",
            RichText::toPlainText("<p>Uno\n  dos</p>\n<p>tres</p>")
        );

        $this->assertSame(
            "Hospital Universitario del Valle E.S.E.\nDirección: Cl. 5 # 36-08",
            RichText::toPlainText('<p>Hospital Universitario del Valle E.S.E.</p><p>Dirección: Cl. 5 # 36-08</p>')
        );

        // Un párrafo vacío entre dos bloques deja una línea en blanco, y solo
        // una: los cierres encadenados dejarían tres o cuatro seguidas.
        $this->assertSame(
            "Título\n\nCuerpo",
            RichText::toPlainText('<h2>Título</h2><p><br></p><p>Cuerpo</p>')
        );
    }

    /** Para una etiqueta <meta> hace falta el mismo texto en un renglón. */
    public function test_una_sola_linea_para_los_metadatos(): void
    {
        $this->assertSame(
            'Uno dos tres',
            RichText::toSingleLine("<p>Uno\n  dos</p>\n<p>tres</p>")
        );
    }

    /**
     * El resumen corta sin partir palabras y sin perder las líneas.
     *
     * `Str::limit(..., preserveWords: true)` convierte los saltos en espacios,
     * así que hacía inútil todo lo anterior: las cuatro líneas del INVIMA
     * —tipo de control, calle, teléfono, correo— volvían a salir seguidas.
     */
    public function test_el_resumen_corta_sin_perder_los_saltos(): void
    {
        $html = '<p>Tipo de control: Inspección y Vigilancia<br>Carrera 10 #64 - 28<br>(+57) 601 742 2121</p>';

        $this->assertSame(
            "Tipo de control: Inspección y Vigilancia\nCarrera 10 #64 - 28\n(+57) 601 742 2121",
            RichText::excerpt($html, 200)
        );

        // Cortado, sigue partiendo por el hueco y no por la mitad de un número
        // de teléfono.
        $this->assertSame(
            "Tipo de control: Inspección y Vigilancia\nCarrera 10 #64 -",
            RichText::excerpt($html, 60)
        );
    }

    /**
     * Los saltos del texto se vuelven <br>, los del sangrado no.
     *
     * El editor del portal guarda lo que se teclea y lo pinta convirtiendo esos
     * saltos; media docena de contenidos del «Directorio de entidades» son
     * bloques de dirección escritos así, sin una sola etiqueta.
     */
    public function test_los_saltos_del_texto_se_vuelven_br(): void
    {
        $this->assertSame(
            '<p>Tipo de control: Vigilancia<br>Carrera 10 #64 - 28</p>',
            RichText::normalizeLegacy("<p>Tipo de control: Vigilancia\nCarrera 10 #64 - 28</p>")
        );

        // Entre etiquetas no se toca: ni el sangrado ni, sobre todo, un salto
        // detrás de un <br> que ya estaba puesto, que quedaría duplicado.
        $this->assertSame(
            "<p>Uno<br>\nDos</p>\n<p>Tres</p>",
            RichText::normalizeLegacy("<p>Uno<br>\nDos</p>\n<p>Tres</p>")
        );
    }

    /**
     * Un cuerpo envuelto en <article> no puede desaparecer.
     *
     * El saneador borra la etiqueta que no conoce JUNTO CON SU CONTENIDO. El
     * editor del portal pegó el texto de «Respuesta del caso Nº 0993312025»
     * dentro de un <article class="content-descri"> y la importación lo guardó
     * vacío: 424 notificaciones con texto y una en blanco.
     */
    public function test_un_cuerpo_envuelto_en_un_bloque_desconocido_sobrevive(): void
    {
        $envoltorios = ['article', 'section', 'main', 'header', 'footer', 'aside'];

        foreach ($envoltorios as $tag) {
            $html = '<'.$tag.' class="content-descri"><p>Señor usuario, la respuesta está disponible.</p></'.$tag.'>';

            $limpio = RichText::clean(RichText::normalizeLegacy($html));

            $this->assertStringContainsString(
                'Señor usuario, la respuesta está disponible.',
                (string) $limpio,
                "El texto envuelto en <{$tag}> se perdió."
            );

            // Se desenvuelve, no se convierte: un <p> dentro de otro <p> no es
            // HTML válido y el saneador lo partiría.
            $this->assertStringNotContainsString('<'.$tag, (string) $limpio);
        }
    }
}
