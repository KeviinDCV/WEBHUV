<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * El idioma del contenido del portal, para declararlo en el marcado.
 *
 * El hospital publica en español y va a seguir publicando en español: lo que se
 * ofrece en inglés es la interfaz. Una página en inglés lleva por tanto texto
 * español dentro, y hay que decirlo en el HTML —criterio 3.1.2 de WCAG, «idioma
 * de las partes»— o un lector de pantalla en inglés lo leerá con fonética
 * inglesa y saldrá ininteligible. El traductor del navegador, además, daría ese
 * texto por inglés y no lo traduciría, que es justo lo que se le está pidiendo.
 *
 * Cuando la página ya está en español no se marca nada: repetir `lang="es"`
 * dentro de un documento que ya es español es ruido, y con la marca puesta
 * siempre no habría forma de distinguir lo que de verdad cambia de idioma.
 */
class PortalLang
{
    /** ¿La página se está sirviendo en un idioma distinto al del contenido? */
    public static function differs(): bool
    {
        return app()->getLocale() !== config('huv.content_locale');
    }

    /**
     * El atributo suelto, para los elementos que se escriben a mano.
     *
     * Se usa donde el texto español viaja en un atributo —`alt`, `title`,
     * `aria-label`— y envolverlo no vale: el idioma de un atributo es el del
     * elemento que lo lleva, así que la marca va en ese mismo elemento.
     */
    public static function attribute(): HtmlString
    {
        return new HtmlString(self::differs() ? ' lang="'.e(config('huv.content_locale')).'"' : '');
    }

    /**
     * Un texto del portal incrustado en una frase traducida.
     *
     * «Participate in :titulo» es una frase inglesa con un titular español
     * dentro. Partir la clave en dos —«Participate in» + el título— dejaría la
     * traducción a merced del orden de las palabras, que en otras lenguas no es
     * el mismo. Así que se marca solo el fragmento y la frase sigue entera.
     *
     * Devuelve HTML, de modo que quien lo use imprime con {!! !!}. El texto se
     * escapa aquí dentro: nunca sale sin pasar por e().
     */
    public static function wrap(?string $text): HtmlString
    {
        $escaped = e((string) $text);

        return new HtmlString(self::differs()
            ? '<span lang="'.e(config('huv.content_locale')).'">'.$escaped.'</span>'
            : $escaped);
    }

    /**
     * Lo mismo en forma de arreglo, para fundirlo con una bolsa de atributos.
     *
     * @return array<string, string>
     */
    public static function marks(): array
    {
        return self::differs() ? ['lang' => config('huv.content_locale')] : [];
    }
}
