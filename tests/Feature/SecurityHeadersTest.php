<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las cabeceras de seguridad.
 *
 * Ninguna cambia lo que se ve: son instrucciones al navegador sobre lo que
 * puede hacer con la página. Van en el código y no en la configuración del
 * servidor para que viajen con el despliegue y no dependan de que alguien se
 * acuerde de ponerlas.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** Las cuatro, en todas las respuestas. */
    public function test_toda_respuesta_lleva_las_cabeceras(): void
    {
        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeaderMissing('X-Powered-By');
    }

    /** También en las que no son HTML y en las que fallan. */
    public function test_tambien_en_el_mapa_del_sitio_y_en_un_404(): void
    {
        foreach (['/sitemap.xml', '/robots.txt', '/tema/no-existe-xyz'] as $ruta) {
            $this->get($ruta)->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    /**
     * Los permisos que el sitio no usa van cerrados.
     *
     * Comprobado en resources/js: no hay cámara, micrófono, ubicación ni pagos.
     * Cerrarlos evita que un script de terceros los pida en nombre del hospital.
     */
    public function test_los_permisos_que_no_se_usan_van_cerrados(): void
    {
        $politica = $this->get('/')->assertOk()->headers->get('Permissions-Policy');

        $this->assertNotNull($politica);

        foreach (['camera', 'microphone', 'geolocation', 'payment'] as $permiso) {
            $this->assertStringContainsString($permiso.'=()', $politica);
        }
    }

    /**
     * La HSTS solo sobre HTTPS, y sin incluir los subdominios.
     *
     * «cross.huv.gov.co» —el formulario de solicitudes que enlaza el propio
     * portal— se sirve por HTTP plano: con `includeSubDomains`, el navegador se
     * negaría a abrirlo en cuanto alguien pasara por aquí.
     */
    public function test_la_hsts_no_arrastra_a_los_subdominios(): void
    {
        // Por HTTP no se manda: el navegador la ignoraría de todos modos.
        $this->get('/')->assertOk()->assertHeaderMissing('Strict-Transport-Security');

        $segura = $this->get('https://localhost/')->assertOk();

        $hsts = $segura->headers->get('Strict-Transport-Security');

        $this->assertNotNull($hsts, 'Sobre HTTPS sí debe mandarse.');
        $this->assertStringContainsString('max-age=', $hsts);
        $this->assertStringNotContainsString('includeSubDomains', $hsts);
    }
}
