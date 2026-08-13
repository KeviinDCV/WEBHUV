<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Depuración del HTML que llega del editor de contenidos.
 *
 * El cuerpo de un contenido se guarda como HTML y se imprime sin escapar, así
 * que hay que limpiarlo al guardar. Confiar en que quien edita «no va a meter
 * un script» no es una defensa: basta con una cuenta comprometida, o con pegar
 * texto copiado de otra web, para dejar un XSS almacenado en el portal.
 *
 * Se depura al guardar y no al mostrar porque así el contenido queda limpio en
 * la base de datos y no depende de que cada vista se acuerde de filtrarlo.
 */
class RichText
{
    /** Etiquetas que el editor puede producir. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'hr' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'sub' => [], 'sup' => [], 'span' => [], 'code' => [], 'pre' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    public static function clean(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            // Sin esquemas de medios permitidos, cualquier <img> se descarta:
            // las imágenes del contenido van por el campo de imagen, no
            // incrustadas en el cuerpo.
            ->forceHttpsUrls(false);

        foreach (self::ALLOWED as $element => $attributes) {
            $config = $attributes === []
                ? $config->allowElement($element)
                : $config->allowElement($element, $attributes);
        }

        $clean = trim((new HtmlSanitizer($config))->sanitize($html));

        // Encabezados sin texto. El editor del portal anterior los deja a
        // pares —«<h3><b><br></b></h3>»— y no son inocuos: quien navega con
        // lector de pantalla salta de encabezado en encabezado y aterriza en
        // uno que no anuncia nada (WCAG 2.4.6).
        $clean = (string) preg_replace(
            '~<(h[234])\b[^>]*>(?:\s|&nbsp;|<(?:br|strong|em|span|u|s)\b[^>]*>|</(?:br|strong|em|span|u|s)>)*</\1>~i',
            '',
            $clean
        );

        // Un editor vacío deja restos como <p><br></p>: eso no es contenido.
        return preg_match('/^(<p>(\s|&nbsp;|<br\s*\/?>)*<\/p>)*$/i', trim($clean)) ? null : trim($clean);
    }

    /**
     * Traduce el HTML del editor del portal anterior al que produce el nuestro.
     *
     * No es cosmético. El saneador descarta la etiqueta no permitida JUNTO CON
     * SU CONTENIDO, no la desenvuelve: un cuerpo entero metido en un <div>
     * desaparece sin dejar rastro ni error. Los artículos del portal usan
     * <div> para casi todo y <b>/<i> para el énfasis, así que sin este paso la
     * importación se comería la mayor parte del texto en silencio.
     *
     * Con expresión regular y no con una sustitución literal: el origen trae
     * «<b >» y etiquetas con atributos que un str_replace dejaría pasar.
     */
    public static function normalizeLegacy(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        // Saltos de línea del texto, a <br>.
        //
        // El editor del portal guarda tal cual lo que se teclea, y al pintarlo
        // convierte esos saltos en <br>. Media docena de contenidos del
        // «Directorio de entidades» son bloques de dirección escritos así, sin
        // una sola etiqueta: «Tipo de control: …\nCarrera 10 #64 - 28…\n(+57)
        // 601 742 2121\nsoytransparente@invima.gov.co». Sin esta conversión el
        // HTML los junta todos en un renglón.
        //
        // Solo los saltos que separan texto de texto: los que hay entre
        // etiquetas —el sangrado del propio HTML— no significan nada, y
        // convertirlos duplicaría los <br> que ya vienen puestos.
        $html = preg_replace('~([^>\s])[ \t]*\r?\n[ \t]*(?=[^<\s])~u', '$1<br>', (string) $html);

        // Envoltorios que se quitan dejando dentro lo que traen.
        //
        // El saneador borra la etiqueta que no conoce JUNTO CON SU CONTENIDO, así
        // que un cuerpo entero envuelto en <article> desaparecía sin más: le
        // pasó a «Respuesta del caso Nº 0993312025», donde el editor del portal
        // pegó el texto dentro de un <article class="content-descri">.
        //
        // Se desenvuelven en vez de convertirse a <p> porque llevan párrafos
        // dentro, y un <p> dentro de otro <p> no es HTML válido.
        $unwrap = ['article', 'section', 'main', 'header', 'footer', 'aside'];

        foreach ($unwrap as $tag) {
            $html = preg_replace('~<\s*/?\s*'.$tag.'(\s[^>]*)?>~i', '', (string) $html);
        }

        $equivalences = [
            'b' => 'strong',
            'i' => 'em',
            'div' => 'p',
            'font' => 'span',
            'center' => 'p',
            'strike' => 's',
        ];

        foreach ($equivalences as $from => $to) {
            $html = preg_replace('~<\s*'.$from.'(\s[^>]*)?>~i', '<'.$to.'>', (string) $html);
            $html = preg_replace('~<\s*/\s*'.$from.'\s*>~i', '</'.$to.'>', (string) $html);
        }

        return $html;
    }

