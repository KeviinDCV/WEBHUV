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

        // Un editor vacío deja restos como <p><br></p>: eso no es contenido.
        return preg_match('/^(<p>(\s|&nbsp;|<br\s*\/?>)*<\/p>)*$/i', $clean) ? null : $clean;
    }

    /** Texto plano del cuerpo, para resúmenes y metadatos. */
    public static function toPlainText(?string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html))) ?? '');
    }
}
