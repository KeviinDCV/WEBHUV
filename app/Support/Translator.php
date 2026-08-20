<?php

namespace App\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Traducción de textos con la API de Google Cloud Translation.
 *
 * Se usa desde `huv:traducir`, nunca al pintar una página: traducir en caliente
 * ataría cada visita a que un servicio de fuera responda, y una caída suya
 * dejaría el portal sin contenido. Aquí se traduce una vez, se guarda y se
 * sirve como cualquier otro texto.
 *
 * El formato es HTML y no texto plano: los cuerpos llevan etiquetas y la API
 * las respeta si se le dice, mientras que en modo texto las devolvería
 * escapadas y el cuerpo saldría enseñando sus propios «<p>» en pantalla.
 */
class Translator
{
    /** El máximo que admite la API por petición. */
    private const MAX_SEGMENTS = 128;

    /**
     * Tope de caracteres por petición.
     *
     * La API admite bastante más, pero un cuerpo largo con su HTML se acerca
     * deprisa: partiendo por aquí, un lote grande no se pierde entero por un
     * único texto que se pase de la raya.
     */
    private const MAX_CHARS = 28000;

    public function __construct(
        private readonly ?string $key = null,
        private readonly string $endpoint = 'https://translation.googleapis.com/language/translate/v2',
    ) {}

    public static function make(): self
    {
        return new self(config('services.google_translate.key'));
    }

    public function configured(): bool
    {
        return filled($this->key);
    }

    /**
     * Traduce una tanda de textos, en el mismo orden en que llegan.
     *
     * @param  list<string>  $textos
     * @return list<string>
     */
    public function translate(array $textos, string $to, ?string $from = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Falta la clave de la API de traducción (GOOGLE_TRANSLATE_KEY).'
            );
        }

        if ($textos === []) {
            return [];
        }

        $from ??= config('huv.content_locale', 'es');

        $salida = [];

        foreach ($this->batches($textos) as $lote) {
            foreach ($this->request($lote, $to, $from) as $traducido) {
                $salida[] = $traducido;
            }
        }

        return $salida;
    }

    /**
     * Reparte los textos en peticiones que la API acepte.
     *
     * @param  list<string>  $textos
     * @return list<list<string>>
     */
    private function batches(array $textos): array
    {
        $lotes = [];
        $actual = [];
        $largo = 0;

        foreach ($textos as $texto) {
            $suyo = mb_strlen($texto);

            // Un texto que por sí solo pasa del tope va en su propia petición:
            // partirlo por la mitad rompería su HTML.
            if ($actual !== [] && (count($actual) >= self::MAX_SEGMENTS || $largo + $suyo > self::MAX_CHARS)) {
                $lotes[] = $actual;
                $actual = [];
                $largo = 0;
            }

            $actual[] = $texto;
            $largo += $suyo;
        }

        if ($actual !== []) {
            $lotes[] = $actual;
        }

        return $lotes;
    }

    /**
     * @param  list<string>  $lote
     * @return list<string>
     */
    private function request(array $lote, string $to, string $from): array
    {
        $respuesta = Http::asForm()
            ->timeout(120)
            // La API devuelve 429 y 503 cuando se le pide mucho seguido; con
            // una tanda de mil textos eso pasa, y perder el lote significa
            // pagar dos veces por lo mismo.
            ->retry(3, 2000, throw: false)
            ->post($this->endpoint, [
                'key' => $this->key,
                'source' => $from,
                'target' => $to,
                'format' => 'html',
                'q' => $lote,
            ]);

        if (! $respuesta->successful()) {
            throw new RequestException($respuesta);
        }

        $traducciones = $respuesta->json('data.translations', []);

        if (count($traducciones) !== count($lote)) {
            // Si no vuelven tantas como se mandaron, no se puede saber cuál es
            // cuál: guardar así emparejaría el cuerpo de un contenido con el
            // título de otro.
            throw new RuntimeException(
                'La API devolvió '.count($traducciones).' traducciones para '.count($lote).' textos.'
            );
        }

        return array_map(
            fn (array $t): string => html_entity_decode((string) ($t['translatedText'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $traducciones
        );
    }
}
