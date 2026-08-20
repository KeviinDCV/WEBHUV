<?php

namespace App\Models\Concerns;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Un modelo cuyo texto se puede servir en otro idioma.
 *
 * El original nunca se toca: sigue en su columna, en español, que es con el que
 * se busca, se ordena y se reimporta. La traducción vive aparte y solo se
 * antepone al pintar.
 *
 * Quien use este rasgo declara `$translatable` con los campos que tienen texto
 * de lectura. No todos valen: un `slug` forma parte de la dirección y traducirlo
 * rompería los enlaces que ya estén compartidos.
 */
trait Translatable
{
    /** @return MorphMany<Translation, self> */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /** @return list<string> */
    public function translatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * El campo en el idioma en curso.
     *
     * Cae al español siempre que no haya traducción: es preferible una ficha
     * mitad en inglés y mitad en español a una ficha con huecos, y así el sitio
     * funciona igual antes de traducir nada.
     *
     * La traducción se sirve solo si la huella coincide con el texto que hay
     * ahora. Si el portal cambió el contenido y todavía no se ha vuelto a
     * traducir, se enseña el español nuevo y no la traducción del texto viejo,
     * que diría otra cosa.
     */
    public function translated(string $field): ?string
    {
        $original = $this->getAttribute($field);
        $locale = app()->getLocale();

        if ($locale === config('huv.content_locale') || blank($original)) {
            return $original;
        }

        $traduccion = $this->translations
            ->firstWhere(fn (Translation $t): bool => $t->locale === $locale && $t->field === $field);

        return $traduccion && $traduccion->source_hash === Translation::hashOf($original)
            ? $traduccion->value
            : $original;
    }

    /** ¿Lo que se va a pintar sigue estando en español? */
    public function isTranslated(string $field): bool
    {
        return $this->translated($field) !== $this->getAttribute($field);
    }
}
