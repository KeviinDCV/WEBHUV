<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\TopicItem;
use App\Support\LegacyLink;
use Illuminate\Support\Str;
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

        // Migrado: el tema y la ficha de dentro. Con las comillas del href, y
        // no la dirección suelta: «/tema/entidad» es prefijo literal de
        // «/tema/entidad/mision-y-vision», así que sin el delimitador la
        // primera comprobación la satisfacía la segunda y el enlace al tema
        // podía desaparecer sin que nadie se enterara.
        $respuesta->assertSee('href="'.route('topics.show', 'entidad').'"', false);
        $respuesta->assertSee('href="'.route('topics.items.show', ['entidad', 'mision-y-vision']).'"', false);

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

    /**
     * Los grupos llegan plegados, pero con todo dentro.
     *
     * Aquí no se copia al portal: allí los doce salen abiertos de golpe, más
     * de cien enlaces seguidos y una barra de desplazamiento que no termina.
     * Plegados, la página cabe en una pantalla y se ve el índice entero.
     *
     * Plegado no es escondido: las entradas están en el HTML, así que las
     * encuentran el buscador del navegador y los rastreadores. Un desplegable
     * que las cargara al abrirse dejaría el índice fuera de las búsquedas, y
     * este índice existe para que la información pública se encuentre.
     */
    public function test_los_grupos_llegan_plegados_y_con_su_contenido_dentro(): void
    {
        $html = $this->get(route('transparency'))->assertOk()->getContent();

        // Ni un solo grupo abierto de salida. Se busca el ATRIBUTO, no la
        // cadena «<details open»: la etiqueta se abre con su id y su clase
        // delante, así que un `open` caería detrás y esa cadena no aparecería
        // nunca. Buscándola, la comprobación pasaba igual con los doce grupos
        // abiertos, que es justo el defecto que dice impedir.
        $this->assertDoesNotMatchRegularExpression('~<details[^>]*\sopen[\s>=]~', $html);

        // Y aun así, dentro está todo.
        $this->assertStringContainsString('Actos administrativos de nombramientos y encargos', $html);
        $this->assertStringContainsString('Decreto Único Reglamentario', $html);

        // Un <details> por grupo, ni uno más.
        $this->assertSame(
            count(config('huv.transparency_index.groups')),
            substr_count($html, '<details')
        );
    }

    /** El segundo grupo, con su tercer nivel. */
    public function test_el_indice_publica_el_grupo_de_normatividad(): void
    {
        $respuesta = $this->get(route('transparency'))->assertOk();

        $respuesta->assertSee('2.1.');
        $respuesta->assertSee('2.2.');
        $respuesta->assertSee('Busqueda de normas', false);

        foreach (['Leyes', 'Decreto Único Reglamentario', 'Normativa aplicable', 'Gaceta Oficial'] as $hija) {
            $respuesta->assertSee($hija, false);
        }

        $respuesta->assertSee('2.1.5.');
        $respuesta->assertSee('Políticas, lineamientos y manuales', false);
    }

    /**
     * Un destino que apunta a un tema filtrado abre ese tema ya filtrado.
     *
     * El grupo de Contratación no enlaza a listados enteros sino a un año
     * concreto —«/tema/planes/2024-526540»—, que en el portal es «el tema
     * planes, categoría 2024». Aquí la categoría es un identificador, así que
     * hay que traducirla; si no, el enlace llevaría al listado completo y quien
     * busca el plan de un año tendría que filtrarlo a mano.
     */
    public function test_un_destino_con_categoria_abre_el_tema_ya_filtrado(): void
    {
        $topic = Topic::create([
            'name' => 'Planes',
            'slug' => 'planes',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        $categoria = $topic->categories()->create([
            'name' => '2024',
            'slug' => '2024-526540',
        ]);

        $this->get(route('transparency'))
            ->assertOk()
            ->assertSee(route('topics.show', ['planes', 'categoria' => $categoria->id]), false);
    }

    /**
     * Un destino con tramos de más abre el tema, sin filtrar.
     *
     * «Informes trimestrales sobre acceso a información, quejas y reclamos»
     * enlaza a «/tema/control/informes-trimestrales-pqrsfd-2023/2024-483422»,
     * tres tramos donde el portal solo entiende dos. No es un enlace roto: allí
     * abre «Rendición de cuentas» con «Todas las categorías» marcado, ignorando
     * lo que sobra. Aquí se hacía lo contrario —darlo por intraducible y
     * mandarlo al portal anterior— y el visitante salía del sitio teniendo el
     * tema en casa.
     */
    public function test_un_destino_con_tramos_de_mas_abre_el_tema_sin_filtrar(): void
    {
        Topic::create([
            'name' => 'Rendición de cuentas',
            'slug' => 'control',
            'legacy_content_types' => ['Document'],
            'imported_at' => now(),
        ]);

        LegacyLink::forget();

        $this->assertSame(
            route('topics.show', 'control'),
            LegacyLink::rewrite('/tema/control/informes-trimestrales-pqrsfd-2023/2024-483422')
        );

        $this->get(route('transparency'))
            ->assertOk()
            ->assertSee(route('topics.show', 'control'), false);
    }

    /**
     * Cada grupo sigue siendo un encabezado, aunque ahora se pliegue.
     *
     * Quien navega con lector de pantalla recorre un índice como este saltando
     * de encabezado en encabezado —tecla H, o la lista de encabezados—. Al
     * convertir los grupos en <details> se perdieron los doce de golpe y la
     * página se quedó con un solo encabezado, el título. El modelo de contenido
     * de <summary> admite uno dentro, así que no hay que elegir entre plegar y
     * poder navegar.
     */
    public function test_cada_grupo_sigue_siendo_un_encabezado(): void
    {
        $html = $this->get(route('transparency'))->assertOk()->getContent();

        $contenido = Str::between($html, '<main', '</main>');

        foreach (config('huv.transparency_index.groups') as $grupo) {
            $this->assertMatchesRegularExpression(
                '~<h2[^>]*>\s*'.preg_quote($grupo['label'], '~').'\s*</h2>~u',
                $contenido,
                "El grupo «{$grupo['label']}» dejó de ser un encabezado."
            );
        }
    }

    /**
     * Están los doce grupos que exige la norma, y en su orden.
     *
     * La numeración del índice no es decorativa: es como se cita cada apartado
     * en una auditoría de la Resolución 1519 de 2020. Que falte un grupo, o que
     * dos estén cambiados de sitio, corre toda la numeración de ahí en adelante
     * y deja de coincidir con lo que pide quien audita.
     */
    public function test_estan_los_doce_grupos_de_la_resolucion(): void
    {
        $grupos = array_column(config('huv.transparency_index.groups'), 'label');

        $this->assertSame([
            'Información de la entidad',
            'Normatividad',
            'Contratación',
            'Planeación',
            'Trámites',
            'Participa',
            'Datos abiertos',
            'Información específica para grupos de interés',
            'Obligación de reporte de información específica',
            'Atención y servicio a la ciudadanía',
            'Noticias',
            'Condiciones técnicas y de seguridad digital',
        ], $grupos);

        // Y los doce llegan a la página, cada uno con su número.
        $respuesta = $this->get(route('transparency'))->assertOk();

        foreach ($grupos as $posicion => $etiqueta) {
            $respuesta->assertSee($etiqueta, false);
            $respuesta->assertSee('huv-transparencia-'.($posicion + 1), false);
        }
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
