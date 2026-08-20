<?php

namespace App\Support;

/**
 * Participación ciudadana de un contenido, tal como la define el portal.
 *
 * Son tres estados, no las seis etapas de la Ley 1757: un contenido abre su
 * muro a todo el mundo, lo abre solo a quien corresponda, o no lo abre. De aquí
 * sale el botón «Participa» que acompaña a la ficha en los listados.
 *
 * Vive aparte porque lo comparten las noticias y los contenidos de un tema, y
 * el editor es el mismo para ambos.
 */
class CommentWall
{
    public const PUBLICA = 0;

    public const PRIVADA = 1;

    public const NINGUNA = 2;

    /**
     * Opciones del selector, en el mismo orden y con las mismas palabras que el
     * portal institucional.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::NINGUNA => __('mensajes.participacion.ninguna'),
            self::PUBLICA => __('mensajes.participacion.publica'),
            self::PRIVADA => __('mensajes.participacion.privada'),
        ];
    }

    /** @return list<int> */
    public static function values(): array
    {
        return array_keys(self::options());
    }

    /** Abierto a participación: cualquier estado que no sea «ninguna». */
    public static function invites(?int $wall): bool
    {
        return $wall !== null && $wall !== self::NINGUNA;
    }
}
