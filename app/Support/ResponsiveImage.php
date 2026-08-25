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

    /**
     * Los de una miniatura de tarjeta.
     *
     * La tarjeta pinta la foto en un hueco de 220×150 en lista y de unos 550 px
     * de ancho en rejilla. 440 cubre los dos con holgura y 880 le da el doble de
     * densidad a una pantalla que lo pida. Servir ahí el original es mandar dos
     * megas para un sello de correo.
     */
    public const CARD_WIDTHS = [440, 880];

    /** Calidad de WebP. Por encima de 82 el fichero crece y no se nota. */
    private const QUALITY = 82;

    private const SUBDIR = 'derivadas';

    /** @var array<string, array{0: int, 1: int}|null> */
    private static array $dimensions = [];

    /**
     * Genera las derivadas que falten y devuelve los anchos disponibles.
     *
     * No se generan anchos mayores que el original: agrandar una imagen no
     * añade detalle, solo peso.
     *
     * @return list<int>
     */
    public static function generate(string $path, string $disk = 'public', ?array $widths = null): array
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

        foreach ($widths ?? self::WIDTHS as $ancho) {
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
    public static function srcset(?string $path, string $disk = 'public', ?array $widths = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $almacen = Storage::disk($disk);

        $partes = collect($widths ?? self::WIDTHS)
            ->filter(fn (int $ancho): bool => $almacen->exists(self::derivativePath($path, $ancho)))
            ->map(fn (int $ancho): string => asset('storage/'.self::derivativePath($path, $ancho)).' '.$ancho.'w');

        return $partes->isEmpty() ? null : $partes->implode(', ');
    }

    /** La derivada más pequeña, que es la que conviene precargar. */
    public static function smallest(?string $path, string $disk = 'public', ?array $widths = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        foreach ($widths ?? self::WIDTHS as $ancho) {
            if (Storage::disk($disk)->exists(self::derivativePath($path, $ancho))) {
                return asset('storage/'.self::derivativePath($path, $ancho));
            }
        }

        return null;
    }

    /** Borra las derivadas de una imagen. Se llama al reemplazarla. */
    public static function forget(?string $path, string $disk = 'public', ?array $widths = null): void
    {
        if (blank($path)) {
            return;
        }

        foreach ($widths ?? self::WIDTHS as $ancho) {
            $derivada = self::derivativePath($path, $ancho);

            if (Storage::disk($disk)->exists($derivada)) {
                Storage::disk($disk)->delete($derivada);
            }
        }
    }

    /**
     * El tamaño real de una imagen, para reservarle su hueco exacto.
     *
     * Hace falta para pintar la foto entera y sin recortarla, que es lo que
     * hace el portal de origen: cada noticia trae la suya con la proporción que
     * tenga —las hay de 4:3, de 3:2 y verticales de 0,79— y la tarjeta se
     * adapta. Sin `width` y `height` de verdad, el navegador no sabe cuánto
     * hueco dejar y la página pega un salto al cargar cada imagen.
     *
     * Se lee del fichero y no de la base a propósito: es una propiedad del
     * archivo, no del contenido, y así no puede quedarse desfasada cuando
     * alguien reemplaza la imagen. `getimagesize` solo lee la cabecera —unos
     * cientos de bytes— y el resultado se guarda para el resto de la petición,
     * que es donde se repetiría: la misma foto sale en la portada, en el tema y
     * en los relacionados.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function dimensions(?string $path, string $disk = 'public'): ?array
    {
        if (blank($path)) {
            return null;
        }

        $almacen = Storage::disk($disk);

        // Solo si el disco es local: sobre almacenamiento remoto esto sería una
        // petición de red por imagen, y entonces sí costaría.
        $absoluta = method_exists($almacen, 'path') ? $almacen->path($path) : null;

        if ($absoluta === null || ! is_file($absoluta)) {
            return null;
        }

        // La clave lleva la fecha y el tamaño del fichero, no solo su ruta: al
        // reemplazar una imagen conservando el nombre, la anterior seguiría
        // guardada y se reservaría el hueco del tamaño viejo.
        $clave = $absoluta.':'.filemtime($absoluta).':'.filesize($absoluta);

        if (array_key_exists($clave, self::$dimensions)) {
            return self::$dimensions[$clave];
        }

        $medidas = @getimagesize($absoluta);

        return self::$dimensions[$clave] = $medidas === false
            ? null
            : [(int) $medidas[0], (int) $medidas[1]];
    }

    /** banners/abc.jpg + 1280 → banners/derivadas/abc-1280.webp */
    public static function derivativePath(string $path, int $ancho): string
    {
        $directorio = trim(dirname($path), '.'.DIRECTORY_SEPARATOR);
        $nombre = pathinfo($path, PATHINFO_FILENAME);

        return ($directorio === '' ? '' : $directorio.'/').self::SUBDIR.'/'.$nombre.'-'.$ancho.'.webp';
    }
}
