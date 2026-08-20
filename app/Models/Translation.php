<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un campo traducido de un contenido.
 *
 * No se edita a mano desde ninguna pantalla: lo escribe `huv:traducir` y lo lee
 * el modelo traducido. Si alguien quiere corregir una traducción, el sitio
 * natural sería el editor del propio contenido, y eso todavía no existe.
 */
class Translation extends Model
{
    protected $guarded = [];

    /** @return MorphTo<Model, self> */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /** La huella con la que se decide si hay que volver a traducir. */
    public static function hashOf(?string $source): string
    {
        return sha1((string) $source);
    }
}
