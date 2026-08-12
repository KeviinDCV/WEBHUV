<?php

namespace Tests\Feature\Admin;

use App\Models\ContentMedia;
use App\Models\Topic;
use App\Models\TopicItem;
use App\Models\User;
use App\Support\CommentWall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El editor de un elemento de tema.
 *
 * Dos cosas que no sabía hacer: subir más de un archivo a un documento —el
 * portal publica hasta veinticinco en uno solo— y las fechas de apertura y
 * cierre de una convocatoria.
 */
class TopicItemEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function editor(): User
    {
        return User::firstOrCreate(
            ['email' => 'editora@huv.gov.co'],
            ['name' => 'Editora del portal', 'password' => Hash::make('Contrasena-Segura-2026#')]
        );
    }

    private function tema(string $tipo, string $slug): Topic
    {
        return Topic::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'legacy_content_types' => [$tipo],
            'imported_at' => now(),
        ]);
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 40, 'application/pdf');
    }

    /* ---------------- Documentos con varios archivos ---------------- */

    public function test_un_documento_admite_varios_archivos_desde_el_editor(): void
    {
        $topic = $this->tema('Document', 'planes');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'Plan Anual de Adquisiciones',
                'body' => '<p>Versiones del plan.</p>',
                'show_in_feed' => '1',
                'file' => $this->pdf('principal.pdf'),
                'files' => [$this->pdf('anexo-uno.pdf'), $this->pdf('anexo-dos.pdf')],
                'file_titles' => ['Anexo uno', 'Anexo dos'],
            ])
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();
        $item->load('media');

        // El principal sigue en sus columnas: es el que da icono y peso a la
        // tarjeta del listado.
        $this->assertSame('principal.pdf', $item->file_name);
        $this->assertTrue($item->isDownloaded());

        // Y los dos anexos existen, con su fichero en disco.
        $this->assertCount(2, $item->files());

        foreach ($item->files() as $file) {
            $this->assertTrue(Storage::disk('public')->exists($file->path));
        }

        // La ficha los publica los tres juntos, sin que se note la costura.
        $item->setRelation('topic', $topic);
        $this->assertSame(
            ['principal.pdf', 'Anexo uno', 'Anexo dos'],
            $item->attachments()->pluck('name')->all()
        );
    }

    /** Y se pueden quitar uno a uno sin dejar el fichero ocupando disco. */
    public function test_se_puede_retirar_un_adjunto_de_un_documento(): void
    {
        $topic = $this->tema('Document', 'planes');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'Plan Anual de Adquisiciones',
                'body' => '<p>Versiones.</p>',
                'show_in_feed' => '1',
                'file' => $this->pdf('principal.pdf'),
                'files' => [$this->pdf('anexo.pdf')],
                'file_titles' => ['Anexo'],
            ])
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();
        $adjunto = $item->files()->sole();

        $this->actingAs($this->editor())
            ->put(route('admin.topics.items.update', [$topic, $item]), [
                'title' => $item->title,
                'body' => $item->body,
                'show_in_feed' => '1',
                'media_delete' => [$adjunto->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ContentMedia::whereKey($adjunto->id)->count());
        $this->assertFalse(Storage::disk('public')->exists($adjunto->path));

        // El principal no se toca al retirar un adjunto.
        $this->assertTrue($item->fresh()->isDownloaded());
    }

    /** Los adjuntos se guardan junto a lo demás del tema, no en una carpeta suelta. */
    public function test_los_adjuntos_se_guardan_en_la_carpeta_del_tema(): void
    {
        $topic = $this->tema('Document', 'planes');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'Plan',
                'body' => '<p>Texto.</p>',
                'show_in_feed' => '1',
                'file' => $this->pdf('principal.pdf'),
                'files' => [$this->pdf('anexo.pdf')],
                'file_titles' => ['Anexo'],
            ])
            ->assertSessionHasNoErrors();

        $adjunto = TopicItem::sole()->files()->sole();

        $this->assertStringStartsWith('temas/'.$topic->id.'/', $adjunto->path);
    }

    /**
     * Reemplazar el archivo principal suelta la dirección de origen.
     *
     * Sin eso, la siguiente reimportación devolvía nombre, peso y extensión a
     * los del portal y dejaba en disco el archivo de quien edita: la ficha
     * anunciaba una cosa y entregaba otra.
     */
    public function test_reemplazar_el_principal_suelta_la_direccion_de_origen(): void
    {
        $topic = $this->tema('Document', 'planes');

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe',
            'body' => '<p>Texto.</p>',
            'source_url' => 'https://portal-anterior.example/archivos/informe.pdf',
            'file_path' => 'documentos/'.$topic->id.'/viejo.pdf',
            'file_name' => 'informe.pdf',
            'file_size' => 1000,
            'file_extension' => 'pdf',
            'published_at' => now(),
        ]);

        Storage::disk('public')->put($item->file_path, 'viejo');

        $this->actingAs($this->editor())
            ->put(route('admin.topics.items.update', [$topic, $item]), [
                'title' => $item->title,
                'body' => $item->body,
                'show_in_feed' => '1',
                'file' => $this->pdf('informe-corregido.pdf'),
            ])
            ->assertSessionHasNoErrors();

        $item = $item->fresh();

        $this->assertNull($item->source_url, 'Sigue apuntando al archivo del portal.');
        $this->assertSame('informe-corregido.pdf', $item->file_name);
        $this->assertFalse(Storage::disk('public')->exists('documentos/'.$topic->id.'/viejo.pdf'));
    }

    /** Con un enlace escrito a mano, ese enlace manda y se conserva. */
    public function test_un_enlace_escrito_a_mano_se_respeta_al_reemplazar(): void
    {
        $topic = $this->tema('Document', 'planes');

        $item = $topic->items()->create([
            'kind' => TopicItem::KIND_DOCUMENT,
            'title' => 'Informe',
            'body' => '<p>Texto.</p>',
            'published_at' => now(),
        ]);

        $this->actingAs($this->editor())
            ->put(route('admin.topics.items.update', [$topic, $item]), [
                'title' => $item->title,
                'body' => $item->body,
                'show_in_feed' => '1',
                'link' => 'https://www.huv.gov.co/informe.pdf',
                'file' => $this->pdf('informe.pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://www.huv.gov.co/informe.pdf', $item->fresh()->source_url);
    }

    /* ---------------- Convocatorias ---------------- */

    public function test_se_publica_una_convocatoria_con_su_apertura_y_su_cierre(): void
    {
        $topic = $this->tema('Convocation', 'convocatorias');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'CP-HUV-26-005 -PROFESIONALES CLINICOS',
                'body' => '<p>Resumen del proceso.</p>',
                'show_in_feed' => '1',
                'opens_at' => '2026-01-30T07:00',
                'closes_at' => '2026-02-27T16:00',
                'comment_wall' => CommentWall::NINGUNA,
                'files' => [$this->pdf('cdp.pdf'), $this->pdf('aviso.pdf')],
                'file_titles' => ['cdp.pdf', 'aviso.pdf'],
            ])
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();

        $this->assertSame(TopicItem::KIND_CONVOCATION, $item->kind);
        $this->assertSame('2026-01-30 07:00:00', $item->opens_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-27 16:00:00', $item->closes_at->format('Y-m-d H:i:s'));

        // Lo importante: el cierre no puede convertirse en caducidad, o la
        // convocatoria desaparecería del listado en cuanto pasara la fecha.
        $this->assertNull($item->expires_at);

        $item->load('media');
        $this->assertCount(2, $item->files());
    }

    /** Una convocatoria vencida sigue publicándose. */
    public function test_una_convocatoria_ya_cerrada_no_desaparece(): void
    {
        $topic = $this->tema('Convocation', 'convocatorias');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'CP-HUV-23-001',
                'body' => '<p>Proceso de 2023.</p>',
                'show_in_feed' => '1',
                'opens_at' => '2023-01-23T08:00',
                'closes_at' => '2023-02-07T11:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $topic->items()->visible()->count());

        $this->get(route('topics.show', $topic))
            ->assertOk()
            ->assertSee('CP-HUV-23-001');
    }

    /** Cerrar antes de abrir es una errata, no una convocatoria. */
    public function test_el_cierre_no_puede_ir_antes_de_la_apertura(): void
    {
        $topic = $this->tema('Convocation', 'convocatorias');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'CP-HUV-26-099',
                'body' => '<p>Texto.</p>',
                'show_in_feed' => '1',
                'opens_at' => '2026-02-27T16:00',
                'closes_at' => '2026-01-30T07:00',
            ])
            // Con el mensaje en español, no con el de Laravel en inglés.
            ->assertSessionHasErrors(['closes_at' => 'La fecha de cierre no puede ir antes de la de inicio.']);

        $this->assertSame(0, TopicItem::count());
    }

    /**
     * Un adjunto sin título toma el nombre del fichero.
     *
     * Antes reventaba con un error del servidor: la línea leía el título por
     * índice sin comprobar que existiera, y el editor no manda título para un
     * archivo al que nadie le escribió uno.
     */
    public function test_un_adjunto_sin_titulo_toma_el_nombre_del_fichero(): void
    {
        $topic = $this->tema('Document', 'planes');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'Plan',
                'body' => '<p>Texto.</p>',
                'show_in_feed' => '1',
                'file' => $this->pdf('principal.pdf'),
                'files' => [$this->pdf('anexo.pdf')],
                // Sin `file_titles`, como cuando nadie escribe nada.
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('anexo.pdf', TopicItem::sole()->files()->sole()->alt);
    }

    /** Un documento se puede publicar solo con adjuntos, sin archivo principal. */
    public function test_un_documento_se_puede_publicar_solo_con_adjuntos(): void
    {
        $topic = $this->tema('Document', 'planes');

        $this->actingAs($this->editor())
            ->post(route('admin.topics.items.store', $topic), [
                'title' => 'Plan por partes',
                'body' => '<p>Texto.</p>',
                'show_in_feed' => '1',
                'files' => [$this->pdf('parte-uno.pdf'), $this->pdf('parte-dos.pdf')],
                'file_titles' => ['Parte uno', 'Parte dos'],
            ])
            ->assertSessionHasNoErrors();

        $item = TopicItem::sole();
        $item->load('media');
        $item->setRelation('topic', $topic);

        $this->assertFalse($item->isDownloaded());
        $this->assertCount(2, $item->attachments());
    }

    /* ---------------- Lo que NO lleva archivos ---------------- */

    /** Un aviso, una pregunta o un enlace siguen siendo título y texto. */
    public function test_el_bloque_de_medios_no_se_ofrece_a_lo_que_no_lo_lleva(): void
    {
        foreach ([['Ad', 'ofertas'], ['Faq', 'preguntas'], ['Link', 'contrataciones']] as [$tipo, $slug]) {
            $topic = $this->tema($tipo, $slug);

            $html = $this->actingAs($this->editor())
                ->get(route('topics.show', $topic))
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString(
                'name="files[]"',
                $html,
                "El editor de «{$slug}» ofrece adjuntar archivos y no debería."
            );
        }
    }

    /**
     * Fotos, vídeo y biblioteca solo donde la ficha los pinta.
     *
     * La ficha de un documento y la de una convocatoria son texto y archivos:
     * ofrecerles subir fotos guardaba en disco algo invisible.
     */
    public function test_ni_el_documento_ni_la_convocatoria_admiten_fotos(): void
    {
        foreach ([['Document', 'planes'], ['Convocation', 'convocatorias']] as [$tipo, $slug]) {
            $topic = $this->tema($tipo, $slug);

            $html = $this->actingAs($this->editor())
                ->get(route('topics.show', $topic))
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString('name="photos[]"', $html, "«{$slug}» ofrece subir fotos.");
            $this->assertStringNotContainsString('name="video_url"', $html, "«{$slug}» ofrece poner vídeo.");

            // Y si alguien las envía de todos modos, no se guardan.
            $this->actingAs($this->editor())
                ->post(route('admin.topics.items.store', $topic), [
                    'title' => 'Con foto colada',
                    'body' => '<p>Texto.</p>',
                    'show_in_feed' => '1',
                    'file' => $tipo === 'Document' ? $this->pdf('principal.pdf') : null,
                    'photos' => [UploadedFile::fake()->image('foto.jpg')],
                    'photo_alts' => ['Una foto'],
                ])
                ->assertSessionHasNoErrors();

            $item = TopicItem::where('topic_id', $topic->id)->sole();
            $item->load('media');

            $this->assertCount(0, $item->images(), "«{$slug}» guardó una foto que su ficha no pinta.");
        }
    }

    /** Y el de un documento o una convocatoria sí lo ofrece. */
    public function test_el_bloque_de_medios_se_ofrece_donde_toca(): void
    {
        foreach ([['Document', 'planes'], ['Convocation', 'convocatorias'], ['Article', 'programas']] as [$tipo, $slug]) {
            $topic = $this->tema($tipo, $slug);

            $html = $this->actingAs($this->editor())
                ->get(route('topics.show', $topic))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(
                'name="files[]"',
                $html,
                "El editor de «{$slug}» no deja adjuntar archivos."
            );
        }
    }
}