    /**
     * Texto plano del cuerpo, para resúmenes y metadatos.
     *
     * Conserva la forma del texto —un salto por cada <br> y por cada bloque que
     * cierra— por dos motivos:
     *
     * 1. `strip_tags` no separa nada. «…Evaristo García E.S.E.</p><p>Dirección:»
     *    salía como «E.S.E.Dirección», dos palabras hechas una, en 52 resúmenes.
     * 2. El portal las conserva. Media docena de contenidos del «Directorio de
     *    entidades» son bloques de dirección —tipo de control, calle, teléfono,
     *    correo—, y en una sola línea corrida no hay quien los lea.
     *
     * Quien necesite una línea sin más —una etiqueta <meta>— que la colapse.
     */
    public static function toPlainText(?string $html): string
    {
        // El sangrado del HTML no es texto. Un «</h2>\n<p>» tiene un salto que
        // solo está ahí para que el HTML se lea, y contarlo dejaba una línea en
        // blanco de más entre el título y el párrafo que le sigue.
        $text = preg_replace('~>\s*\n\s*<~u', '><', (string) $html);

        $text = preg_replace('~<br\s*/?>~i', "\n", (string) $text);
        $text = preg_replace('~</(?:p|div|h[1-6]|li|tr|blockquote|pre)\s*>~i', "\n", (string) $text);

        $text = html_entity_decode(strip_tags((string) $text));

        // Espacios sí, saltos no. `[^\S\n]` es «blanco que no sea salto de
        // línea»: recoge también el espacio duro que deja el editor del portal.
        $text = preg_replace('~[^\S\n]+~u', ' ', $text);
        $text = preg_replace('~ *\n *~u', "\n", (string) $text);

        // Una línea en blanco como mucho: los cierres encadenados de bloque
        // —«</p></div></li>»— dejarían tres o cuatro seguidas.
        return trim(preg_replace('~\n{3,}~u', "\n\n", (string) $text) ?? '');
    }

    /** El mismo texto en una sola línea, para atributos y metadatos. */
    public static function toSingleLine(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', self::toPlainText($html)) ?? '');
    }

    /**
     * Resumen del cuerpo, cortado sin partir palabras y sin perder las líneas.
     *
     * No sirve `Str::limit(..., preserveWords: true)`: además de cortar,
     * convierte los saltos de línea en espacios, y eso deshace justo lo que
     * `toPlainText()` conserva —los bloques de dirección del «Directorio de
     * entidades» volvían a salir en un solo renglón—.
     */
    public static function excerpt(?string $html, int $characters): string
    {
        $text = self::toPlainText($html);

        if (mb_strlen($text) <= $characters) {
            return $text;
        }

        $cut = mb_substr($text, 0, $characters);

        // Se corta en el último hueco: partir una palabra por la mitad se lee
        // mal y, en un teléfono o un correo, se lee mal y además engaña.
        $espacio = mb_strrpos($cut, ' ');
        $salto = mb_strrpos($cut, "\n");
        $en = max($espacio === false ? 0 : $espacio, $salto === false ? 0 : $salto);

        return rtrim($en > 0 ? mb_substr($cut, 0, $en) : $cut);
    }
}
