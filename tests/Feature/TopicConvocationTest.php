<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Convocatorias.
 *
 * Un proceso con fecha de apertura y de cierre. Lo que las distingue de todo lo
 * demás es que cerradas se siguen leyendo: el portal publica las de 2023, todas
 * vencidas hace años, al lado de las de 2026.
 */
class TopicConvocationTest extends TestCase
{
    use RefreshDatabase;

    private function tema(): Topic
    {
        return Topic::create([
            'name' => 'Convocatorias',
            'slug' => 'convocatorias',
            'legacy_content_types' => ['Convocation'],
            'imported_at' => now(),
        ]);
    }

    private function convocatoria(Topic $topic, array $overrides = []): TopicItem
    {
        return $topic->items()->create(array_merge([
            'kind' => TopicItem::KIND_CONVOCATION,
            'title' => 'CP-HUV-23-001 SUMINISTRO MATERIAL DE OSTEOSINTESIS',
            'body' => '<p>Resumen del proceso.</p>',
            'opens_at' => now()->subYears(3),
            'closes_at' => now()->subYears(3)->addDays(15),
            'published_at' => now()->subYears(3),
            'modified_at' => now()->subYears(3),
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */

    /**
     * La regresión que justifica las columnas nuevas.
     *
     * `expires_at` significa «a partir de aquí deja de verse». Si la fecha de
     * cierre hubiera ido ahí, las cincuenta y dos convocatorias del portal
     * —todas vencidas— habrían desaparecido del listado sin que nada lo dijera.
     */
    public function test_una_convocatoria_cerrada_se_sigue_leyendo(): void
    {
        $topic = $this->tema();

        // Recién creada, `is_active` todavía vive en el valor por omisión de la
        // tabla y no en el modelo: hay que releerlo para preguntarle.
        $item = $this->convocatoria($topic)->fresh();

        $this->assertTrue($item->closes_at->isPast(), 'La prueba necesita una convocatoria vencida.');
        $this->assertNull($item->expires_at, 'El cierre no puede escribirse como caducidad.');
        $this->assertTrue($item->isPublic());

        $this->assertSame(1, $topic->items()->visible()->count());

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('CP-HUV-23-001 SUMINISTRO MATERIAL DE OSTEOSINTESIS');
    }

    /** Y su apertura y su cierre se guardan, aunque la ficha no los publique. */
    public function test_la_convocatoria_guarda_su_apertura_y_su_cierre(): void
    {
        $topic = $this->tema();

        $item = $this->convocatoria($topic, [
            'opens_at' => '2026-01-30 07:00:00',
            'closes_at' => '2026-02-27 16:00:00',
        ]);

        $this->assertSame('2026-01-30 07:00:00', $item->opens_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-27 16:00:00', $item->closes_at->format('Y-m-d H:i:s'));

        // El portal solo las enseña en el editor, así que la ficha no las
        // rotula, ni las confunde con la fecha de expedición de un documento.
        $this->get(route('topics.items.show', [$topic, $item]))
            ->assertOk()
            ->assertDontSee('Fecha de expedición')
            ->assertDontSee('2026/01/30');
    }

    /**
     * En el portal la convocatoria es una tarjeta de color liso, no una blanca
     * como las de los demás temas.
     */
    public function test_la_tarjeta_de_una_convocatoria_va_en_color(): void
    {
        $topic = $this->tema();
        $this->convocatoria($topic);

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        // Sobre la propia tarjeta, no en cualquier parte de la página: el azul
        // del buscador y el de los botones también contienen «bg-azure».
        preg_match_all('/<article[^>]*class="([^"]*)"/', $html, $articulos);

        $enColor = array_filter(
            $articulos[1] ?? [],
            fn (string $clases) => str_contains($clases, 'bg-azure') && str_contains($clases, 'text-on-accent')
        );

        $this->assertCount(1, $enColor, 'La tarjeta de la convocatoria no va en color.');

        // Con un color propio escrito a mano el modo de alto contraste dejaría
        // la tarjeta ilegible: tiene que salir del tema.
        $this->assertStringNotContainsString('#3B76FB', $html);
        $this->assertStringNotContainsString('rgb(59, 118, 251)', $html);
    }

    /** El tema no ofrece ordenar por fecha de expedición: no hay documentos. */
    public function test_el_listado_no_ofrece_la_fecha_de_expedicion(): void
    {
        $topic = $this->tema();
        $this->convocatoria($topic);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewHas('tabs', [
                ['key' => 'recientes', 'label' => 'Recientes'],
                ['key' => 'az', 'label' => 'A-Z'],
            ]);
    }

    /** Y sus archivos se publican todos, como en cualquier otro tema. */
    public function test_la_ficha_publica_todos_los_archivos(): void
    {
        $topic = $this->tema();
        $item = $this->convocatoria($topic);

        foreach (['cdp.pdf', 'aviso.pdf', 'estudios-previos.pdf'] as $i => $name) {
            $item->media()->create([
                'type' => 'file',
                'path' => 'temas/1/'.$name,
                'original_name' => $name,
                'alt' => $name,
                'size' => 1000 * ($i + 1),
                'position' => $i + 1,
            ]);
        }

        $item->load('media');

        $this->assertCount(3, $item->attachments());

        $this->get(route('topics.items.show', [$topic, $item]))
            ->assertOk()
            ->assertSee('Archivos para descargar')
            ->assertSee('cdp.pdf')
            ->assertSee('aviso.pdf')
            ->assertSee('estudios-previos.pdf');
    }
}
