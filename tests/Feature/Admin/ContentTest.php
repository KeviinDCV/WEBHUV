<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\User;
use App\Support\CommentWall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentTest extends TestCase
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

    private function noticia(array $overrides = []): Content
    {
        return Content::create(array_merge([
            'title' => 'Noticia de ejemplo',
            'category' => Content::NEWS_CATEGORY,
            'excerpt' => 'Resumen de la noticia de ejemplo.',
            'published_at' => now()->subHour(),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Comunicado a la comunidad',
            'category' => Content::NEWS_CATEGORY,
            'excerpt' => 'Resumen del comunicado.',
            'body' => '<p>Cuerpo del comunicado.</p>',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'show_in_feed' => '1',
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */
    /* Acceso                                                              */
    /* ------------------------------------------------------------------ */

    public function test_la_administracion_de_contenidos_exige_sesion(): void
    {
        $content = $this->noticia();

        $this->get('/administracion/contenidos/nuevo')->assertRedirect(route('login'));
        $this->get("/administracion/contenidos/{$content->id}/editar")->assertRedirect(route('login'));
        $this->post('/administracion/contenidos', $this->datos())->assertRedirect(route('login'));
        $this->post("/administracion/contenidos/{$content->id}/destacar")->assertRedirect(route('login'));
        $this->post("/administracion/contenidos/{$content->id}/activar")->assertRedirect(route('login'));
        $this->post("/administracion/contenidos/{$content->id}/ocultar")->assertRedirect(route('login'));
        $this->delete("/administracion/contenidos/{$content->id}")->assertRedirect(route('login'));

        $this->assertDatabaseCount('contents', 1);
        $this->assertTrue($content->refresh()->is_active);
    }

    /* ------------------------------------------------------------------ */
    /* Alta y edición                                                      */
    /* ------------------------------------------------------------------ */

    public function test_se_puede_publicar_un_contenido(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos())
            ->assertRedirect(route('home').'#huv-contenidos');

        $content = Content::sole();

        $this->assertSame('Comunicado a la comunidad', $content->title);
        $this->assertSame('comunicado-a-la-comunidad', $content->slug);
        $this->assertTrue($content->is_active);
    }

    public function test_el_formulario_de_edicion_precarga_los_datos(): void
    {
        $content = $this->noticia(['title' => 'Rendición de cuentas 2025']);

        $this->actingAs($this->editor())
            ->get("/administracion/contenidos/{$content->id}/editar")
            ->assertOk()
            ->assertSee('Actualizar contenido', false)
            ->assertSee('Rendición de cuentas 2025', false)
            ->assertSee('Eliminar contenido', false);
    }

    public function test_sin_fecha_de_visualizacion_el_contenido_no_muestra_fecha(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos(['no_date' => '1']));

        $this->assertNull(Content::sole()->published_at);
    }

    public function test_cada_foto_necesita_su_descripcion(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos([
                'photos' => [UploadedFile::fake()->image('foto.jpg', 1200, 768)],
            ]))
            ->assertSessionHasErrors('photo_alts.0');

        $this->assertDatabaseCount('contents', 0);
    }

    public function test_se_pueden_subir_varias_fotos_y_elegir_la_principal(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'photos' => [
                UploadedFile::fake()->image('uno.jpg', 1200, 768),
                UploadedFile::fake()->image('dos.jpg', 1200, 768),
            ],
            'photo_alts' => ['Fachada del hospital', 'Sala de espera'],
        ]));

        $content = Content::with('media')->sole();

        $this->assertCount(2, $content->images());
        // Sin elección explícita, la primera queda como principal para que las
        // tarjetas de la portada no se queden sin imagen.
        $this->assertSame('Fachada del hospital', $content->mainImage()->alt);
        $this->assertCount(1, $content->images()->where('is_main', true));
    }

    public function test_el_video_debe_ser_de_youtube(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post('/administracion/contenidos', $this->datos(['video_url' => 'https://vimeo.com/12345']))
            ->assertSessionHasErrors('video_url');

        $this->actingAs($editor)->post('/administracion/contenidos', $this->datos([
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]));

        $this->assertSame('dQw4w9WgXcQ', Content::with('media')->sole()->video()->youtubeId());
    }

    public function test_se_pueden_adjuntar_documentos(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'files' => [UploadedFile::fake()->create('resolucion.pdf', 500, 'application/pdf')],
            'file_titles' => ['Resolución 1234 de 2026'],
        ]));

        $file = Content::with('media')->sole()->files()->first();

        $this->assertSame('Resolución 1234 de 2026', $file->alt);
        $this->assertSame('PDF', $file->extension());
        Storage::disk('public')->assertExists($file->path);
    }

    public function test_se_rechazan_formatos_de_archivo_no_previstos(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos([
                'files' => [UploadedFile::fake()->create('programa.exe', 100, 'application/octet-stream')],
            ]))
            ->assertSessionHasErrors('files.0');
    }

    public function test_al_eliminar_el_contenido_se_borran_sus_archivos(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->post('/administracion/contenidos', $this->datos([
            'photos' => [UploadedFile::fake()->image('foto.jpg', 1200, 768)],
            'photo_alts' => ['Fachada'],
        ]));

        $content = Content::with('media')->sole();
        $path = $content->mainImage()->path;

        $this->actingAs($editor)->delete("/administracion/contenidos/{$content->id}");

        // El borrado en cascada de la base de datos no dispara los eventos de
        // Eloquent: sin el gancho, el archivo se quedaría huérfano en disco.
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('content_media', 0);
    }

    /* ------------------------------------------------------------------ */
    /* Biblioteca de imágenes                                              */
    /* ------------------------------------------------------------------ */

    public function test_se_pueden_crear_categorias_y_subir_imagenes_a_la_biblioteca(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post('/administracion/biblioteca/categorias', ['name' => 'Fachadas'])
            ->assertRedirect();

        $category = \App\Models\MediaCategory::sole();
        $this->assertSame('fachadas', $category->slug);

        $this->actingAs($editor)->post('/administracion/biblioteca/imagenes', [
            'image' => UploadedFile::fake()->image('fachada.jpg', 1200, 768),
            'alt' => 'Fachada principal del hospital',
            'media_category_id' => $category->id,
        ]);

        $image = \App\Models\LibraryImage::sole();
        $this->assertSame('Fachada principal del hospital', $image->alt);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_la_imagen_de_biblioteca_exige_descripcion(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/biblioteca/imagenes', [
                'image' => UploadedFile::fake()->image('foto.jpg', 1200, 768),
            ])
            ->assertSessionHasErrors('alt');

        $this->assertDatabaseCount('library_images', 0);
    }

    public function test_una_imagen_de_biblioteca_se_puede_usar_en_varios_contenidos(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->post('/administracion/biblioteca/imagenes', [
            'image' => UploadedFile::fake()->image('compartida.jpg', 1200, 768),
            'alt' => 'Imagen compartida',
        ]);

        $library = \App\Models\LibraryImage::sole();

        foreach (['Primero', 'Segundo'] as $title) {
            $this->actingAs($editor)->post('/administracion/contenidos', $this->datos([
                'title' => $title,
                'library_ids' => [$library->id],
            ]));
        }

        $this->assertDatabaseCount('content_media', 2);
        $this->assertSame('Imagen compartida', Content::with('media')->firstWhere('title', 'Primero')->mainImage()->alt);
    }

    public function test_desvincular_una_imagen_de_biblioteca_no_borra_su_archivo(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->post('/administracion/biblioteca/imagenes', [
            'image' => UploadedFile::fake()->image('compartida.jpg', 1200, 768),
            'alt' => 'Imagen compartida',
        ]);

        $library = \App\Models\LibraryImage::sole();

        $this->actingAs($editor)->post('/administracion/contenidos', $this->datos([
            'library_ids' => [$library->id],
        ]));

        $content = Content::sole();

        // Se guarda de nuevo sin la imagen: se desvincula.
        $this->actingAs($editor)->put("/administracion/contenidos/{$content->id}", $this->datos());

        $this->assertDatabaseCount('content_media', 0);
        // El archivo es de la biblioteca y puede estar en uso en otros
        // contenidos: desvincularlo de uno no debe borrarlo del disco.
        Storage::disk('public')->assertExists($library->path);
        $this->assertDatabaseCount('library_images', 1);
    }

    /* ------------------------------------------------------------------ */
    /* Participación ciudadana                                             */
    /* ------------------------------------------------------------------ */

    public function test_una_noticia_puede_abrirse_a_la_participacion(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'comment_wall' => CommentWall::PUBLICA,
        ]));

        $content = Content::sole();

        $this->assertSame(CommentWall::PUBLICA, $content->comment_wall);
        $this->assertTrue($content->invitesParticipation());

        // El botón «Participa» del portal, en la ficha y en el muro.
        $this->get("/contenidos/{$content->slug}")
            ->assertSee('Este contenido está abierto a la participación ciudadana', false);

        $this->get('/')->assertSee('Participa<span class="sr-only"> en «', false);
    }

    public function test_sin_participacion_no_sale_el_boton(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'comment_wall' => CommentWall::NINGUNA,
        ]));

        $content = Content::sole();

        $this->assertFalse($content->invitesParticipation());

        $this->get("/contenidos/{$content->slug}")
            ->assertDontSee('abierto a la participación ciudadana');

        $this->get('/')->assertDontSee('Participa<span class="sr-only"> en «', false);
    }

    /** Solo se admiten los tres estados del portal. */
    public function test_un_estado_de_participacion_desconocido_se_rechaza(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos(['comment_wall' => 9]))
            ->assertSessionHasErrors('comment_wall');
    }

    /**
     * Un campo vacío llega como null porque el middleware lo convierte, y el
     * valor por defecto de `input()` no interviene: la clave existe. Sin
     * cuidado, ese null acabaría en cero, que significa participación pública.
     */
    public function test_un_estado_vacio_no_abre_la_participacion(): void
    {
        $this->actingAs($this->editor())
            ->post('/administracion/contenidos', $this->datos(['comment_wall' => '']))
            ->assertSessionHasNoErrors();

        $content = Content::sole();

        $this->assertSame(CommentWall::NINGUNA, $content->comment_wall);
        $this->assertFalse($content->invitesParticipation());
    }

    /* ------------------------------------------------------------------ */
    /* Programación                                                        */
    /* ------------------------------------------------------------------ */

    public function test_un_contenido_programado_no_se_muestra_al_visitante(): void
    {
        $this->noticia(['title' => 'Anuncio programado', 'published_at' => now()->addDays(3)]);

        $this->get('/')->assertDontSee('Anuncio programado', false);

        $this->actingAs($this->editor())->get('/')
            ->assertSee('Anuncio programado', false)
            ->assertSee('Programado', false);
    }

    public function test_la_pagina_de_un_contenido_programado_no_existe_para_el_visitante(): void
    {
        $content = $this->noticia(['title' => 'Anuncio programado', 'published_at' => now()->addDays(3)]);

        $this->get("/contenidos/{$content->slug}")->assertNotFound();

        // Quien administra sí puede revisarlo antes de que salga.
        $this->actingAs($this->editor())->get("/contenidos/{$content->slug}")->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* Página del contenido                                                */
    /* ------------------------------------------------------------------ */

    public function test_la_pagina_del_contenido_muestra_cuerpo_medios_y_compartir(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'title' => 'Comunicado con material',
            'body' => '<p>Primer párrafo del comunicado.</p><h2>Un apartado</h2>',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'photos' => [UploadedFile::fake()->image('foto.jpg', 1200, 768)],
            'photo_alts' => ['Fachada del hospital'],
            'files' => [UploadedFile::fake()->create('anexo.pdf', 200, 'application/pdf')],
            'file_titles' => ['Anexo técnico'],
        ]));

        $content = Content::sole();

        $this->get("/contenidos/{$content->slug}")
            ->assertOk()
            ->assertSee('Comunicado con material', false)
            ->assertSee('Primer párrafo del comunicado.', false)
            ->assertSee('Un apartado', false)
            ->assertSee('Fachada del hospital', false)
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Anexo técnico', false)
            ->assertSee('Compartir', false)
            ->assertSee('¿Encontraste lo que buscabas?', false);
    }

    public function test_un_contenido_sin_enlace_externo_apunta_a_su_propia_pagina(): void
    {
        $content = $this->noticia();

        $this->assertSame(route('contents.show', $content->slug), $content->url());

        $conEnlace = $this->noticia(['title' => 'Con enlace', 'link' => 'https://www.huv.gov.co/x']);
        $this->assertSame('https://www.huv.gov.co/x', $conEnlace->url());
    }

    public function test_la_pagina_de_un_contenido_inactivo_no_existe_para_el_visitante(): void
    {
        $content = $this->noticia(['is_active' => false]);

        $this->get("/contenidos/{$content->slug}")->assertNotFound();
        $this->actingAs($this->editor())->get("/contenidos/{$content->slug}")->assertOk();
    }

    public function test_el_cuerpo_se_depura_antes_de_guardarse(): void
    {
        $this->actingAs($this->editor())->post('/administracion/contenidos', $this->datos([
            'body' => '<p>Texto legítimo</p><script>alert(1)</script>'
                .'<p onclick="robar()">Con atributo</p><a href="javascript:alert(2)">Enlace</a>',
        ]));

        $body = Content::sole()->body;

        // Un script almacenado se ejecutaría en cada visita a la portada.
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);

        // El formato legítimo sí se conserva.
        $this->assertStringContainsString('Texto legítimo', $body);
    }

    public function test_los_slugs_no_se_repiten(): void
    {
        $this->noticia(['title' => 'Mismo título']);
        $segundo = $this->noticia(['title' => 'Mismo título']);

        $this->assertSame('mismo-titulo', Content::orderBy('id')->first()->slug);
        $this->assertSame('mismo-titulo-2', $segundo->slug);
    }

    /* ------------------------------------------------------------------ */
    /* Acciones del menú                                                   */
    /* ------------------------------------------------------------------ */

    public function test_destacar_desmarca_la_anterior_de_su_categoria(): void
    {
        $antigua = $this->noticia(['title' => 'Antigua destacada', 'is_featured' => true]);
        $nueva = $this->noticia(['title' => 'Nueva destacada']);

        $this->actingAs($this->editor())
            ->post("/administracion/contenidos/{$nueva->id}/destacar")
            ->assertRedirect();

        // Dos destacadas dejarían al bloque eligiendo una al azar.
        $this->assertTrue($nueva->refresh()->is_featured);
        $this->assertFalse($antigua->refresh()->is_featured);
    }

    public function test_destacar_no_afecta_a_otras_categorias(): void
    {
        $comunicado = $this->noticia(['title' => 'Comunicado destacado', 'category' => 'Comunicados', 'is_featured' => true]);
        $noticia = $this->noticia(['title' => 'Noticia destacada']);

        $this->actingAs($this->editor())->post("/administracion/contenidos/{$noticia->id}/destacar");

        $this->assertTrue($comunicado->refresh()->is_featured);
    }

    public function test_inactivar_y_ocultar_son_acciones_distintas(): void
    {
        $content = $this->noticia();
        $editor = $this->editor();

        $this->actingAs($editor)->post("/administracion/contenidos/{$content->id}/activar");
        $this->assertFalse($content->refresh()->is_active);
        $this->assertFalse($content->is_hidden);

        $this->actingAs($editor)->post("/administracion/contenidos/{$content->id}/activar");
        $this->assertTrue($content->refresh()->is_active);

        $this->actingAs($editor)->post("/administracion/contenidos/{$content->id}/ocultar");
        $this->assertTrue($content->refresh()->is_hidden);
        $this->assertTrue($content->is_active);
    }

    public function test_se_puede_eliminar_un_contenido(): void
    {
        $content = $this->noticia();

        $this->actingAs($this->editor())
            ->delete("/administracion/contenidos/{$content->id}")
            ->assertRedirect(route('home').'#huv-contenidos');

        $this->assertDatabaseCount('contents', 0);
    }

    /* ------------------------------------------------------------------ */
    /* Portada                                                             */
    /* ------------------------------------------------------------------ */

    public function test_la_portada_publica_las_noticias_activas(): void
    {
        $this->noticia(['title' => 'Noticia visible']);

        $this->get('/')->assertSee('Noticia visible', false);
    }

    public function test_lo_inactivo_y_lo_oculto_no_se_muestra_al_visitante(): void
    {
        $this->noticia(['title' => 'Noticia inactiva', 'is_active' => false]);
        $this->noticia(['title' => 'Noticia oculta', 'is_hidden' => true]);

        $this->get('/')
            ->assertDontSee('Noticia inactiva', false)
            ->assertDontSee('Noticia oculta', false);
    }

    public function test_quien_administra_si_ve_lo_inactivo_para_poder_revertirlo(): void
    {
        $this->noticia(['title' => 'Noticia inactiva', 'is_active' => false]);
        $this->noticia(['title' => 'Noticia oculta', 'is_hidden' => true]);

        // Si desaparecieran también para quien administra, ocultar un contenido
        // lo volvería inalcanzable: no habría desde dónde volver a mostrarlo.
        $this->actingAs($this->editor())->get('/')
            ->assertSee('Noticia inactiva', false)
            ->assertSee('Noticia oculta', false)
            ->assertSee('Inactivo', false)
            ->assertSee('Oculto en la portada', false);
    }

    public function test_el_menu_de_acciones_solo_existe_con_sesion_iniciada(): void
    {
        $content = $this->noticia();

        $this->get('/')
            ->assertDontSee(route('admin.contents.feature', $content), false)
            ->assertDontSee('Acciones del contenido', false);

        $this->actingAs($this->editor())->get('/')
            ->assertSee(route('admin.contents.feature', $content), false)
            ->assertSee(route('admin.contents.active', $content), false)
            ->assertSee(route('admin.contents.hidden', $content), false)
            ->assertSee('editar='.$content->id, false)
            ->assertSee('Acciones del contenido', false);
    }

    public function test_la_noticia_destacada_ocupa_el_lugar_principal(): void
    {
        $this->noticia(['title' => 'Noticia reciente', 'published_at' => now()]);
        $this->noticia(['title' => 'Noticia antigua destacada', 'published_at' => now()->subMonth(), 'is_featured' => true]);

        $html = $this->get('/')->getContent();

        // Aunque sea más antigua, la destacada va primero.
        $this->assertLessThan(
            strpos($html, 'Noticia reciente'),
            strpos($html, 'Noticia antigua destacada')
        );
    }

    public function test_sin_destacada_explicita_la_mas_reciente_ocupa_ese_lugar(): void
    {
        $this->noticia(['title' => 'La más antigua', 'published_at' => now()->subMonth()]);
        $this->noticia(['title' => 'La más reciente', 'published_at' => now()]);

        $html = $this->get('/')->getContent();

        $this->assertLessThan(
            strpos($html, 'La más antigua'),
            strpos($html, 'La más reciente')
        );
    }

    public function test_el_muro_respeta_la_casilla_de_mostrar_en_el_listado(): void
    {
        $this->noticia(['title' => 'Fuera del muro', 'show_in_feed' => false]);

        $html = $this->get('/')->getContent();

        // Sigue en el bloque de Noticias, pero no en el listado general.
        $feed = substr($html, strpos($html, 'id="huv-contenidos"'));
        $this->assertStringNotContainsString('Fuera del muro', $feed);
        $this->assertStringContainsString('Fuera del muro', $html);
    }
}
