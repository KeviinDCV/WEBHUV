<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicItem;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El índice de Transparencia.
 *
 * El mapa que exige la Resolución 1519 de 2020: grupos numerados que llevan a
 * donde de verdad está cada cosa. No tiene contenido propio, así que lo que hay
 * que comprobar es la numeración —así se cita cada apartado en una auditoría— y
 * que los destinos se muden solos según avanza la migración.
 */
class TransparencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los temas migrados se consultan una sola vez por peticion y se
        // guardan en estatica: sin vaciarla, el indice sigue creyendo que no
        // hay nada migrado cuando la prueba anterior no creo ningun tema.
        LegacyLink::forget();
    }

    private function grupo(): array
    {
        return config('huv.transparency_index.groups.0');
    }

    public function test_el_indice_publica_el_primer_grupo_con_su_numeracion(): void
    {
        $respuesta = $this->get(route('transparency'))->assertOk();

        $respuesta->assertSee('Información de la entidad');

        // La numeración del texto legal, entrada a entrada.
        foreach ($this->grupo()['items'] as $posicion => $entrada) {
            $respuesta->assertSee('1.'.($posicion + 1).'.');
            $respuesta->assertSee($entrada['label'], false);
        }

        // Y las dos hijas de la primera, con su tercer nivel.
        $respuesta->assertSee('1.1.1.');
        $respuesta->assertSee('1.1.2.');
        $respuesta->assertSee('Misión y Visión', false);
        $respuesta->assertSee('Funciones y deberes', false);
    }

    /**
     * Un destino ya migrado enlaza aquí dentro; uno que no, al portal anterior.
     *
     * Es lo que permite trasladar el índice entero de una vez y que se vaya
     * mudando solo: sin esto habría que volver a editarlo cada vez que se
     * importa un tema.
     */
    public function test_los_destinos_migrados_enlazan_dentro_y_el_resto_al_portal_anterior(): void
    {
        $topic = Topic::create([
            'name' => 'Entidad',
            'slug' => 'entidad',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ]);

        $topic->items()->create([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'Misión y Visión',
            'slug' => 'mision-y-vision',
            'published_at' => now()->subDay(),
        ]);

        $respuesta = $this->get(route('transparency'))->assertOk();

        // Migrado: el tema y la ficha de dentro.
        $respuesta->assertSee(route('topics.show', 'entidad'), false);
        $respuesta->assertSee(route('topics.items.show', ['entidad', 'mision-y-vision']), false);

        // Sin migrar: se queda en el portal anterior, y avisando de que sale.
        $respuesta->assertSee(
            rtrim((string) config('huv.legacy_base'), '/').'/tema/calendario-de-actividades',
            false
        );
        $respuesta->assertSee('se abre en una pestaña nueva');
    }

    /** El enlace de fuera se anuncia y se abre aparte; el de casa, no. */
    public function test_solo_lo_que_sale_fuera_abre_pestana_nueva(): void
    {
        $html = $this->get(route('transparency'))->assertOk()->getContent();

        // «Mecanismo de presentación directa…» es una dirección de otra casa.
        $this->assertMatchesRegularExpression(
            '~<a href="https://acortar\.link/OUtyCS"\s+target="_blank" rel="noopener noreferrer"~',
            $html
        );
    }

    /** Ninguna entrada del índice se queda sin destino. */
    public function test_ninguna_entrada_se_queda_sin_destino(): void
    {
        $sinDestino = [];

        $revisar = function (array $entradas, string $prefijo) use (&$revisar, &$sinDestino): void {
            foreach ($entradas as $posicion => $entrada) {
                $orden = $prefijo.($posicion + 1);

                if (blank($entrada['path'] ?? null) && blank($entrada['url'] ?? null)) {
                    $sinDestino[] = $orden.' '.$entrada['label'];
                }

                $revisar($entrada['children'] ?? [], $orden.'.');
            }
        };

        foreach (config('huv.transparency_index.groups') as $numero => $grupo) {
            $revisar($grupo['items'], ($numero + 1).'.');
        }

        $this->assertSame([], $sinDestino);
    }
}
