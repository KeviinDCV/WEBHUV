<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Rótulo traducido de una entrada de `config/huv.php`.
 *
 * La configuración no puede llamar a `__()`: el contenedor la lee una vez por
 * petición —y con `config:cache`, una vez y para siempre— cuando el middleware
 * todavía no ha fijado el idioma. Por eso cada entrada con rótulo declara en
 * 'i18n' una clave de traducción estable y es la vista la que pide el texto.
 *
 * Sin traducción se devuelve el rótulo escrito en la configuración. Es a
 * propósito: el menú, el pie y el índice de Transparencia no pueden quedarse en
 * blanco porque a alguien se le olvide traducir una entrada nueva, y en español
 * —el idioma del portal— el original ya es el texto bueno.
 */
class ConfigLabel
{
    /**
     * @param  array<string, mixed>  $entry  La entrada de configuración.
     * @param  string  $field  El campo del que sale el rótulo de reserva.
     * @param  string|null  $suffix  Nombre en español del campo, cuando la
     *                               entrada tiene varios textos y 'i18n' es el
     *                               prefijo del que cuelgan todos.
     */
    public static function of(array $entry, string $field = 'label', ?string $suffix = null): string
    {
        $original = (string) ($entry[$field] ?? '');
        $key = $entry['i18n'] ?? null;

        if (! is_string($key) || $key === '') {
            return $original;
        }

        return self::text($suffix === null ? $key : $key.'.'.$suffix, $original);
    }

    /**
     * El rótulo listo para imprimir, con su idioma declarado si hace falta.
     *
     * Buena parte de las entradas de la configuración no se traducen porque son
     * nombres propios: «Personería Municipal de Tuluá», «Gaceta Oficial». Esas
     * se quedan en español a propósito, y por eso mismo hay que decirlo en el
     * marcado cuando la página va en otro idioma (WCAG 3.1.2).
     *
     * Se decide aquí y no en la vista porque aquí es donde se sabe si el texto
     * que sale es la traducción o el original: una entrada nueva sin traducir
     * queda marcada sola, sin que nadie tenga que acordarse.
     *
     * @param  array<string, mixed>  $entry
     */
    public static function marked(array $entry, string $field = 'label', ?string $suffix = null): HtmlString
    {
        $original = (string) ($entry[$field] ?? '');
        $label = self::of($entry, $field, $suffix);

        return $label === $original ? PortalLang::wrap($label) : new HtmlString(e($label));
    }

    /**
     * Un elemento de una lista de cadenas sueltas.
     *
     * «Servicios y especialidades» y el mosaico de Transparencia se escriben
     * como listas de texto, sin sitio donde poner un 'i18n' por elemento, así
     * que la clave se compone con la del bloque y la posición en la lista.
     *
     * @param  array<string, mixed>  $block  El bloque que contiene la lista.
     */
    public static function item(array $block, string $list, int $position, string $original): string
    {
        $key = $block['i18n'] ?? null;

        if (! is_string($key) || $key === '') {
            return $original;
        }

        return self::text($key.'.'.$list.'.'.$position, $original);
    }

    /** Traducción de una clave, con el rótulo de la configuración de reserva. */
    private static function text(string $key, string $original): string
    {
        $translated = __($key);

        // `__()` devuelve la clave tal cual cuando no hay traducción, y un
        // array cuando la clave apunta a un grupo entero.
        return is_string($translated) && $translated !== $key ? $translated : $original;
    }
}
