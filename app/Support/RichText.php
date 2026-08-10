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

    /** Texto plano del cuerpo, para resúmenes y metadatos. */
    public static function toPlainText(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html))) ?? '');
    }
}
