<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Models\User;
use App\Support\CommentWall;
use App\Support\LegacyLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Temas cuyos contenidos son artículos —«Programas»—, y temas mixtos.
 */
class TopicArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'Programas',
            'slug' => 'programas',
            'legacy_content_types' => ['Article'],
            'imported_at' => now(),
        ], $overrides));
    }

    private function articulo(Topic $topic, array $overrides = []): TopicItem
    {
        return $topic->items()->create(array_merge([
            'kind' => TopicItem::KIND_ARTICLE,
            'title' => 'SAMI Programa de Salud Mental Integral',
            'body' => '<p>El Programa de Salud Mental Integral surge en el 2024.</p>',
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function conImagen(TopicItem $item): TopicItem
    {
        $item->media()->create([
            'type' => ContentMedia::TYPE_IMAGE,
            'path' => 'temas/1/sami.jpeg',
            'alt' => 'Equipo del programa',
            'is_main' => true,
        ]);

        return $item->fresh('media');
    }

    /* ------------------------------------------------------------------ */
    /* Listado */
    /* ------------------------------------------------------------------ */

    public function test_el_listado_de_articulos_conserva_la_forma_del_tema(): void
    {
        $topic = $this->tema();
        $item = $this->conImagen($this->articulo($topic));

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee($item->title)
            // Los mismos textos que en un tema documental: generalizar la vista
            // no puede haberse llevado por delante la fidelidad.
            ->assertSee('Busca en Programas')
            ->assertSee('Cargar más contenidos', false)
            ->assertSee(url('storage/temas/1/sami.jpeg'))
            ->assertSee('El Programa de Salud Mental Integral surge en el 2024.');
    }

    /**
     * El portal de origen solo ofrece ordenar por fecha de expedición donde hay
     * documentos, porque es un dato que los artículos no tienen.
     */
    public function test_la_pestana_de_expedicion_solo_sale_en_temas_con_documentos(): void
    {
        $articles = $this->tema();
        $this->articulo($articles);

        // Se comprueban los datos de la vista y no el HTML: las pestañas viajan
        // dentro de un @json, que escapa los acentos y haría fallar la búsqueda
        // de «expedición» por un motivo que no tiene nada que ver.
        $this->get(route('topics.show', $articles))
            ->assertOk()
            ->assertViewHas('tabs', fn (array $tabs) => ! in_array('expedicion', array_column($tabs, 'key'), true));

        $documents = $this->tema([
            'name' => 'Presupuesto',
            'slug' => 'presupuesto',
            'legacy_content_types' => ['Document'],
        ]);
        $documents->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Ejecución de enero',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('topics.show', $documents))
            ->assertOk()
            ->assertViewHas('tabs', fn (array $tabs) => in_array('expedicion', array_column($tabs, 'key'), true));
    }

    /**
     * En un tema de orden manual, quien edita coloca los contenidos donde
     * quiere. Ofrecer «Ordenar por» desharía ese trabajo delante del visitante,
     * y por eso el portal ni siquiera pinta la fila.
     */
    public function test_un_tema_de_orden_manual_no_ofrece_ordenar(): void
    {
        $topic = $this->tema([
            'name' => 'Control Interno',
            'slug' => 'control-interno',
            'legacy_content_types' => ['Document'],
            'legacy_template_type' => Topic::TEMPLATE_SORTABLE,
        ]);

        // Se dan de alta en orden inverso al manual a propósito.
        foreach ([['Tercero', 10], ['Primero', 30], ['Segundo', 20]] as [$title, $order]) {
            $topic->items()->create([
                'kind' => TopicItem::KIND_DOCUMENT,
                'title' => $title,
                'published_at' => now()->subDays($order),
                'legacy_display_order' => $order,
            ]);
        }

        $response = $this->get(route('topics.show', $topic))->assertOk();

        $response->assertViewHas('tabs', fn (array $tabs) => $tabs === []);

        // Manda el orden manual, no la fecha.
        $response->assertViewHas(
            'items',
            fn ($items) => $items->pluck('title')->all() === ['Primero', 'Segundo', 'Tercero']
        );
    }

    /**
     * La ficha se encabeza con la fecha de la última modificación, como en el
     * portal: un documento corregido no puede seguir anunciando el día en que
     * se subió por primera vez.
     */
    public function test_la_ficha_se_encabeza_con_la_fecha_de_modificacion(): void
    {
        $topic = $this->tema(['legacy_content_types' => ['Document']]);

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe de Austeridad',
            'published_at' => now()->subMonths(3),
            'modified_at' => now()->subDays(2),
        ]);

        $this->assertTrue($item->date()->isSameDay(now()->subDays(2)));

        // Y el listado se ordena por esa misma fecha, no por la de creación.
        $otro = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe más nuevo pero sin tocar',
            'published_at' => now()->subMonth(),
            'modified_at' => now()->subMonth(),
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewHas(
                'items',
                fn ($items) => $items->pluck('id')->all() === [$item->id, $otro->id]
            );
    }

    public function test_un_tema_corriente_si_ofrece_ordenar(): void
    {
        $topic = $this->tema(['legacy_content_types' => ['Document']]);
        $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewHas('tabs', fn (array $tabs) => count($tabs) === 3);
    }

    public function test_un_articulo_con_dos_categorias_cuenta_en_las_dos(): void
    {
        $topic = $this->tema();

        $ptee = TopicCategory::create(['topic_id' => $topic->id, 'name' => 'Programa PTEE', 'slug' => 'programa-ptee']);
        $year = TopicCategory::create(['topic_id' => $topic->id, 'name' => '2025', 'slug' => '2025']);

        $this->articulo($topic)->categories()->attach([$ptee->id, $year->id]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) {
                return $categories->firstWhere('name', 'Programa PTEE')['count'] === 1
                    && $categories->firstWhere('name', '2025')['count'] === 1;
            });
    }

    public function test_una_categoria_sin_articulos_visibles_no_aparece(): void
    {
        $topic = $this->tema();

        TopicCategory::create(['topic_id' => $topic->id, 'name' => 'Categoría vacía', 'slug' => 'categoria-vacia']);

        $this->get(route('topics.show', $topic))->assertOk()->assertDontSee('Categoría vacía');
    }

    /**
     * Un aviso es solo título y texto: no lleva archivo, ni imagen, ni fecha de
     * expedición.
     */
    public function test_un_tema_admite_avisos(): void
    {
        $topic = $this->tema([
            'name' => 'Otros',
            'slug' => 'otros',
            'legacy_content_types' => ['Document', 'Ad'],
        ]);

        $aviso = $topic->items()->create([
            'kind' => TopicItem::KIND_NOTICE,
            'title' => 'Tarifas de liquidación del Impuesto de Industria y Comercio',
            'body' => '<p>En virtud del marco normativo que nos rige…</p>',
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue($aviso->isNotice());
        // «Clasificado» es como lo llama el portal en su propio editor.
        $this->assertSame('Clasificado', $topic->itemNoun(TopicItem::KIND_NOTICE));

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee($aviso->title)
            ->assertSee('En virtud del marco normativo que nos rige');

        $this->get(route('topics.items.show', [$topic, $aviso]))
            ->assertOk()
            ->assertSee('En virtud del marco normativo que nos rige')
            // Ni recuadro de archivo ni fecha de expedición.
            ->assertDontSee('Archivos para descargar')
            ->assertDontSee('Fecha de expedición');
    }

    /** Donde se mezclan tipos, el listado deja filtrar por cuál. */
    /**
     * El filtro ofrece los tipos que hay, no los que el tema admite.
     *
     * «Planeación y presupuesto participativo» declara doce tipos en el origen
     * y publica tres. Ofrecer los otros nueve sería prometer filtros que
     * siempre devuelven la lista vacía.
     */
    public function test_un_tema_mixto_ofrece_filtrar_por_los_tipos_que_tiene(): void
    {
        $mixto = $this->tema([
            'name' => 'Otros',
            'slug' => 'otros',
            // Admite tres; solo se publicarán dos.
            'legacy_content_types' => ['Document', 'Ad', 'Convocation'],
        ]);

        $mixto->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Tarifas vigentes',
            'published_at' => now()->subDay(),
        ]);

        $mixto->items()->create([
            'kind' => TopicItem::KIND_NOTICE,
            'title' => 'Aviso de subasta',
            'published_at' => now()->subDay(),
        ]);

        $respuesta = $this->get(route('topics.show', $mixto))->assertOk();

        $respuesta->assertSee('Todos los contenidos');
        $respuesta->assertSee('>Documento</option>', false);
        $respuesta->assertSee('>Clasificado</option>', false);

        // Admitida pero sin publicar: no se ofrece.
        $respuesta->assertDontSee('>Convocatoria</option>', false);

        // Con un solo tipo no hay nada que elegir.
        $simple = $this->tema(['legacy_content_types' => ['Document']]);

        $simple->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Ejecución presupuestal',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('topics.show', $simple))
            ->assertOk()
            ->assertDontSee('Todos los contenidos');
    }

    /**
     * «Noticias» es la portada vista desde otra página: una sola copia de cada
     * noticia, en `contents`, y una sola ficha en /contenidos/{slug}.
     */
    public function test_el_tema_de_noticias_se_sirve_de_los_contenidos(): void
    {
        $topic = $this->tema([
            'name' => 'Noticias',
            'slug' => 'noticias',
            'content_category' => Content::NEWS_CATEGORY,
        ]);

        $this->assertTrue($topic->isContentBacked());

        $noticia = Content::create([
            'title' => 'El HUV cumple setenta años',
            'category' => Content::NEWS_CATEGORY,
            'body' => '<p>Una celebración de toda la ciudad.</p>',
            'published_at' => now()->subDay(),
            'show_in_feed' => true,
        ]);

        // Sale en el tema…
        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('El HUV cumple setenta años')
            ->assertSee('Busca en Noticias')
            // …enlazando a su única ficha, no a una bajo el tema.
            ->assertSee(route('contents.show', $noticia->slug), false);

        // …y en la portada, porque es el mismo contenido.
        $this->get(route('home'))->assertOk()->assertSee('El HUV cumple setenta años');

        // El tema no guarda elementos propios: no hay copia que desincronizar.
        $this->assertSame(0, $topic->items()->count());
    }

    /**
     * Regresión de maquetación: mezclar `class` con `@class` en la misma
     * etiqueta emite DOS atributos, el navegador se queda con el primero y el
     * apilado se pierde, con lo que las fotos salen a tamaño natural y
     * desbordan la tarjeta.
     */
    public function test_la_tarjeta_de_noticia_emite_un_solo_atributo_de_clases(): void
    {
        $topic = $this->tema([
            'name' => 'Noticias',
            'slug' => 'noticias',
            'content_category' => Content::NEWS_CATEGORY,
        ]);

        $content = Content::create([
            'title' => 'El HUV cumple setenta años',
            'category' => Content::NEWS_CATEGORY,
            'published_at' => now()->subDay(),
        ]);

        $content->media()->create([
            'type' => ContentMedia::TYPE_IMAGE,
            'path' => 'contenidos/aniversario.jpg',
            'alt' => 'Celebración',
            'is_main' => true,
        ]);

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        preg_match('~<article[^>]*>~', $html, $etiqueta);

        $this->assertNotEmpty($etiqueta, 'No se encontró ninguna tarjeta.');
        $this->assertSame(1, substr_count($etiqueta[0], 'class='), $etiqueta[0]);
        $this->assertStringContainsString('flex-col', $etiqueta[0]);

        // La foto ocupa el ancho y se queda con la altura que le toque por su
        // propia proporción: ni la recorta un `object-cover`, ni se la impone
        // un 16:9, ni la estira un padre sin altura. Es lo que hace el portal
        // de origen, donde cada noticia trae la suya como la subieron.
        preg_match('~<picture>.*?</picture>~s', $html, $foto);

        $this->assertNotEmpty($foto, 'No se encontró la foto de la tarjeta.');
        $this->assertStringContainsString('h-auto', $foto[0]);
        $this->assertStringNotContainsString('object-cover', $foto[0]);
        $this->assertStringNotContainsString('aspect-[', $foto[0]);
    }

    /** Un «Link» del portal es una noticia cuyo destino está fuera. */
    public function test_una_noticia_puede_apuntar_fuera_del_portal(): void
    {
        $topic = $this->tema([
            'name' => 'Noticias',
            'slug' => 'noticias',
            'content_category' => Content::NEWS_CATEGORY,
        ]);

        Content::create([
            'title' => 'Intranet',
            'category' => Content::NEWS_CATEGORY,
            'link' => 'https://intranet.huv.gov.co',
            'published_at' => now()->subDay(),
            'show_in_feed' => true,
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('https://intranet.huv.gov.co', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_un_tema_mixto_pinta_las_dos_clases_de_ficha(): void
    {
        $topic = $this->tema(['legacy_content_types' => ['Document', 'Article']]);

        $this->articulo($topic, ['title' => 'Programa Fénix']);
        $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe de gestión',
            'published_at' => now()->subDay(),
            'file_path' => 'documentos/1/informe.pdf',
            'file_name' => 'informe.pdf',
            'file_extension' => 'pdf',
            'file_size' => 2048,
        ]);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Programa Fénix')
            ->assertSee('Informe de gestión')
            // El recuadro con la extensión solo lo lleva el documento.
            ->assertSee('PDF');
    }

    /* ------------------------------------------------------------------ */
    /* Participación */
    /* ------------------------------------------------------------------ */

    public function test_participa_sale_con_muro_publico_o_privado_y_lo_ve_cualquiera(): void
    {
        // El menú principal ya tiene una entrada «Participa», así que se busca
        // el botón de la ficha por su texto oculto, que es único.
        $badge = 'Participa<span class="sr-only"> en «';

        $topic = $this->tema();
        $this->articulo($topic, ['comment_wall' => CommentWall::PUBLICA]);

        // Sin sesión iniciada: no es un distintivo de moderación.
        $this->get(route('topics.show', $topic))->assertOk()->assertSee($badge, false);

        $privado = $this->tema(['name' => 'Planes', 'slug' => 'planes']);
        $this->articulo($privado, ['comment_wall' => CommentWall::PRIVADA]);
        $this->get(route('topics.show', $privado))->assertOk()->assertSee($badge, false);

        $sin = $this->tema(['name' => 'Otros', 'slug' => 'otros']);
        $this->articulo($sin, ['comment_wall' => CommentWall::NINGUNA]);
        $this->get(route('topics.show', $sin))->assertOk()->assertDontSee($badge, false);
    }

    /* ------------------------------------------------------------------ */
    /* Visibilidad y fronteras */
    /* ------------------------------------------------------------------ */

    public function test_el_visitante_no_ve_lo_inactivo_lo_oculto_lo_programado_ni_lo_caducado(): void
    {
        $topic = $this->tema();

        $this->articulo($topic, ['title' => 'Artículo inactivo', 'is_active' => false]);
        $this->articulo($topic, ['title' => 'Artículo oculto', 'is_hidden' => true]);
        $this->articulo($topic, ['title' => 'Artículo programado', 'published_at' => now()->addWeek()]);
        $this->articulo($topic, ['title' => 'Artículo caducado', 'expires_at' => now()->subDay()]);
        $this->articulo($topic, ['title' => 'Artículo publicado']);

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Artículo publicado')
            ->assertDontSee('Artículo inactivo')
            ->assertDontSee('Artículo oculto')
            ->assertDontSee('Artículo programado')
            ->assertDontSee('Artículo caducado');
    }

    /**
     * La frontera con la portada, que ya se cruzó, y a propósito.
     *
     * Esta prueba decía que un elemento de tema NO podía salir en la portada, y
     * su comentario avisaba: «unirlos algún día tiene que ser un acto
     * deliberado, no un descuido». Ese día llegó —el muro pasó a filtrar por
     * tipo, como el portal de origen, y para eso tiene que mezclar—, así que la
     * prueba cambia de contenido pero no de intención: sigue custodiando la
     * frontera, solo que ahora la frontera es otra.
     *
     * La regla nueva: sale lo que el portal de origen marcó para su portada, y
     * solo eso. La marca se importó en `legacy_show_on_home` y aquí no se
     * decide nada por cuenta propia.
     */
    public function test_a_la_portada_solo_llega_lo_que_el_portal_marco(): void
    {
        $topic = $this->tema();

        $this->articulo($topic, [
            'title' => 'Marcado para la portada',
            'legacy_show_on_home' => true,
        ]);

        $this->articulo($topic, [
            'title' => 'Sin marcar, se queda en su tema',
            'legacy_show_on_home' => false,
            // Destacado dentro de su tema, que es otra cosa: destacar no es
            // publicar en la portada.
            'is_featured' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Marcado para la portada')
            ->assertDontSee('Sin marcar, se queda en su tema');
    }

    /** Y ni marcado sale lo que el origen no considera contenido de muro. */
    public function test_una_pregunta_o_un_tramite_no_van_al_muro_ni_marcados(): void
    {
        $topic = $this->tema();

        foreach ([TopicItem::KIND_QUESTION, TopicItem::KIND_PROCEDURE] as $clase) {
            $topic->items()->create([
                'kind' => $clase,
                'title' => 'No es contenido de muro: '.$clase,
                'slug' => 'no-muro-'.$clase,
                'published_at' => now()->subDay(),
                'legacy_show_on_home' => true,
            ]);
        }

        $respuesta = $this->get(route('home'))->assertOk();

        $respuesta->assertDontSee('No es contenido de muro: '.TopicItem::KIND_QUESTION);
        $respuesta->assertDontSee('No es contenido de muro: '.TopicItem::KIND_PROCEDURE);
    }

    /** Un artículo de tema vive en /tema/…, no en /contenidos/…: una sola URL. */
    public function test_no_hay_dos_direcciones_para_el_mismo_articulo(): void
    {
        $topic = $this->tema();
        $item = $this->articulo($topic);

        $this->get(route('topics.items.show', [$topic, $item]))->assertOk();
        $this->get('/contenidos/'.$item->slug)->assertNotFound();
    }

    /**
     * El slug de un elemento es único dentro de su tema, no en todo el portal:
     * sin acotar el enlace, una dirección serviría el contenido de otro tema.
     */
    public function test_dos_temas_admiten_el_mismo_slug_y_cada_direccion_sirve_el_suyo(): void
    {
        $programas = $this->tema();
        $planes = $this->tema(['name' => 'Planes', 'slug' => 'planes']);

        $uno = $this->articulo($programas, ['title' => 'Presentación', 'body' => '<p>La de Programas.</p>']);
        $otro = $this->articulo($planes, ['title' => 'Presentación', 'body' => '<p>La de Planes.</p>']);

        $this->assertSame($uno->slug, $otro->slug);

        $this->get(route('topics.items.show', [$programas, $uno]))
            ->assertOk()
            ->assertSee('La de Programas.')
            ->assertDontSee('La de Planes.');

        $this->get(route('topics.items.show', [$planes, $otro]))
            ->assertOk()
            ->assertSee('La de Planes.');
    }

    /* ------------------------------------------------------------------ */
    /* Ficha */
    /* ------------------------------------------------------------------ */

    public function test_la_ficha_del_articulo_muestra_imagen_cuerpo_y_fechas(): void
    {
        $topic = $this->tema();
        $item = $this->conImagen($this->articulo($topic));

        $this->get(route('topics.items.show', [$topic, $item]))
            ->assertOk()
            ->assertSee($item->title)
            ->assertSee('Modificación:')
            ->assertSee('Creación:')
            ->assertSee('El Programa de Salud Mental Integral surge en el 2024.')
            ->assertSee('Equipo del programa')
            // Un artículo no tiene fecha de expedición.
            ->assertDontSee('Fecha de expedición');
    }

    /**
     * Al compartir el enlace gana la primera etiqueta que aparece: si la ficha
     * apila un segundo juego, la tarjeta muestra la imagen genérica del portal
     * en vez de la del contenido.
     */
    public function test_la_ficha_declara_una_sola_imagen_para_compartir(): void
    {
        $topic = $this->tema();
        $item = $this->conImagen($this->articulo($topic));

        $response = $this->get(route('topics.items.show', [$topic, $item]))->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'property="og:image"'));
        $this->assertSame(1, substr_count($html, 'property="og:type"'));
        $this->assertStringContainsString(
            '<meta property="og:image" content="'.$item->imageUrl().'">',
            $html
        );
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
    }

    public function test_destacar_un_articulo_no_toca_la_noticia_destacada_de_la_portada(): void
    {
        $topic = $this->tema();
        $item = $this->articulo($topic);

        $noticia = Content::create([
            'title' => 'Noticia principal',
            'category' => Content::NEWS_CATEGORY,
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.feature', [$topic, $item]))
            ->assertRedirect();

        $this->assertTrue($item->fresh()->is_featured);
        $this->assertTrue($noticia->fresh()->is_featured);
    }
    /* ------------------------------------------------------------------ */
    /* Eventos */
    /* ------------------------------------------------------------------ */

    /**
     * Un evento es un tipo propio, no una noticia.
     *
     * El portal lo publica exactamente como un artículo —título, texto y nada
     * más—, pero guarda aparte su fecha, su lugar y su organizador. Se traen
     * los tres: «Calendario de actividades» son ciento cuarenta y un eventos y
     * ahí harán falta.
     */
    public function test_un_evento_conserva_su_fecha_lugar_y_organizador(): void
    {
        $topic = $this->tema([
            'name' => 'Rendición de cuentas',
            'slug' => 'rendicion-de-cuentas',
            'legacy_content_types' => ['Event', 'Document'],
        ]);

        $evento = $topic->items()->create([
            'kind' => TopicItem::KIND_EVENT,
            'title' => 'Invitación Audiencia Pública de Rendición de Cuentas Vigencia 2022',
            'slug' => 'audiencia-publica-de-rendicion-de-cuentas-vigencia-2022',
            'body' => '<p>La Gerencia invita a la ciudadanía.</p>',
            'opens_at' => Carbon::parse('2023-03-28 08:00:00'),
            'event_location' => 'Auditorio Carlos Manzano',
            'event_host' => 'Hospital Universitario del Valle',
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue($evento->isEvent());
        $this->assertFalse($evento->isArticle());
        $this->assertSame('Evento', $topic->itemNoun(TopicItem::KIND_EVENT));

        // Y se publica: sin el tipo, la importación lo saltaba y el listado se
        // quedaba con un contenido de menos sin decirlo.
        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('Invitación Audiencia Pública de Rendición de Cuentas Vigencia 2022');

        $this->get(route('topics.items.show', [$topic, $evento]))
            ->assertOk()
            ->assertSee('La Gerencia invita a la ciudadanía');
    }
    /* ------------------------------------------------------------------ */
    /* Trámites */
    /* ------------------------------------------------------------------ */

    /**
     * Un trámite publica su modalidad, su costo y lo que tarda.
     *
     * Son los tres datos que el portal enseña al lado del nombre y lo único
     * que hay que saber antes de venir al hospital. Llegan en la misma lista de
     * atributos que el lugar de un evento, aparte del cuerpo.
     */
    public function test_un_tramite_publica_modalidad_costo_y_duracion(): void
    {
        $topic = $this->tema([
            'name' => 'Trámites y servicios',
            'slug' => 'tramites-y-servicios',
            'legacy_content_types' => ['Procedure'],
        ]);

        $tramite = $topic->items()->create([
            'kind' => TopicItem::KIND_PROCEDURE,
            'title' => 'Historia clínica',
            'slug' => 'historia-clinica',
            'body' => '<p>Obtener la historia clínica.</p>',
            'source_url' => 'https://www.gov.co/ficha-tramites-y-servicios/T73911',
            'procedure_type' => TopicItem::PROCEDURE_IN_PERSON,
            'procedure_cost_type' => TopicItem::COST_FREE,
            'procedure_time' => '10 Días Hábiles',
            'published_at' => now()->subDay(),
        ]);

        $this->assertTrue($tramite->isProcedure());
        $this->assertSame('Trámite', $topic->itemNoun(TopicItem::KIND_PROCEDURE));
        $this->assertSame('Trámite presencial', $tramite->procedureType());
        $this->assertSame('Trámite sin costo', $tramite->procedureCost());

        // La ficha completa vive en gov.co: el listado lleva allí y no a una
        // página nuestra que solo repetiría el resumen.
        $this->assertSame('https://www.gov.co/ficha-tramites-y-servicios/T73911', $tramite->url());

        $html = $this->get(route('topics.show', $topic))->assertOk()->getContent();

        // En filas, como el portal, y no en tarjetas.
        preg_match('~<li[^>]*border-b border-line py-5.*?</li>~s', $html, $m);

        $this->assertNotEmpty($m, 'No se pintó la fila del trámite.');
        $this->assertStringContainsString('Trámite presencial', $m[0]);
        $this->assertStringContainsString('Trámite sin costo', $m[0]);
        $this->assertStringContainsString('Duración 10 Días Hábiles', $m[0]);
        $this->assertStringContainsString('Última modificación:', $m[0]);
        $this->assertStringContainsString('Ver más', $m[0]);
    }

    /**
     * «Con costo» y «costo exacto» no son lo mismo.
     *
     * Uno dice que cuesta; el otro, cuánto. Es la diferencia entre saber a qué
     * atenerse y no saberlo, así que no se resumen en la misma frase.
     */
    public function test_un_tramite_con_costo_exacto_dice_cuanto(): void
    {
        $topic = $this->tema([
            'name' => 'Trámites y servicios',
            'slug' => 'tramites-y-servicios',
            'legacy_content_types' => ['Procedure'],
        ]);

        $conCosto = $topic->items()->create([
            'kind' => TopicItem::KIND_PROCEDURE,
            'title' => 'Terapia',
            'slug' => 'terapia',
            'procedure_cost_type' => TopicItem::COST_PAID,
            'published_at' => now(),
        ]);

        $exacto = $topic->items()->create([
            'kind' => TopicItem::KIND_PROCEDURE,
            'title' => 'Copia de historia clínica',
            'slug' => 'copia-de-historia-clinica',
            'procedure_cost_type' => TopicItem::COST_EXACT,
            'procedure_cost' => '$12.000',
            'published_at' => now(),
        ]);

        $this->assertSame('Trámite con costo', $conCosto->procedureCost());
        $this->assertSame('Costo: $12.000', $exacto->procedureCost());

        // Sin la cifra, «costo exacto» no puede prometer lo que no tiene.
        $exacto->procedure_cost = null;

        $this->assertSame('Trámite con costo', $exacto->procedureCost());
    }
}
