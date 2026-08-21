<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use App\Support\ResponsiveImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las versiones reducidas del banner.
 *
 * El carrusel servía un JPEG de 3750 px de ancho para pintarlo a 1280: se
 * tiraba el 88 % de los píxeles y el fichero mayor pesaba dos megas. Es el
 * elemento más grande de la portada y el que decide cuándo se da por cargada.
 */
class ResponsiveImageTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    /** Un JPEG de verdad del ancho que se pida, no un fichero de mentira. */
    private function jpeg(int $ancho, int $alto): string
    {
        $lienzo = imagecreatetruecolor($ancho, $alto);
        imagefilledrectangle($lienzo, 0, 0, $ancho, $alto, imagecolorallocate($lienzo, 27, 58, 107));

        ob_start();
        imagejpeg($lienzo, null, 90);
        $bytes = (string) ob_get_clean();

        imagedestroy($lienzo);

        return $bytes;
    }

    private function subirBanner(int $ancho = 3750, int $alto = 968): string
    {
        Storage::fake('public');

        $ruta = 'banners/original.jpg';
        Storage::disk('public')->put($ruta, $this->jpeg($ancho, $alto));

        return $ruta;
    }

    /* ------------------------------------------------------------------ */

    public function test_genera_las_tres_versiones_en_webp(): void
    {
        $ruta = $this->subirBanner();

        $this->assertSame([768, 1280, 1920], ResponsiveImage::generate($ruta));

        foreach ([768, 1280, 1920] as $ancho) {
            $derivada = ResponsiveImage::derivativePath($ruta, $ancho);

            $this->assertTrue(Storage::disk('public')->exists($derivada), 'Falta la de '.$ancho);

            // Que sea WebP de verdad, y del ancho pedido.
            $imagen = imagecreatefromstring(Storage::disk('public')->get($derivada));

            $this->assertNotFalse($imagen);
            $this->assertSame($ancho, imagesx($imagen));
        }
    }

    /** La proporción se respeta: un banner estirado se vería deformado. */
    public function test_conserva_la_proporcion(): void
    {
        $ruta = $this->subirBanner(3750, 968);

        ResponsiveImage::generate($ruta);

        $imagen = imagecreatefromstring(
            Storage::disk('public')->get(ResponsiveImage::derivativePath($ruta, 1280))
        );

        $this->assertSame(1280, imagesx($imagen));
        $this->assertSame((int) round(968 * 1280 / 3750), imagesy($imagen));
    }

    /**
     * No se agranda una imagen pequeña.
     *
     * Ampliar no añade detalle, solo peso: una imagen de 900 px solo genera la
     * de 768.
     */
    public function test_no_agranda_una_imagen_menor_que_el_ancho_pedido(): void
    {
        $ruta = $this->subirBanner(900, 300);

        $this->assertSame([768], ResponsiveImage::generate($ruta));
        $this->assertFalse(Storage::disk('public')->exists(ResponsiveImage::derivativePath($ruta, 1280)));
    }

    /** El original no se toca nunca: es la reserva y la fuente para rehacerlas. */
    public function test_el_original_no_se_modifica(): void
    {
        $ruta = $this->subirBanner();
        $antes = Storage::disk('public')->get($ruta);

        ResponsiveImage::generate($ruta);

        $this->assertSame($antes, Storage::disk('public')->get($ruta));
    }

    /**
     * Las derivadas pesan una fracción del original, y van comprimidas.
     *
     * Se usa una imagen con ruido y no un rectángulo liso: WebP deja un color
     * plano en cuatro bytes con cualquier calidad, así que con una imagen lisa
     * la prueba pasaría igual sin comprimir nada, y no diría nada.
     */
    public function test_la_version_menor_pesa_mucho_menos_y_va_comprimida(): void
    {
        Storage::fake('public');

        $ruta = 'banners/ruido.jpg';
        Storage::disk('public')->put($ruta, $this->ruido(2000, 516));

        ResponsiveImage::generate($ruta);

        $original = Storage::disk('public')->size($ruta);
        $menor = Storage::disk('public')->size(ResponsiveImage::derivativePath($ruta, 768));

        $this->assertLessThan($original / 2, $menor, 'La derivada debería pesar bastante menos.');

        // Y frente a la misma reducción SIN comprimir: si se guardara a calidad
        // máxima, el fichero sería sensiblemente mayor.
        $this->assertLessThan(
            $this->pesoSinComprimir($ruta, 768) * 0.8,
            $menor,
            'La derivada no parece estar comprimida.'
        );
    }

    /** El mismo redimensionado guardado a calidad máxima, para comparar. */
    private function pesoSinComprimir(string $ruta, int $ancho): int
    {
        $origen = imagecreatefromstring(Storage::disk('public')->get($ruta));
        $alto = (int) round(imagesy($origen) * $ancho / imagesx($origen));
        $destino = imagecreatetruecolor($ancho, $alto);

        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $ancho, $alto, imagesx($origen), imagesy($origen));

        ob_start();
        imagewebp($destino, null, 100);
        $bytes = strlen((string) ob_get_clean());

        imagedestroy($destino);
        imagedestroy($origen);

        return $bytes;
    }

    /** Una imagen con ruido, donde la calidad de compresión sí se nota. */
    private function ruido(int $ancho, int $alto): string
    {
        $lienzo = imagecreatetruecolor($ancho, $alto);

        // Bloques de 4 px: suficiente detalle para que comprimir importe, y
        // rápido de generar. Sin aleatoriedad, para que la prueba no varíe.
        for ($x = 0; $x < $ancho; $x += 4) {
            for ($y = 0; $y < $alto; $y += 4) {
                $color = imagecolorallocate($lienzo, ($x * 7) % 256, ($y * 13) % 256, ($x + $y) % 256);
                imagefilledrectangle($lienzo, $x, $y, $x + 3, $y + 3, $color);
            }
        }

        ob_start();
        imagejpeg($lienzo, null, 95);
        $bytes = (string) ob_get_clean();

        imagedestroy($lienzo);

        return $bytes;
    }

    /* ------------------------------------------------------------------ */

    /** Sin derivadas no hay srcset: la plantilla cae al original sin más. */
    public function test_sin_derivadas_no_hay_srcset(): void
    {
        $ruta = $this->subirBanner();

        $this->assertNull(ResponsiveImage::srcset($ruta));
        $this->assertNull(ResponsiveImage::srcset(null));

        ResponsiveImage::generate($ruta);

        $srcset = ResponsiveImage::srcset($ruta);

        $this->assertNotNull($srcset);
        foreach ([768, 1280, 1920] as $ancho) {
            $this->assertStringContainsString($ancho.'w', $srcset);
        }
    }

    public function test_al_reemplazar_la_imagen_se_borran_sus_derivadas(): void
    {
        $ruta = $this->subirBanner();
        ResponsiveImage::generate($ruta);

        ResponsiveImage::forget($ruta);

        foreach ([768, 1280, 1920] as $ancho) {
            $this->assertFalse(Storage::disk('public')->exists(ResponsiveImage::derivativePath($ruta, $ancho)));
        }
    }

    /* ------------------------------------------------------------------ */
    /* La portada                                                          */
    /* ------------------------------------------------------------------ */

    /** Con derivadas, el carrusel ofrece WebP y deja el original de reserva. */
    public function test_el_carrusel_ofrece_webp_y_conserva_la_reserva(): void
    {
        $ruta = $this->subirBanner();
        ResponsiveImage::generate($ruta);

        Banner::create(['image_path' => $ruta, 'alt_text' => 'Banner de prueba', 'position' => 1, 'is_active' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('sizes="100vw"', $html);
        // El original sigue ahí para quien no entienda WebP.
        $this->assertStringContainsString('original.jpg', $html);
    }

    /** Sin derivadas todavía, la imagen se sigue viendo. */
    public function test_sin_derivadas_el_carrusel_sigue_funcionando(): void
    {
        $ruta = $this->subirBanner();

        Banner::create(['image_path' => $ruta, 'alt_text' => 'Banner de prueba', 'position' => 1, 'is_active' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('original.jpg', $html);
        $this->assertStringNotContainsString('type="image/webp"', $html);
    }

    /**
     * El primero se precarga; los demás no.
     *
     * Su etiqueta está detrás del menú completo, en el byte doscientos mil del
     * HTML: sin anunciarlo en la cabecera, el navegador no se entera de que
     * existe hasta haber leído todo eso.
     */
    public function test_solo_el_primer_banner_se_precarga(): void
    {
        Storage::fake('public');

        foreach ([1, 2] as $posicion) {
            $ruta = 'banners/banner-'.$posicion.'.jpg';
            Storage::disk('public')->put($ruta, $this->jpeg(3750, 968));
            ResponsiveImage::generate($ruta);

            Banner::create(['image_path' => $ruta, 'alt_text' => 'Banner '.$posicion, 'position' => $posicion, 'is_active' => true]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'as="image"'), 'Debe precargarse exactamente uno.');

        // Y tiene que ser EL PRIMERO. Contar cuántas precargas hay no basta:
        // precargar el último también sería una sola, y sería el banner que no
        // se ve. Se mira la etiqueta en sí, no la página entera, porque los dos
        // banners aparecen en ella de todos modos.
        preg_match('~<link rel="preload" as="image".*?>~s', $html, $etiqueta);

        $this->assertNotEmpty($etiqueta, 'No se encontró la etiqueta de precarga.');
        $this->assertStringContainsString('banner-1-768.webp', $etiqueta[0]);
        $this->assertStringNotContainsString('banner-2', $etiqueta[0]);
    }

    /** Y en ninguna otra página, que no tienen carrusel. */
    public function test_las_demas_paginas_no_precargan_ninguna_imagen(): void
    {
        $ruta = $this->subirBanner();
        ResponsiveImage::generate($ruta);
        Banner::create(['image_path' => $ruta, 'alt_text' => 'Banner', 'position' => 1, 'is_active' => true]);

        foreach ([route('transparency'), route('branches'), route('contact')] as $pagina) {
            $this->get($pagina)->assertOk()->assertDontSee('as="image"', false);
        }
    }

    /* ------------------------------------------------------------------ */

    /** Al subir un banner desde el panel, sus versiones se generan solas. */
    public function test_al_subir_un_banner_se_generan_sus_versiones(): void
    {
        Storage::fake('public');

        $this->actingAs($this->editor())
            ->post(route('admin.banners.store'), [
                'media_type' => 'image',
                'image' => UploadedFile::fake()->image('nuevo.jpg', 2000, 516),
                'alt_text' => 'Un banner nuevo',
                'filter_color' => '#000000',
                'filter_opacity' => 0,
                'title_color' => '#FFFFFF',
                'subtitle_color' => '#FFFFFF',
                'title_font' => 'Montserrat',
                'subtitle_font' => 'Montserrat',
                'alignment' => 'left',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $banner = Banner::firstOrFail();

        foreach ([768, 1280, 1920] as $ancho) {
            $this->assertTrue(
                Storage::disk('public')->exists(ResponsiveImage::derivativePath($banner->image_path, $ancho)),
                'Falta la versión de '.$ancho.' px'
            );
        }
    }
}
