<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Support\RichText;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa un tema documental del portal actual.
 *
 * El portal de micolombiadigital es una SPA sobre una API REST pública, así que
 * no hace falta raspar HTML: los contenidos llegan ya estructurados, con su
 * título, descripción, fecha de expedición, categoría y archivo.
 *
 *   php artisan huv:importar presupuesto
 *   php artisan huv:importar presupuesto --sin-archivos   (solo metadatos)
 *
 * Es idempotente: cada documento se identifica por su identificador de origen,
 * así que volver a ejecutarlo actualiza lo que cambió y descarga solo lo que
 * falte. Se puede interrumpir y retomar sin problema.
 */
class ImportTopic extends Command
{
    protected $signature = 'huv:importar
        {tema : Nombre del tema en el portal actual, por ejemplo «presupuesto»}
        {--sin-archivos : Importa los metadatos sin descargar los archivos}
        {--limite= : Importa solo los primeros N documentos, útil para probar}';

    protected $description = 'Importa un tema documental desde el portal actual';

    /** El máximo que admite la API de origen. */
    private const PAGE_SIZE = 20;

    /**
     * La API cuenta las páginas desde cero.
     *
     * Empezar en 1 no da error: devuelve la segunda página y se pierde la
     * primera en silencio, que es peor.
     */
    private const FIRST_PAGE = 0;

