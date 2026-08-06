<?php

namespace Tests\Feature\Admin;

use App\Models\Document;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    private function tema(): Topic
    {
        return Topic::create(['name' => 'Presupuesto', 'slug' => 'presupuesto', 'imported_at' => now()]);
    }

    private function documento(Topic $topic, array $overrides = []): Document
    {
        return $topic->documents()->create(array_merge([
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
            'description' => '<p>Ejecución del mes de febrero.</p>',
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
            ->assertSee('huv-editor-documento', false)
            ->assertSee('Nuevo contenido')
            // El formulario llega relleno con el documento que se va a editar.
            ->assertSee($document->title)
            ->assertSee(route('admin.documents.update', [$topic, $document]), false);
    }

    public function test_el_visitante_no_ve_el_editor(): void
    {
        $topic = $this->tema();
        $this->documento($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertDontSee('Nuevo contenido')
            ->assertDontSee('huv-editor-documento', false);
    }

    public function test_se_publica_un_documento_con_su_archivo(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.documents.store', $topic), $this->datos())
            ->assertRedirect(route('topics.show', $topic).'#huv-documentos');

        $document = Document::sole();

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
            ->post(route('admin.documents.store', $topic), $this->datos(['file' => null]))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Document::count());
    }

    public function test_un_documento_puede_apuntar_a_un_enlace_externo(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.documents.store', $topic), $this->datos([
                'file' => null,
                'link' => 'https://www.datos.gov.co/ejemplo.pdf',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('https://www.datos.gov.co/ejemplo.pdf', Document::sole()->fileUrl());
    }

    public function test_el_html_de_la_descripcion_se_depura(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.documents.store', $topic), $this->datos([
                'description' => '<p>Presupuesto</p><script>alert(1)</script>',
            ]));

        $this->assertStringNotContainsString('<script', Document::sole()->description);
    }

    public function test_se_crea_una_categoria_desde_el_propio_formulario(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.documents.store', $topic), $this->datos([
                'new_category' => 'Ejecución Presupuestal 2026',
            ]));

        $category = TopicCategory::sole();

        $this->assertSame('Ejecución Presupuestal 2026', $category->name);
        $this->assertSame($topic->id, $category->topic_id);
        $this->assertSame($category->id, Document::sole()->topic_category_id);
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
            ->post(route('admin.documents.store', $topic), $this->datos([
                'topic_category_id' => $category->id,
            ]))
            ->assertSessionHasErrors('topic_category_id');
    }

    public function test_programar_deja_el_documento_fuera_del_listado_publico(): void
    {
        Storage::fake('public');

        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.documents.store', $topic), $this->datos([
                'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]));

        $document = Document::sole();

        $this->assertTrue($document->isScheduled());

        // Se cierra la sesión: para quien administra sí es accesible, que es
        // justo el sentido de programar algo.
        auth()->logout();

        $this->get(route('documents.show', [$topic, $document]))->assertNotFound();
    }

    public function test_se_actualiza_un_documento(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->actingAs($this->editor())
            ->put(route('admin.documents.update', [$topic, $document]), $this->datos([
                'file' => null,
                'title' => 'Título corregido',
            ]))
            ->assertRedirect(route('topics.show', $topic).'#huv-documentos');

        $this->assertSame('Título corregido', $document->fresh()->title);
    }

    public function test_al_eliminar_se_borra_tambien_el_archivo(): void
    {
        Storage::fake('public');

        $topic = $this->tema();
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.documents.store', $topic), $this->datos());

        $document = Document::sole();
        $path = $document->file_path;

        $this->actingAs($editor)
            ->delete(route('admin.documents.destroy', [$topic, $document]))
            ->assertRedirect(route('topics.show', $topic).'#huv-documentos');

        $this->assertSame(0, Document::count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_las_acciones_rapidas_del_lapiz(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('admin.documents.feature', [$topic, $document]))
            ->assertRedirect();
        $this->assertTrue($document->fresh()->is_featured);

        $this->actingAs($editor)->post(route('admin.documents.active', [$topic, $document]));
        $this->assertFalse($document->fresh()->is_active);

        $this->actingAs($editor)->post(route('admin.documents.hidden', [$topic, $document]));
        $this->assertTrue($document->fresh()->is_hidden);
    }

    public function test_un_documento_no_se_administra_desde_otro_tema(): void
    {
        $topic = $this->tema();
        $other = Topic::create(['name' => 'Planes', 'slug' => 'planes']);
        $document = $this->documento($topic);

        $this->actingAs($this->editor())
            ->delete(route('admin.documents.destroy', [$other, $document]))
            ->assertNotFound();

        $this->assertSame(1, Document::count());
    }

    public function test_sin_sesion_no_se_puede_administrar(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->post(route('admin.documents.store', $topic), $this->datos())->assertRedirect(route('login'));
        $this->delete(route('admin.documents.destroy', [$topic, $document]))->assertRedirect(route('login'));

        $this->assertSame(1, Document::count());
    }
}
