<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloque de mapa de un tema.
 *
 * En el portal el mapa es un «bloque» colgado de un tema —hay uno solo,
 * «Ubicación fisica» en Directorio institucional—, y aparece en el listado del
 * tema, no en la ficha de cada contenido. Aquí vive en configuración, así que
 * lo que se comprueba es que se pinte donde toca y solo ahí.
 */
class TopicMapTest extends TestCase
{
    use RefreshDatabase;

    private function tema(string $slug, string $name): Topic
    {
        return Topic::create([
            'name' => $name,
            'slug' => $slug,
            'legacy_content_types' => ['Article'],
            'legacy_template_type' => Topic::TEMPLATE_SORTABLE,
            'imported_at' => now(),
        ]);
    }

    private function articulo(Topic $topic): TopicItem
    {
        return $topic->items()->create([
            'title' => 'Información de contacto',
            'slug' => 'informacion-de-contacto',
            'kind' => TopicItem::KIND_ARTICLE,
            'body' => '<p>Cl. 5 # 36-08</p>',
            'published_at' => now()->subYears(3),
            'is_active' => true,
        ]);
    }

    public function test_el_tema_configurado_conoce_su_mapa(): void
    {
        $conMapa = $this->tema('directorio-institucional', 'Directorio institucional');
        $sinMapa = $this->tema('directorio-de-funcionarios', 'Directorio de funcionarios');

        $mapa = $conMapa->map();

        $this->assertNotNull($mapa);
        $this->assertSame(16, $mapa['zoom']);
        // Las del portal, no las del centro de la ciudad: el mapa señala el
        // hospital.
        $this->assertEqualsWithDelta(3.430215, $mapa['latitude'], 0.0001);
        $this->assertEqualsWithDelta(-76.545449, $mapa['longitude'], 0.0001);

        $this->assertNull($sinMapa->map());
    }

    public function test_el_listado_del_tema_pinta_el_mapa(): void
    {
        $topic = $this->tema('directorio-institucional', 'Directorio institucional');
        $this->articulo($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('huvMap(', false)
            ->assertSee('Ubicación física')
            // `isolate`: Leaflet reparte z-index de hasta 1000 entre sus capas.
            // Sin contexto de apilamiento propio compiten en el raíz contra la
            // cabecera fija y el cajón de navegación, y los botones de zoom
            // acaban pintados por encima de ellos.
            ->assertSee('relative isolate mt-10', false);
    }

    public function test_un_tema_sin_mapa_no_carga_el_componente(): void
    {
        $topic = $this->tema('directorio-de-funcionarios', 'Directorio de funcionarios');
        $this->articulo($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertDontSee('huvMap(', false)
            ->assertDontSee('huv-mapa-titulo', false);
    }

    public function test_la_ficha_de_un_contenido_no_pinta_el_mapa(): void
    {
        $topic = $this->tema('directorio-institucional', 'Directorio institucional');
        $item = $this->articulo($topic);

        // El portal lo pone en el listado del tema y en ningún sitio más:
        // repetirlo en cada ficha sería medio megabyte de teselas por contenido.
        $this->get(route('topics.items.show', [$topic, $item]))
            ->assertOk()
            ->assertDontSee('huvMap(', false);
    }

    public function test_sin_javascript_queda_la_direccion_y_el_enlace_al_mapa(): void
    {
        $topic = $this->tema('directorio-institucional', 'Directorio institucional');
        $this->articulo($topic);

        // Lo que se sirve en el HTML es la alternativa; Leaflet la sustituye al
        // cargar. Si esto se quedara vacío, quien navegue sin JavaScript —o con
        // un bloqueador— vería un rectángulo gris y ninguna dirección.
        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Calle 5 # 36-08, Santiago de Cali, Valle del Cauca')
            ->assertSee('openstreetmap.org/?mlat=3.430215&amp;mlon=-76.545449', false)
            ->assertSee('Ver la ubicación en OpenStreetMap');
    }
}
