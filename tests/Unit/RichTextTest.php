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

    /**
     * Enlaces sin rótulo.
     *
     * Los deja el editor del portal anterior al borrar el texto de un enlace
     * sin borrar la etiqueta. En «Denuncias por posibles actos de corrupción»
     * hay uno justo encima del enlace bueno y apuntando al mismo formulario: no
     * se ve, pero un lector de pantalla lo lista y lo lee sin nombre, y el
     * tabulador se para en él.
     */
    public function test_los_enlaces_sin_rotulo_se_retiran(): void
    {
        $clean = RichText::clean(
            '<p><a href="https://acortar.link/OUtyCS" target=""></a></p>'
            .'<h3><a href="https://acortar.link/OUtyCS" target=""><u>Formulario Denuncias</u></a></h3>'
        );

        $this->assertStringNotContainsString('</a></p>', $clean);
        $this->assertStringContainsString('<u>Formulario Denuncias</u></a></h3>', $clean);

        // Uno con un <br> dentro tampoco es un rótulo, y el encabezado que se
        // queda sin nada se va detrás.
        $this->assertNull(RichText::clean('<h3><a href="https://x.test"><strong><br></strong></a></h3>'));

        // Un espacio en blanco no cuenta como texto; una palabra sí.
        $this->assertSame(
            '<p>y <a href="https://y.test">este sí</a></p>',
            RichText::clean('<p><a href="https://x.test"> </a>y <a href="https://y.test">este sí</a></p>')
        );
    }

    public function test_un_cuerpo_vacio_no_es_contenido(): void
    {
        $this->assertNull(RichText::clean('<p><br></p>'));
        $this->assertNull(RichText::normalizeLegacy(''));
    }

    /**
     * El texto plano es el mismo que arma el portal.
     *
     * No hace falta adivinarlo: su API publica en `metaDescription` el resumen
     * que enseña en la tarjeta. Estos dos casos son literales —cuerpo y resumen
     * tal como los devuelve— y el resultado coincide carácter a carácter,
     * espacios dobles incluidos.
     */
    public function test_el_texto_plano_reproduce_el_resumen_del_portal(): void
    {
        // INVIMA, del «Directorio de entidades»: una dirección escrita a salto
        // de línea, sin una sola etiqueta dentro del párrafo.
        $this->assertSame(
            "Tipo de control: Inspección y Vigilancia\nCarrera 10 #64 - 28, Bogotá D.C.\n"
                ."(+57) 601 742 2121\nsoytransparente@invima.gov.co\n",
            RichText::toPlainText(
                "<p>Tipo de control: Inspección y Vigilancia\nCarrera 10 #64 - 28, Bogotá D.C.\n"
                    ."(+57) 601 742 2121\nsoytransparente@invima.gov.co</p>"
            )
        );

        // Servicio Geológico Colombiano: la misma dirección, pero escrita con
        // <br> Y con salto detrás. El <br> no cuenta —si contara, saldría una
        // línea en blanco entre cada renglón—.
        $this->assertSame(
            "Tipo de Control: Inspección y Vigilancia \nCarrera 50 # 26-20 Bogotá D.C Colombia  \n"
                ."(+57) 601 2200000 \nrelacionciudadana@sgc.gov.co \n\n \n",
            RichText::toPlainText(
                "<p>Tipo de Control: Inspección y Vigilancia <br>\nCarrera 50 # 26-20 Bogotá D.C Colombia  <br>\n"
                    ."(+57) 601 2200000 <br>\nrelacionciudadana@sgc.gov.co \n\n </p>"
            )
        );
    }

    /**
     * Un <br> no separa; un bloque que cierra, sí.
     *
     * En la tarjeta de «Valores y Principios Corporativos» se lee «…servicios
     * de salud:Destacarse por la calidad…»: el <br> que hay en medio no separa
     * nada. Se reproduce igual, con las palabras pegadas incluidas.
     */
    public function test_un_br_no_separa_pero_un_bloque_que_cierra_si(): void
    {
        $this->assertSame(
            "Principios Corporativos\n\n1. Liderazgo:Destacarse\n",
            RichText::toPlainText(
                '<p>Principios Corporativos</p><p><img src="x"></p><p><br><!--StartFragment-->'
                    .'<b>1. Liderazgo:</b><br><span>Destacarse</span></p>'
            )
        );
    }

    /** El blanco entre dos etiquetas es sangrado del HTML y no cuenta. */
    public function test_el_sangrado_entre_etiquetas_no_deja_saltos(): void
    {
        $this->assertSame(
            "Oficina Control Interno\nEvaluar el sistema\nde la entidad\n",
            RichText::toPlainText(
                "<h2><b>Oficina Control Interno</b></h2>\n"
                    ."<p><span>Evaluar el sistema\nde la entidad</span><br></p>"
            )
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
        $html = "<p>Tipo de control: Inspección y Vigilancia\nCarrera 10 #64 - 28\n(+57) 601 742 2121</p>";

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
     * Un cuerpo envuelto en <article> no puede desaparecer.
     *
     * El saneador borra la etiqueta que no conoce JUNTO CON SU CONTENIDO. El
     * editor del portal pegó el texto de «Respuesta del caso Nº 0993312025»
     * dentro de un <article class="content-descri"> y la importación lo guardó
     * vacío: 424 notificaciones con texto y una en blanco.
     */
    public function test_un_cuerpo_envuelto_en_un_bloque_desconocido_sobrevive(): void
    {
        $envoltorios = ['article', 'section', 'main', 'header', 'footer', 'aside', 'label'];

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

    /**
     * El caso real del <label>, con el enlace dentro.
     *
     * El editor del portal lo usa para colgarle una clase a un enlace, sin
     * formulario ninguno al que etiquetar. En «Plan Anual de Adquisiciones V3
     * -2024» envolvía el único enlace útil del contenido y se lo llevaba por
     * delante: quedaba el título suelto y cuatro saltos de línea.
     */
    public function test_un_enlace_envuelto_en_label_no_se_pierde(): void
    {
        $html = '<p>Plan Anual de Adquisiciones V3 -2024</p><p><br>'
            .'<label class="BreadCrumbLabel" data-translated="true">'
            .'<a href="https://community.secop.gov.co/Public/App/AnnualPurchasingPlanEditPublic/View?id=417273" target="">'
            .'Ver plan anual de adquisiciones</a><b>-SECOP II</b><br></label></p>';

        $limpio = (string) RichText::clean(RichText::normalizeLegacy($html));

        $this->assertStringContainsString('Ver plan anual de adquisiciones', $limpio);
        $this->assertStringContainsString('community.secop.gov.co', $limpio);
        $this->assertStringContainsString('<strong>-SECOP II</strong>', $limpio);
    }
}
