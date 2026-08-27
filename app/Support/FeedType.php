<?php

namespace App\Support;

use App\Models\TopicItem;

/**
 * Los tipos con los que se filtra el muro de la portada.
 *
 * Son los mismos seis del portal de origen y en su orden —comprobado en su
 * propia página: la barra dice «Filtrar por fecha | Todos los contenidos» y ese
 * segundo despliega Noticia, Documento, Convocatoria, Evento, Link y
 * Clasificados—. Su API confirma que el muro es una mezcla: cada contenido
 * llega con su `contentType`.
 *
 * Aquí solo viven los tipos y la correspondencia con lo nuestro. Quién entra en
 * el muro lo decide HomeController.
 */
class FeedType
{
    public const NEWS = 'Noticia';

    public const DOCUMENT = 'Documento';

    public const CONVOCATION = 'Convocatoria';

    public const EVENT = 'Evento';

    public const LINK = 'Link';

    /**
     * El sexto del portal de origen.
     *
     * Aquí no le corresponde nada: el hospital nunca publicó un clasificado, y
     * por eso no hay ninguna clase de elemento que caiga en él. Se declara igual
     * para que el día que exista solo haya que apuntarlo en el mapa de abajo, y
     * porque la lista de tipos del portal es la que es.
     */
    public const CLASSIFIED = 'Clasificados';

    /** En el orden en que los enseña el portal de origen. */
    public const ALL = [
        self::NEWS,
        self::DOCUMENT,
        self::CONVOCATION,
        self::EVENT,
        self::LINK,
        self::CLASSIFIED,
    ];

    /**
     * Qué tipo le toca a cada clase de elemento de tema.
     *
     * Los que no están aquí —«pregunta», «trámite»— no son contenido del muro
     * en el portal de origen y tampoco lo son aquí: una pregunta frecuente y un
     * trámite se leen en su sección, no entre las novedades.
     *
     * «aviso» va con las noticias: es título y texto, sin archivo ni fechas, o
     * sea un artículo corto. Es la lectura conservadora; el origen no distingue
     * esa clase, así que no hay a qué otra cosa mandarlo.
     */
    private const FROM_KIND = [
        TopicItem::KIND_ARTICLE => self::NEWS,
        TopicItem::KIND_NOTICE => self::NEWS,
        TopicItem::KIND_DOCUMENT => self::DOCUMENT,
        TopicItem::KIND_CONVOCATION => self::CONVOCATION,
        TopicItem::KIND_EVENT => self::EVENT,
        TopicItem::KIND_LINK => self::LINK,
    ];

    /** El tipo de una clase de elemento, o null si no va al muro. */
    public static function fromKind(?string $kind): ?string
    {
        return self::FROM_KIND[$kind] ?? null;
    }

    /** Las clases de elemento que sí van al muro. */
    public static function feedKinds(): array
    {
        return array_keys(self::FROM_KIND);
    }
}