    public function handle(): int
    {
        $base = rtrim((string) config('huv.legacy_base'), '/');

        if ($base === '') {
            $this->error('No hay portal de origen configurado (huv.legacy_base).');

            return self::FAILURE;
        }

        $slug = Str::slug($this->argument('tema'));

        $this->components->info("Importando «{$slug}» desde {$base}");

        $tag = $this->findTag($base, $slug);

        if (! $tag) {
            $this->error("El portal de origen no tiene un tema llamado «{$slug}».");

            return self::FAILURE;
        }

        $topic = Topic::updateOrCreate(
            ['legacy_tag_id' => $tag['tagID']],
            ['name' => $tag['name'], 'slug' => $slug, 'description' => $tag['description'] ?? null]
        );

        $items = $this->fetchContents($base, $tag['tagID']);

        if ($limit = $this->option('limite')) {
            $items = array_slice($items, 0, (int) $limit);
        }

        $this->components->info(count($items).' documentos encontrados.');

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        $created = $updated = $downloaded = $failed = 0;

        foreach ($items as $item) {
            try {
                $detail = $this->fetchDetail($base, $item['friendlyName']);
                $document = $this->store($topic, $detail);

                $document->wasRecentlyCreated ? $created++ : $updated++;

                if (! $this->option('sin-archivos') && ! $document->isDownloaded()) {
                    $this->download($document) ? $downloaded++ : $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->components->warn("«{$item['friendlyName']}»: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $topic->update(['imported_at' => now()]);

        $this->components->twoColumnDetail('Tema', $topic->name);
        $this->components->twoColumnDetail('Categorías', (string) $topic->categories()->count());
        $this->components->twoColumnDetail('Documentos nuevos', (string) $created);
        $this->components->twoColumnDetail('Actualizados', (string) $updated);
        $this->components->twoColumnDetail('Archivos descargados', (string) $downloaded);

        if ($failed > 0) {
            $this->components->warn("{$failed} con problemas. Vuelva a ejecutar el comando para reintentarlos.");
        }

        $this->components->info('Listo: /tema/'.$topic->slug);

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>|null
     */
    private function findTag(string $base, string $slug): ?array
    {
        $page = self::FIRST_PAGE;

        do {
            $response = Http::timeout(30)->retry(2, 500)
                ->get("{$base}/api/v1/tags/", ['pageSize' => self::PAGE_SIZE, 'page' => $page])
                ->throw()
                ->json();

            foreach ($response['results'] ?? [] as $tag) {
                if (($tag['friendlyName'] ?? null) === $slug) {
                    return $tag;
                }
            }

            $page++;
        } while (($response['meta']['hasNextPage'] ?? false) && $page <= 50);

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchContents(string $base, int $tagId): array
    {
        $items = [];
        $page = self::FIRST_PAGE;

        do {
            $response = Http::timeout(30)->retry(2, 500)
                ->get("{$base}/api/v1/contents", [
                    'tags' => $tagId,
                    'pageSize' => self::PAGE_SIZE,
                    'page' => $page,
                ])
                ->throw()
                ->json();

            $items = array_merge($items, $response['results'] ?? []);
            $page++;
        } while (($response['meta']['hasNextPage'] ?? false) && $page <= 200);

        // El origen dice cuántos hay: si no cuadran, algo se quedó fuera y hay
        // que enterarse ahora, no meses después al echar en falta un documento.
        $expected = $response['meta']['totalCount'] ?? null;

        if ($expected !== null && count($items) !== $expected) {
            $this->components->warn(
                "El origen declara {$expected} contenidos y se recibieron ".count($items).'. Revise la paginación.'
            );
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchDetail(string $base, string $friendlyName): array
    {
        return Http::timeout(30)->retry(2, 500)
            ->get("{$base}/api/v1/contents/".rawurlencode($friendlyName))
            ->throw()
            ->json();
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function store(Topic $topic, array $detail): Document
    {
        // `labels` y `files` llegan como listas aunque el portal solo permita
        // una categoría y un archivo por documento.
        $category = $this->categoryFor($topic, $detail['labels'][0] ?? null);
        $file = $detail['files'][0] ?? null;
        $name = $file['name'] ?? null;

        $document = Document::firstOrNew(['legacy_content_id' => $detail['contentID']]);

        $document->fill([
            'topic_id' => $topic->id,
            'topic_category_id' => $category?->id,
            'title' => $detail['name'],
            'description' => $this->descriptionFrom($detail),
            'issued_at' => $this->date($detail['startingDate'] ?? null),
            'published_at' => $this->date($detail['creationDate'] ?? null),
            'modified_at' => $this->date($detail['modifiedDate'] ?? null),
            'source_url' => $file['filePath'] ?? null,
            'file_name' => $name,
            'file_size' => $file['size'] ?? null,
            'file_extension' => $name ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : null,
            'is_featured' => (bool) ($detail['isFeatured'] ?? false),
            'is_active' => (bool) ($detail['published'] ?? true),
        ]);

        if (blank($document->slug)) {
            $document->slug = $document->uniqueSlug($detail['friendlyName'] ?: $detail['name']);
        }

        $document->save();

        return $document;
    }

    /**
     * @param  array<string, mixed>|null  $label
     */
    private function categoryFor(Topic $topic, ?array $label): ?TopicCategory
    {
        if (blank($label['name'] ?? null)) {
            return null;
        }

        return TopicCategory::updateOrCreate(
            ['topic_id' => $topic->id, 'slug' => Str::slug($label['name'])],
            ['name' => $label['name'], 'legacy_label_id' => $label['labelID'] ?? null]
        );
    }

    /**
     * La descripción viene como HTML en `body`; se guarda saneada para poder
     * mostrarla y seguir editándola con el mismo editor que el resto del sitio.
     *
     * @param  array<string, mixed>  $detail
     */
    private function descriptionFrom(array $detail): ?string
    {
        foreach (['body', 'description'] as $field) {
            if (filled($detail[$field] ?? null)) {
                return RichText::clean((string) $detail[$field]);
            }
        }

        return filled($detail['metaDescription'] ?? null)
            ? '<p>'.e(trim((string) $detail['metaDescription'])).'</p>'
            : null;
    }

    private function date(?string $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }

    /** Descarga el archivo y lo deja bajo storage/app/public/documentos. */
    private function download(Document $document): bool
    {
        if (blank($document->source_url)) {
            return false;
        }

        // Archivos de varios megas: se escriben por partes para no cargarlos
        // enteros en memoria.
        $response = Http::timeout(180)->retry(2, 1000)->withOptions(['stream' => true])
            ->get($document->source_url);

        if (! $response->successful()) {
            return false;
        }

        $name = $document->file_name ?: basename(parse_url($document->source_url, PHP_URL_PATH));
        $path = 'documentos/'.$document->topic_id.'/'.Str::random(8).'-'.Str::slug(pathinfo($name, PATHINFO_FILENAME))
            .'.'.ltrim($document->file_extension ?: pathinfo($name, PATHINFO_EXTENSION), '.');

        Storage::disk('public')->put($path, $response->getBody()->detach());

        $document->update([
            'file_path' => $path,
            'file_size' => Storage::disk('public')->size($path),
        ]);

        return true;
    }
}
