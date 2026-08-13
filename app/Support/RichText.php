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

        // Los saltos de línea del texto se dejan como están.
        //
        // Media docena de contenidos del «Directorio de entidades» son bloques
        // de dirección escritos a salto de línea y sin una sola etiqueta. Da la
        // tentación de convertirlos en <br>, pero el portal no lo hace: en su
        // ficha, «…autónoma y objetiva\nde la entidad…» se lee de corrido, en
        // un solo párrafo. Los renglones sueltos solo salen en el resumen de la
        // tarjeta, y de eso se encarga toPlainText().

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
     * Texto plano del cuerpo, tal como lo arma el portal para sus resúmenes.
     *
     * No es una interpretación: la API publica en `metaDescription` el mismo
     * resumen que enseña en la tarjeta, y comparándolo con el cuerpo de cuatro
     * contenidos distintos salen exactamente estas cuatro reglas.
     *
     *   · El blanco que hay ENTRE dos etiquetas es sangrado del HTML y se tira.
     *   · Cada bloque que cierra —</p>, </div>, </h2>…— deja un salto de línea.
     *   · <br> no deja nada. Por eso «…servicios de salud:</b><br><span>
     *     Destacarse…» sale pegado en la tarjeta de «Valores y Principios
     *     Corporativos», y así tiene que quedar.
     *   · Todo lo demás se respeta: los saltos que el texto trae escritos y los
     *     espacios, incluidos los dobles.
     *
     * Quien necesite una línea sin más —una etiqueta <meta>— que use
     * toSingleLine().
     */
    public static function toPlainText(?string $html): string
    {
        $text = preg_replace('~>\s+<~u', '><', (string) $html);
        $text = preg_replace('~</(?:p|div|h[1-6]|li|tr|blockquote|pre)\s*>~i', "\n", (string) $text);

        $text = html_entity_decode(strip_tags((string) $text));

        // Un cuerpo que solo tiene párrafos vacíos daría una ristra de saltos
        // que se pinta como un hueco: para eso, mejor nada.
        return trim((string) $text) === '' ? '' : (string) $text;
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
     *
     * El corte sí recorta los blancos de los extremos, que es lo que se ve en
     * el portal: el resumen empieza en la primera palabra.
     */
    public static function excerpt(?string $html, int $characters): string
    {
        $text = trim(self::toPlainText($html));

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
