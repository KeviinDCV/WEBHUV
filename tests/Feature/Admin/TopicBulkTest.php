<?php

namespace Tests\Feature\Admin;

use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Carga masiva de enlaces desde una hoja de cálculo.
 */
class TopicBulkTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::firstOrCreate(
            ['email' => 'editora@huv.gov.co'],
            ['name' => 'Editora del portal', 'password' => Hash::make('Contrasena-Segura-2026#')]
        );
    }

    private function tema(): Topic
    {
        return Topic::create([
            'name' => 'Contrataciones',
            'slug' => 'contrataciones',
            'legacy_content_types' => ['Link'],
            'imported_at' => now(),
        ]);
    }

    /**
     * Escribe una hoja de cálculo de verdad: leer xlsx con una falsificación no
     * probaría nada, porque el formato es un zip con XML dentro.
     *
     * @param  list<list<string>>  $rows
     */
    private function hoja(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'huv').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return new UploadedFile($path, 'contrataciones.xlsx', null, null, true);
    }

    /* ------------------------------------------------------------------ */

    public function test_la_pantalla_explica_el_formato_esperado(): void
    {
        $this->actingAs($this->editor())
            ->get(route('admin.topics.bulk.create', $this->tema()))
            ->assertOk()
            ->assertSee('Carga masiva')
            ->assertSee('formato Excel (xlsx)')
            ->assertSee('Debe ir sin encabezado.');
    }

    public function test_sin_sesion_no_se_puede_cargar(): void
    {
        $topic = $this->tema();

        $this->get(route('admin.topics.bulk.create', $topic))->assertRedirect(route('login'));
        $this->post(route('admin.topics.bulk.store', $topic), [])->assertRedirect(route('login'));
    }

    public function test_se_cargan_las_filas_de_la_hoja(): void
    {
        $topic = $this->tema();

        $archivo = $this->hoja([
            ['C26-106', 'QUINBERLAB SAS', 'https://community.secop.gov.co/uno'],
            ['C26-104', 'BECTON DICKINSON DE COLOMBIA LTDA', 'https://community.secop.gov.co/dos'],
            ['C26-102', 'MACROSEARCH SAS', 'https://community.secop.gov.co/tres'],
        ]);

        $this->actingAs($this->editor())
            ->post(route('admin.topics.bulk.store', $topic), ['archivo' => $archivo])
            ->assertRedirect(route('topics.show', $topic).'#huv-listado')
            ->assertSessionHas('status', '3 contenidos cargados.');

        $this->assertSame(3, TopicItem::count());

        $item = TopicItem::where('title', 'C26-106')->sole();

        $this->assertSame(TopicItem::KIND_LINK, $item->kind);
        $this->assertSame('https://community.secop.gov.co/uno', $item->source_url);
        $this->assertStringContainsString('QUINBERLAB SAS', $item->body);
    }

    /** Las filas en blanco del final de una hoja no son un error. */
    public function test_las_filas_vacias_se_ignoran_sin_ruido(): void
    {
        $topic = $this->tema();

        $archivo = $this->hoja([
            ['C26-106', 'QUINBERLAB SAS', 'https://community.secop.gov.co/uno'],
            ['', '', ''],
            ['', '', ''],
        ]);

        $this->actingAs($this->editor())
            ->post(route('admin.topics.bulk.store', $topic), ['archivo' => $archivo])
            ->assertSessionHas('status', '1 contenidos cargados.');

        $this->assertSame(1, TopicItem::count());
    }

    /** Una fila mal formada no puede tumbar las demás. */
    public function test_las_filas_invalidas_se_reportan_y_el_resto_entra(): void
    {
        $topic = $this->tema();

        $archivo = $this->hoja([
            ['C26-106', 'QUINBERLAB SAS', 'https://community.secop.gov.co/uno'],
            ['C26-104', 'Sin dirección', ''],
            ['C26-102', 'Dirección inventada', 'esto-no-es-una-url'],
        ]);

        $respuesta = $this->actingAs($this->editor())
            ->post(route('admin.topics.bulk.store', $topic), ['archivo' => $archivo]);

        $respuesta->assertSessionHas('status', '1 contenidos cargados. 2 filas quedaron fuera.');

        $problemas = session('bulkIssues');

        $this->assertCount(2, $problemas);
        $this->assertStringContainsString('Fila 2', $problemas[0]);
        $this->assertStringContainsString('Fila 3', $problemas[1]);

        $this->assertSame(1, TopicItem::count());
    }

    public function test_solo_se_admiten_hojas_de_calculo(): void
    {
        $topic = $this->tema();

        $this->actingAs($this->editor())
            ->post(route('admin.topics.bulk.store', $topic), [
                'archivo' => UploadedFile::fake()->create('listado.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->assertSame(0, TopicItem::count());
    }
}
