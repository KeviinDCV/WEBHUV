<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\User;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El resolutor recuerda los temas migrados durante la petición, y en las
        // pruebas todas comparten proceso.
        LegacyLink::forget();
    }

    private function editor(): User
    {
        return User::create([
            'name' => 'Editora del portal',
            'email' => 'editora@huv.gov.co',
            'password' => Hash::make('Contrasena-Segura-2026#'),
        ]);
    }

    private function tema(array $overrides = []): Topic
    {
        return Topic::create(array_merge([
            'name' => 'Presupuesto',
            'slug' => 'presupuesto',
            'imported_at' => now(),
        ], $overrides));
    }

    private function documento(Topic $topic, array $overrides = []): Document
    {
        return $topic->documents()->create(array_merge([
            'title' => 'Ejecución presupuestal de enero',
            'description' => '<p>Ejecución presupuestal del mes de enero.</p>',
            'issued_at' => now()->subMonths(2),
            'published_at' => now()->subDay(),
            'file_path' => 'documentos/1/enero.pdf',
            'file_name' => 'enero.pdf',
            'file_size' => 1048576,
            'file_extension' => 'pdf',
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* Listado público                                                     */
    /* ------------------------------------------------------------------ */

    public function test_el_tema_muestra_sus_documentos(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Presupuesto')
            ->assertSee($document->title)
            ->assertSee('Busca en Presupuesto')
            ->assertSee('Cargar más contenidos', false);
    }

    public function test_las_categorias_se_listan_con_su_recuento(): void
    {
        $topic = $this->tema();

        $category = TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => 'Ejecución Presupuestal 2026',
            'slug' => 'ejecucion-presupuestal-2026',
        ]);

        $this->documento($topic, ['topic_category_id' => $category->id]);
        $this->documento($topic, ['title' => 'Otro', 'topic_category_id' => $category->id]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Ejecución Presupuestal 2026')
            ->assertViewHas(
                'categories',
                fn ($categories) => $categories->firstWhere('name', 'Ejecución Presupuestal 2026')['count'] === 2
            );
    }

    public function test_una_categoria_sin_documentos_visibles_no_aparece(): void
    {
        $topic = $this->tema();

        TopicCategory::create([
            'topic_id' => $topic->id,
            'name' => 'Categoría vacía',
            'slug' => 'categoria-vacia',
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertDontSee('Categoría vacía');
    }

    public function test_el_visitante_no_ve_lo_inactivo_lo_oculto_ni_lo_programado(): void
    {
        $topic = $this->tema();

        $this->documento($topic, ['title' => 'Documento inactivo', 'is_active' => false]);
        $this->documento($topic, ['title' => 'Documento oculto', 'is_hidden' => true]);
        $this->documento($topic, ['title' => 'Documento programado', 'published_at' => now()->addWeek()]);
        $this->documento($topic, ['title' => 'Documento publicado']);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Documento publicado')
            ->assertDontSee('Documento inactivo')
            ->assertDontSee('Documento oculto')
            ->assertDontSee('Documento programado');
    }

    public function test_quien_administra_si_los_ve_marcados(): void
    {
        $topic = $this->tema();
        $this->documento($topic, ['title' => 'Documento oculto', 'is_hidden' => true]);

        $this->actingAs($this->editor())
            ->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Documento oculto')
            ->assertSee('Oculto en el listado');
    }

    /* ------------------------------------------------------------------ */
    /* Ficha del documento                                                 */
    /* ------------------------------------------------------------------ */

    public function test_la_ficha_muestra_la_fecha_de_expedicion_y_el_archivo(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('documents.show', [$topic, $document]))
            ->assertOk()
            ->assertSee($document->title)
            ->assertSee('Fecha de expedición')
            ->assertSee('Archivos para descargar')
            ->assertSee('1 Mb')
            ->assertSee('enero.pdf');
    }

    public function test_la_ficha_de_un_documento_oculto_no_existe_para_el_visitante(): void
    {
        $topic = $this->tema();
        $document = $this->documento($topic, ['is_hidden' => true]);

        $this->get(route('documents.show', [$topic, $document]))->assertNotFound();

        $this->actingAs($this->editor())
            ->get(route('documents.show', [$topic, $document]))
            ->assertOk()
            ->assertSee('noindex, nofollow', false);
    }

    public function test_un_documento_de_otro_tema_no_se_sirve_bajo_este(): void
    {
        $topic = $this->tema();
        $other = $this->tema(['name' => 'Planes', 'slug' => 'planes']);

        $document = $this->documento($other);

        $this->get(route('documents.show', [$topic->slug, $document->slug]))->assertNotFound();
    }

    /**
     * La dirección del archivo se arma con asset(): Storage::url() la construye
     * a partir de APP_URL y devolvería un enlace roto en cuanto el aplicativo se
     * sirva en otro puerto o dominio.
     */
    public function test_el_enlace_del_archivo_no_depende_de_app_url(): void
    {
        config(['app.url' => 'https://otro-dominio.example']);

        $topic = $this->tema();
        $document = $this->documento($topic);

        $this->get(route('documents.show', [$topic, $document]))
            ->assertOk()
            ->assertSee(url('storage/documentos/1/enero.pdf'))
            ->assertDontSee('otro-dominio.example');
    }

    /* ------------------------------------------------------------------ */
    /* Enlaces del menú durante la migración                               */
    /* ------------------------------------------------------------------ */

    public function test_un_tema_migrado_se_enlaza_dentro_del_aplicativo(): void
    {
        $this->tema();

        $resolved = LegacyLink::resolve(['label' => 'Presupuesto', 'path' => '/tema/presupuesto']);

        $this->assertSame(route('topics.show', 'presupuesto'), $resolved['href']);
        $this->assertFalse($resolved['external']);
    }

    public function test_un_tema_sin_migrar_sigue_apuntando_al_portal_actual(): void
    {
        $resolved = LegacyLink::resolve(['label' => 'Planes', 'path' => '/tema/planes']);

        $this->assertSame(
            rtrim((string) config('huv.legacy_base'), '/').'/tema/planes',
            $resolved['href']
        );
        $this->assertTrue($resolved['external']);
    }

    public function test_el_menu_lleva_al_tema_migrado(): void
    {
        $this->tema();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('topics.show', 'presupuesto'), false);
    }
}
