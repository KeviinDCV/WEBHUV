<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function imagen(): UploadedFile
    {
        return UploadedFile::fake()->image('banner.jpg', Banner::IMAGE_WIDTH, Banner::IMAGE_HEIGHT);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'media_type' => 'image',
            'filter_color' => '#000000',
            'filter_opacity' => 30,
            'title' => 'Título del banner',
            'title_color' => '#FFFFFF',
            'title_font' => 'Montserrat',
            'subtitle' => 'Subtítulo del banner',
            'subtitle_color' => '#FFFFFF',
            'subtitle_font' => 'Montserrat',
            'alignment' => 'left',
            'alt_text' => 'Descripción accesible del banner',
            'link' => 'https://www.huv.gov.co/noticias/ejemplo',
            'image' => $this->imagen(),
        ], $overrides);
    }

    private function crearBanner(array $overrides = []): Banner
    {
        return Banner::create(array_merge([
            'position' => 1,
            'image_path' => 'banners/ejemplo.jpg',
            'alt_text' => 'Banner de ejemplo',
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* Acceso                                                              */
    /* ------------------------------------------------------------------ */

    public function test_la_administracion_exige_sesion_iniciada(): void
    {
        $banner = $this->crearBanner();

        $this->get('/administracion/banners')->assertRedirect(route('login'));
        $this->get('/administracion/banners/nuevo')->assertRedirect(route('login'));
        $this->get("/administracion/banners/{$banner->id}/editar")->assertRedirect(route('login'));
        $this->post('/administracion/banners', [])->assertRedirect(route('login'));
        $this->delete("/administracion/banners/{$banner->id}")->assertRedirect(route('login'));

        $this->assertDatabaseCount('banners', 1);
    }

    public function test_el_listado_muestra_los_banners_en_orden(): void
    {
        $this->crearBanner(['position' => 2, 'alt_text' => 'Segundo banner']);
        $this->crearBanner(['position' => 1, 'alt_text' => 'Primer banner']);

        $html = $this->actingAs($this->editor())->get('/administracion/banners')->getContent();

        $this->assertLessThan(
            strpos($html, 'Segundo banner'),
            strpos($html, 'Primer banner'),
            'El banner con posición 1 debe aparecer antes.'
        );
    }

    /* ------------------------------------------------------------------ */
    /* Alta                                                                */
    /* ------------------------------------------------------------------ */

    public function test_el_formulario_de_alta_se_muestra(): void
    {
        $this->actingAs($this->editor())
            ->get('/administracion/banners/nuevo')
            ->assertOk()
            ->assertSee('Configuración del banner', false)
            ->assertSee('Vista previa', false)
            ->assertSee('Texto descriptivo para accesibilidad', false)
            ->assertSee(Banner::IMAGE_WIDTH.' × '.Banner::IMAGE_HEIGHT, false)
            // Al crear no hay nada que eliminar.
            ->assertDontSee('Eliminar banner', false);
    }

    public function test_el_formulario_de_edicion_precarga_los_datos(): void
    {
        $banner = $this->crearBanner([
            'title' => 'Congreso de neurociencias',
            'subtitle' => 'Del contexto al cerebro',
            'alt_text' => 'Afiche del congreso',
            'link' => 'https://www.huv.gov.co/congreso',
            'filter_opacity' => 45,
        ]);

        $this->actingAs($this->editor())
            ->get("/administracion/banners/{$banner->id}/editar")
            ->assertOk()
            ->assertSee('Congreso de neurociencias', false)
            ->assertSee('Del contexto al cerebro', false)
            ->assertSee('Afiche del congreso', false)
            ->assertSee('https://www.huv.gov.co/congreso', false)
            ->assertSee('Eliminar banner', false);
    }

    public function test_se_puede_agregar_un_banner(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/banners', $this->datos())
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::sole();

        $this->assertSame('Título del banner', $banner->title);
        $this->assertSame('Descripción accesible del banner', $banner->alt_text);
        $this->assertSame(1, $banner->position);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_el_texto_accesible_es_obligatorio(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/banners', $this->datos(['alt_text' => '']))
            ->assertSessionHasErrors('alt_text');

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_la_imagen_es_obligatoria_al_crear(): void
    {
        $datos = $this->datos();
        unset($datos['image']);

        $this->actingAs($this->editor())
            ->post('/administracion/banners', $datos)
            ->assertSessionHasErrors('image');
    }

    public function test_se_rechazan_archivos_que_no_son_imagen(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/banners', $this->datos([
                'image' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_se_rechazan_imagenes_de_mas_de_dos_megas(): void
    {
        $grande = UploadedFile::fake()
            ->image('banner.jpg', Banner::IMAGE_WIDTH, Banner::IMAGE_HEIGHT)
            ->size(2049);

        $this->actingAs($this->editor())
            ->post('/administracion/banners', $this->datos(['image' => $grande]))
            ->assertSessionHasErrors('image');
    }

    public function test_el_enlace_debe_ser_una_url_valida(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/banners', $this->datos(['link' => 'www.huv.gov.co']))
            ->assertSessionHasErrors('link');
    }

    public function test_no_se_pueden_publicar_mas_de_cinco_banners(): void
    {
        for ($i = 1; $i <= Banner::MAX; $i++) {
            $this->crearBanner(['position' => $i, 'alt_text' => "Banner {$i}"]);
        }

        $editor = $this->editor();

        $this->actingAs($editor)->get('/administracion/banners/nuevo')->assertSessionHasErrors('banner');
        $this->actingAs($editor)->post('/administracion/banners', $this->datos())->assertSessionHasErrors('banner');

        $this->assertDatabaseCount('banners', Banner::MAX);
    }

    /* ------------------------------------------------------------------ */
    /* Edición y borrado                                                   */
    /* ------------------------------------------------------------------ */

    public function test_se_puede_editar_sin_reemplazar_la_imagen(): void
    {
        $banner = $this->crearBanner(['image_path' => 'banners/original.jpg']);
        Storage::disk('public')->put('banners/original.jpg', 'contenido');

        $datos = $this->datos(['title' => 'Título corregido']);
        unset($datos['image']);

        $this->actingAs($this->editor())
            ->put("/administracion/banners/{$banner->id}", $datos)
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertSame('Título corregido', $banner->title);
        $this->assertSame('banners/original.jpg', $banner->image_path);
    }

    public function test_al_reemplazar_la_imagen_se_borra_la_anterior(): void
    {
        $banner = $this->crearBanner(['image_path' => 'banners/original.jpg']);
        Storage::disk('public')->put('banners/original.jpg', 'contenido');

        $this->actingAs($this->editor())
            ->put("/administracion/banners/{$banner->id}", $this->datos());

        // Si no se borrara, el disco acumularía archivos que ya no usa nadie.
        Storage::disk('public')->assertMissing('banners/original.jpg');
        Storage::disk('public')->assertExists($banner->refresh()->image_path);
    }

    public function test_al_eliminar_el_banner_se_borra_su_imagen(): void
    {
        $banner = $this->crearBanner(['image_path' => 'banners/borrable.jpg']);
        Storage::disk('public')->put('banners/borrable.jpg', 'contenido');

        $this->actingAs($this->editor())
            ->delete("/administracion/banners/{$banner->id}")
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseCount('banners', 0);
        Storage::disk('public')->assertMissing('banners/borrable.jpg');
    }

    /* ------------------------------------------------------------------ */
    /* Orden y rotación                                                    */
    /* ------------------------------------------------------------------ */

    public function test_se_puede_reordenar_y_ajustar_la_rotacion(): void
    {
        $primero = $this->crearBanner(['position' => 1, 'alt_text' => 'Uno']);
        $segundo = $this->crearBanner(['position' => 2, 'alt_text' => 'Dos']);

        $this->actingAs($this->editor())
            ->post('/administracion/banners/orden', [
                'order' => [$segundo->id, $primero->id],
                'rotation' => 15,
            ])
            ->assertRedirect(route('admin.banners.index'));

        $this->assertSame(1, $segundo->refresh()->position);
        $this->assertSame(2, $primero->refresh()->position);
        $this->assertSame(15, Setting::get('banners.rotation_seconds'));
    }

    public function test_la_rotacion_solo_acepta_valores_previstos(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/banners/orden', ['rotation' => 999])
            ->assertSessionHasErrors('rotation');
    }

    /* ------------------------------------------------------------------ */
    /* Portada                                                             */
    /* ------------------------------------------------------------------ */

    public function test_la_portada_publica_los_banners_guardados(): void
    {
        Setting::put('banners.rotation_seconds', 20);

        $this->crearBanner([
            'position' => 1,
            'alt_text' => 'Congreso de neurociencias',
            'link' => 'https://www.huv.gov.co/congreso',
        ]);

        $response = $this->get('/');

        $response->assertSee('Congreso de neurociencias', false);
        $response->assertSee('https://www.huv.gov.co/congreso', false);
        // La duración configurada llega al carrusel.
        $response->assertSee('seconds: 20', false);
    }

    public function test_sin_banners_la_seccion_sigue_ocupando_su_espacio(): void
    {
        // El hueco del carrusel se reserva siempre: así la portada no se
        // recoloca al publicar el primer banner.
        $this->get('/')
            ->assertOk()
            ->assertSee('id="inicio"', false)
            ->assertSee('aria-label="Banner principal"', false)
            ->assertSee('Banner principal ('.Banner::IMAGE_WIDTH.' × '.Banner::IMAGE_HEIGHT.')', false)
            // Sin diapositivas no hay carrusel que anunciar.
            ->assertDontSee('aria-roledescription="carrusel"', false)
            // Ni atajos de administración para el visitante anónimo.
            ->assertDontSee('Agregar el primer banner', false);

        $this->actingAs($this->editor())->get('/')
            ->assertSee('Agregar el primer banner', false);
    }

    public function test_el_boton_editar_del_banner_lleva_a_su_administracion(): void
    {
        $this->crearBanner();

        $this->actingAs($this->editor())->get('/')
            ->assertSee('data-huv-edit="banner"', false)
            ->assertSee(route('admin.banners.index'), false);
    }
}
