<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Versiones de una imagen a varios anchos, en WebP.
 *
 * El carrusel de la portada servía un JPEG de 3750 px de ancho para pintarlo a
 * 1280 como mucho: se tiraba el 88 % de los píxeles, y el fichero mayor de la
 * carpeta pesa dos megas. Es el elemento más grande de la portada y el que
 * decide cuándo se considera cargada.
 *
 * Se hace con GD, que ya viene con PHP y admite WebP en esta instalación, en
 * lugar de añadir una biblioteca de imágenes entera para tres redimensionados.
 *
 * Las derivadas viven al lado del original, en un subdirectorio, y llevan el
 * ancho en el nombre. El original NUNCA se toca: es lo que se sirve a un
 * navegador que no entienda WebP, y lo que permite regenerar las derivadas si
 * algún día cambian los tamaños.
 */
class ResponsiveImage
{
    /**
     * Los anchos que se generan.
     *
     * 768 para el móvil, 1280 para el ancho real del contenedor en escritorio,
     * y 1920 para pantallas de mayor densidad. Más allá no se gana nada: el
     * carrusel nunca se pinta más ancho.
     */
    public const WIDTHS = [768, 1280, 1920];

    /** Calidad de WebP. Por encima de 82 el fichero crece y no se nota. */
    private const QUALITY = 82;

    private const SUBDIR = 'derivadas';

    /**
     * Genera las derivadas que falten y devuelve los anchos disponibles.
     *
     * No se generan anchos mayores que el original: agrandar una imagen no
     * añade detalle, solo peso.
     *
     * @return list<int>
     */
    public static function generate(string $path, string $disk = 'public'): array
    {
        $almacen = Storage::disk($disk);

        if (! $almacen->exists($path)) {
            return [];
        }

        $origen = @imagecreatefromstring($almacen->get($path));

        if ($origen === false) {
            return [];
        }

        $anchoOriginal = imagesx($origen);
        $altoOriginal = imagesy($origen);
        $hechos = [];

        foreach (self::WIDTHS as $ancho) {
            if ($ancho > $anchoOriginal) {
                continue;
            }

            $alto = (int) round($altoOriginal * $ancho / $anchoOriginal);
            $destino = imagecreatetruecolor($ancho, $alto);

            // `imagecopyresampled` interpola; `imagecopyresized` no, y deja los
            // bordes dentados en una foto.
            imagecopyresampled($destino, $origen, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

            ob_start();
            imagewebp($destino, null, self::QUALITY);
            $bytes = (string) ob_get_clean();

            imagedestroy($destino);

            if ($bytes !== '') {
                $almacen->put(self::derivativePath($path, $ancho), $bytes);
                $hechos[] = $ancho;
            }
        }

        imagedestroy($origen);

        return $hechos;
    }

    /**
     * El `srcset` de las derivadas que existan en disco.
     *
     * Devuelve nada si no hay ninguna: así la plantilla puede caer al original
     * sin comprobar nada más, y una imagen recién subida antes de generar sus
     * derivadas se sigue viendo.
     */
    public static function srcset(?string $path, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        $almacen = Storage::disk($disk);

        $partes = collect(self::WIDTHS)
            ->filter(fn (int $ancho): bool => $almacen->exists(self::derivativePath($path, $ancho)))
            ->map(fn (int $ancho): string => asset('storage/'.self::derivativePath($path, $ancho)).' '.$ancho.'w');

        return $partes->isEmpty() ? null : $partes->implode(', ');
    }

    /** La derivada más pequeña, que es la que conviene precargar. */
    public static function smallest(?string $path, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        foreach (self::WIDTHS as $ancho) {
            if (Storage::disk($disk)->exists(self::derivativePath($path, $ancho))) {
                return asset('storage/'.self::derivativePath($path, $ancho));
            }
        }

        return null;
    }

    /** Borra las derivadas de una imagen. Se llama al reemplazarla. */
    public static function forget(?string $path, string $disk = 'public'): void
    {
        if (blank($path)) {
            return;
        }

        foreach (self::WIDTHS as $ancho) {
            $derivada = self::derivativePath($path, $ancho);

            if (Storage::disk($disk)->exists($derivada)) {
                Storage::disk($disk)->delete($derivada);
            }
        }
    }

    /** banners/abc.jpg + 1280 → banners/derivadas/abc-1280.webp */
    public static function derivativePath(string $path, int $ancho): string
    {
        $directorio = trim(dirname($path), '.'.DIRECTORY_SEPARATOR);
        $nombre = pathinfo($path, PATHINFO_FILENAME);

        return ($directorio === '' ? '' : $directorio.'/').self::SUBDIR.'/'.$nombre.'-'.$ancho.'.webp';
    }
}
