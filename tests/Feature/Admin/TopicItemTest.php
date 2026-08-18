<?php

namespace Tests\Feature\Admin;

use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Models\User;
use App\Support\CommentWall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopicItemTest extends TestCase
{
    use RefreshDatabase;

    /** firstOrCreate: hay pruebas que la piden varias veces en la misma. */
    private function editor(): User
    {
        return User::firstOrCreate(
            ['email' => 'editora@huv.gov.co'],
            ['name' => 'Editora del portal', 'password' => Hash::make('Contrasena-Segura-2026#')]
        );
    }

    private function tema(): Topic
    {
        return Topic::create(['name' => 'Presupuesto', 'slug' => 'presupuesto', 'imported_at' => now()]);
    }

    private function documento(Topic $topic, array $overrides = []): TopicItem
    {
        return $topic->items()->create(array_merge([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Ejecución presupuestal de enero',
            'published_at' => now()->subDay(),
            'file_path' => 'documentos/1/enero.pdf',
            'file_name' => 'enero.pdf',
            'file_extension' => 'pdf',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Ejecución presupuestal de febrero',
            'issued_at' => '2026-02-28',
            'body' => '<p>Ejecución del mes de febrero.</p>',
            'file' => UploadedFile::fake()->create('febrero.pdf', 120, 'application/pdf'),
            'show_in_feed' => '1',
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */

    public function test_el_editor_se_abre_dentro_del_listado_del_tema(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->actingAs($this->editor())
            ->get(route('topics.show', $topic).'?editar='.$document->id)
            ->assertOk()
            ->assertSee('huv-editor-tema', false)
            ->assertSee('Nuevo contenido')
            // El formulario llega relleno con el documento que se va a editar.
            ->assertSee($document->title)
            ->assertSee(route('admin.topics.items.update', [$topic, $document]), false);
    }

    public function test_el_visitante_no_ve_el_editor(): void
    {
        $topic = $this->tema();
        $this->documento($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertDontSee('Nuevo contenido')
            ->assertDontSee('huv-editor-tema', false);
    }

    public function test_se_publica_un_documento_con_su_archivo(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos())
            ->assertRedirect(route('topics.show', $topic).'#huv-listado');

        $document = TopicItem::sole();

        $this->assertSame('Ejecución presupuestal de febrero', $document->title);
        $this->assertSame('2026-02-28', $document->issued_at->format('Y-m-d'));
        $this->assertSame('pdf', $document->file_extension);
        $this->assertFalse($document->is_hidden);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_un_documento_sin_archivo_ni_enlace_no_se_publica(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos(['file' => null]))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, TopicItem::count());
    }

    public function test_un_documento_puede_apuntar_a_un_enlace_externo(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos([
                'file' => null,
                'link' => 'https://www.datos.gov.co/ejemplo.pdf',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('https://www.datos.gov.co/ejemplo.pdf', TopicItem::sole()->fileUrl());
    }

    public function test_el_html_de_la_descripcion_se_depura(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos([
                'body' => '<p>Presupuesto</p><script>alert(1)</script>',
            ]));

        $this->assertStringNotContainsString('<script', TopicItem::sole()->body);
    }

    public function test_se_crea_una_categoria_desde_el_propio_formulario(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos([
                'new_category' => 'Ejecución Presupuestal 2026',
            ]));

        $category = TopicCategory::sole();

        $this->assertSame('Ejecución Presupuestal 2026', $category->name);
        $this->assertSame($topic->id, $category->topic_id);
        $this->assertSame([$category->id], TopicItem::sole()->categories->pluck('id')->all());
    }

    public function test_no_se_admite_una_categoria_de_otro_tema(): void
    {
        $topic = $this->tema();
        $other = Topic::create(['name' => 'Planes', 'slug' => 'planes']);

        $category = TopicCategory::create([
            'topic_id' => $other->id,
            'name' => 'Categoría ajena',
            'slug' => 'categoria-ajena',
        ]);

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos([
                'topic_category_ids' => [$category->id],
            ]))
            ->assertSessionHasErrors('topic_category_ids.0');
    }

    public function test_programar_deja_el_documento_fuera_del_listado_publico(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datos([
                'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]));

        $document = TopicItem::sole();

        $this->assertTrue($document->isScheduled());

        // Se cierra la sesión: para quien administra sí es accesible, que es
        // justo el sentido de programar algo.
        auth()->logout();

        $this->get(route('topics.items.show', [$topic, $document]))->assertNotFound();
    }

    public function test_se_actualiza_un_documento(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->actingAs($this->editor())
            ->put(route('admin.topics.items.update', [$topic, $document]), $this->datos([
                'file' => null,
                'title' => 'Título corregido',
            ]))
            ->assertRedirect(route('topics.show', $topic).'#huv-listado');

        $this->assertSame('Título corregido', $document->fresh()->title);
    }

    public function test_al_eliminar_se_borra_tambien_el_archivo(): void
    {
        Storage::fake('public');

        $topic = $this->tema();
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.topics.items.store', $topic), $this->datos());

        $document = TopicItem::sole();
        $path = $document->file_path;

        $this->actingAs($editor)
            ->delete(route('admin.topics.items.destroy', [$topic, $document]))
            ->assertRedirect(route('topics.show', $topic).'#huv-listado');

        $this->assertSame(0, TopicItem::count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_las_acciones_rapidas_del_lapiz(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.topics.items.feature', [$topic, $document]))
            ->assertRedirect();
        $this->assertTrue($document->fresh()->is_featured);

        $this->actingAs($editor)->post(route('admin.topics.items.active', [$topic, $document]));
        $this->assertFalse($document->fresh()->is_active);

        $this->actingAs($editor)->post(route('admin.topics.items.hidden', [$topic, $document]));
        $this->assertTrue($document->fresh()->is_hidden);
    }

    public function test_un_documento_no_se_administra_desde_otro_tema(): void
    {
        $topic = $this->tema();
        $other = Topic::create(['name' => 'Planes', 'slug' => 'planes']);
        $document = $this->documento($topic);

        $this->actingAs($this->editor())
            ->delete(route('admin.topics.items.destroy', [$other, $document]))
            ->assertNotFound();

        $this->assertSame(1, TopicItem::count());
    }

    public function test_sin_sesion_no_se_puede_administrar(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->post(route('admin.topics.items.store', $topic), $this->datos())->assertRedirect(route('login'));
        $this->delete(route('admin.topics.items.destroy', [$topic, $document]))->assertRedirect(route('login'));

        $this->assertSame(1, TopicItem::count());
    }

    /* ------------------------------------------------------------------ */
    /* Artículos                                                           */
    /* ------------------------------------------------------------------ */

    private function temaDeArticulos(): Topic
    {
        return Topic::create([
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'content_template' => '<p>Resumen del programa:</p>',
            'imported_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosArticulo(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Programa Fénix',
            'body' => '<p>El Programa Fénix hace parte del CIAVI.</p>',
            'show_in_feed' => '1',
            'comment_wall' => 2,
        ], $overrides);
    }

    public function test_se_publica_un_articulo_con_foto_y_varios_archivos(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'photos' => [UploadedFile::fake()->image('equipo.jpg')],
                'photo_alts' => ['Equipo del programa'],
                'files' => [
                    UploadedFile::fake()->create('plan.pdf', 40, 'application/pdf'),
                    UploadedFile::fake()->create('cierre.pdf', 40, 'application/pdf'),
                ],
                'file_titles' => ['Plan', 'Cierre'],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('topics.show', $topic).'#huv-listado');

        $item = TopicItem::sole();

        $this->assertSame(TopicItem::KIND_ARTICLE, $item->kind);
        $this->assertCount(1, $item->images());
        $this->assertCount(2, $item->files());
        $this->assertSame('Equipo del programa', $item->mainImage()->alt);
        $this->assertDatabaseCount('content_media', 3);
    }

    /** Sin descripción, una foto es inservible con lector de pantalla. */
    public function test_cada_foto_necesita_su_descripcion(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'photos' => [UploadedFile::fake()->image('equipo.jpg')],
            ]))
            ->assertSessionHasErrors('photo_alts.0');

        $this->assertSame(0, TopicItem::count());
    }

    public function test_se_sincronizan_varias_categorias_y_se_crea_la_nueva(): void
    {
        $topic = $this->temaDeArticulos();

        $ptee = TopicCategory::create(['topic_id' => $topic->id, 'name' => 'Programa PTEE', 'slug' => 'programa-ptee']);

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'topic_category_ids' => [$ptee->id],
                'new_category' => '2025',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['2025', 'Programa PTEE'], TopicItem::sole()->categories->pluck('name')->all());
    }

    public function test_un_muro_de_participacion_desconocido_se_rechaza(): void
    {
        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo(['comment_wall' => 9]))
            ->assertSessionHasErrors('comment_wall');
    }

    public function test_un_tipo_que_el_tema_no_admite_se_rechaza(): void
    {
        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'kind' => TopicItem::KIND_DOCUMENT,
            ]))
            ->assertSessionHasErrors('kind');
    }

    /**
     * Un `kind` vacío o de un tipo inesperado no puede tumbar la petición: el
     * campo decide qué reglas se aplican, y si revienta lo hace antes de
     * validar nada, sin mensaje y perdiendo lo escrito.
     */
    public function test_un_tipo_vacio_o_raro_no_tumba_la_peticion(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo(['kind' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(TopicItem::KIND_ARTICLE, TopicItem::sole()->kind);

        $this->actingAs($editor)
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'title' => 'Otro',
                'kind' => ['articulo'],
            ]))
            ->assertSessionHasErrors('kind');
    }

    /** Un muro vacío tampoco puede abrir la participación por su cuenta. */
    public function test_un_muro_vacio_deja_el_contenido_sin_participacion(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo(['comment_wall' => '']))
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();

        $this->assertSame(CommentWall::NINGUNA, $item->comment_wall);
        $this->assertFalse($item->invitesParticipation());
    }

    /** La plantilla del tema precarga el editor, y solo al crear. */
    public function test_la_plantilla_del_tema_precarga_el_editor(): void
    {
        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Resumen del programa:');

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Ya escrito',
            'body' => '<p>Contenido propio.</p>',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->editor())
            ->get(route('topics.show', $topic).'?editar='.$item->id)
            ->assertOk()
            ->assertSee('Contenido propio.')
            ->assertDontSee('Resumen del programa:');
    }

    public function test_el_chip_dice_noticia_en_un_tema_de_articulos(): void
    {
        $this->actingAs($this->editor())
            ->get(route('topics.show', $this->temaDeArticulos()))
            ->assertOk()
            ->assertSee('Noticia');

        $this->actingAs($this->editor())
            ->get(route('topics.show', $this->tema()))
            ->assertOk()
            ->assertSee('Documento');
    }

    /**
     * El borrado en cascada de la base de datos no dispara los eventos de
     * Eloquent: sin recorrer los medios uno a uno, los archivos se quedarían en
     * el disco para siempre.
     */
    public function test_al_eliminar_un_articulo_desaparecen_sus_archivos(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'photos' => [UploadedFile::fake()->image('equipo.jpg')],
                'photo_alts' => ['Equipo del programa'],
            ]));

        $item = TopicItem::sole();
        $path = $item->mainImage()->path;

        $this->actingAs($this->editor())
            ->delete(route('admin.topics.items.destroy', [$topic, $item]));

        $this->assertDatabaseCount('content_media', 0);
        Storage::disk('public')->assertMissing($path);
    }

    /** Y lo mismo al borrar el tema entero. */
    public function test_al_eliminar_el_tema_desaparecen_los_archivos_de_sus_elementos(): void
    {
        Storage::fake('public');

        $topic = $this->temaDeArticulos();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), $this->datosArticulo([
                'photos' => [UploadedFile::fake()->image('equipo.jpg')],
                'photo_alts' => ['Equipo del programa'],
            ]));

        $path = TopicItem::sole()->mainImage()->path;

        $topic->delete();

        $this->assertSame(0, TopicItem::count());
        $this->assertDatabaseCount('content_media', 0);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Un medio pertenece a un contenido o a un elemento de tema, nunca a los dos
     * ni a ninguno: si no, borrar un dueño dejaría el archivo colgando del otro.
     */
    public function test_un_medio_necesita_exactamente_un_dueno(): void
    {
        $topic = $this->temaDeArticulos();
        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Programa Fénix',
            'published_at' => now()->subDay(),
        ]);

        $content = Content::create([
            'title' => 'Noticia',
            'category' => Content::NEWS_CATEGORY,
            'published_at' => now()->subDay(),
        ]);

        $this->expectException(\LogicException::class);

        ContentMedia::create([
            'content_id' => $content->id,
            'topic_item_id' => $item->id,
            'type' => ContentMedia::TYPE_IMAGE,
            'path' => 'temas/1/foto.jpg',
        ]);
    }

    public function test_un_medio_sin_dueno_tampoco_se_admite(): void
    {
        $this->expectException(\LogicException::class);

        ContentMedia::create([
            'type' => ContentMedia::TYPE_IMAGE,
            'path' => 'temas/1/foto.jpg',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Eventos                                                             */
    /* ------------------------------------------------------------------ */

    private function agenda(): Topic
    {
        return Topic::create([
            'name' => 'Calendario de actividades',
            'slug' => 'calendario-de-actividades',
            'legacy_content_types' => ['Event'],
            'imported_at' => now(),
        ]);
    }

    /**
     * Un evento se crea con lugar, organizador y hora.
     *
     * La importación ya traía los tres —«EventHost» y «EventLocation» llegan
     * entre los atributos del contenido, y «startingDate» como fecha de inicio—
     * pero no había dónde escribirlos: un evento creado aquí salía sin lugar ni
     * organizador, y uno importado los perdía en cuanto alguien lo editaba.
     */
    public function test_un_evento_guarda_lugar_organizador_y_hora(): void
    {
        $topic = $this->agenda();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'kind' => TopicItem::KIND_EVENT,
                'title' => 'Jornada de donación de sangre',
                'body' => '<p>Ven a donar.</p>',
                'event_host' => 'Banco de Sangre',
                'event_location' => 'Auditorio principal',
                'event_date' => '2026-09-15',
                'event_time' => '14:30',
                'published_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $item = TopicItem::sole();

        $this->assertSame('Banco de Sangre', $item->event_host);
        $this->assertSame('Auditorio principal', $item->event_location);
        $this->assertSame('2026-09-15 14:30', $item->opens_at->format('Y-m-d H:i'));
    }

    /** Los dos datos se pintan en una línea: setenta caracteres y ni uno más. */
    public function test_el_lugar_y_el_organizador_no_pasan_de_setenta(): void
    {
        $topic = $this->agenda();

        foreach (['event_host', 'event_location'] as $campo) {
            $this->actingAs($this->editor())
                ->post(route('admin.topics.items.store', $topic), [
                    'kind' => TopicItem::KIND_EVENT,
                    'title' => 'Evento',
                    $campo => str_repeat('a', 71),
                    'published_at' => now()->format('Y-m-d\TH:i'),
                ])
                ->assertSessionHasErrors($campo);
        }

        $this->assertDatabaseCount('topic_items', 0);
    }

    /** El editor enseña los campos del evento, y solo para un evento. */
    public function test_el_editor_ofrece_los_campos_del_evento(): void
    {
        $html = $this->actingAs($this->editor())
            ->get(route('topics.show', $this->agenda()))
            ->assertOk()
            ->getContent();

        foreach (['event_host', 'event_location', 'event_date', 'event_time'] as $campo) {
            $this->assertStringContainsString('name="'.$campo.'"', $html, "Falta «{$campo}» en el editor.");
        }

        // Y solo se habilitan cuando el tipo es evento.
        $this->assertStringContainsString('x-show="isEvent"', $html);
    }
}
