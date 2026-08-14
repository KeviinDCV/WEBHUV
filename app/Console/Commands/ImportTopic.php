<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Support\CommentWall;
use App\Support\RichText;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Importa un tema del portal actual: sus documentos y sus artículos.
 *
 * El portal de micolombiadigital es una SPA sobre una API REST pública, así que
 * no hace falta raspar HTML: los contenidos llegan ya estructurados, con su
 * título, cuerpo, fechas, categorías, imagen y archivos.
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

    protected $description = 'Importa un tema desde el portal actual';

    /** El máximo que admite la API de origen. */
    private const PAGE_SIZE = 20;

    /**
     * La API cuenta las páginas desde cero.
     *
     * Empezar en 1 no da error: devuelve la segunda página y se pierde la
     * primera en silencio, que es peor.
     */
    private const FIRST_PAGE = 0;

    /* Resultados de intentar traer el archivo de un documento. */
    private const FILE_DOWNLOADED = 'descargado';

    private const FILE_UP_TO_DATE = 'al-dia';

    /** El origen no publica ningún archivo: no es un fallo y no se reintenta. */
    private const FILE_MISSING = 'sin-archivo';

    private const FILE_FAILED = 'fallo';

    /**
     * Espera creciente entre reintentos, en milisegundos.
     *
     * El portal de origen empieza a devolver 403 cuando se le piden cientos de
     * contenidos seguidos —no es un permiso denegado, es un freno—. Medio
     * segundo no basta para que se le pase: hay que darle tiempo de verdad,
     * o una importación grande termina con fallos que no lo son.
     */
    private const RETRY_BACKOFF = [1000, 4000, 10000];

    /**
     * Temas del portal que en este aplicativo son categorías de contenido.
     *
     * No son temas documentales: sus contenidos son las noticias que ya
     * alimentan la portada. Importarlos a `topic_items` dejaría cada noticia
     * por duplicado y dos sitios donde editarla, así que van a `contents`.
     */
    private const CONTENT_CATEGORIES = [
        'noticias' => 'Noticias',
        'notificaciones-judiciales' => 'Notificaciones Judiciales',
    ];

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
            [
                'name' => $tag['name'],
                'slug' => $slug,
                'description' => $tag['description'] ?? null,
                // Se guarda la lista entera y no un tipo: 22 de los 62 temas del
                // portal declaran varios, y 14 los mezclan de verdad. Ausente
                // significa documental, que es como se comportaba antes.
                'legacy_content_types' => $tag['validContentTypes'] ?? null,
                // «Sortable»: el orden lo pone quien edita, no la fecha.
                'legacy_template_type' => $tag['templateType'] ?? null,
                'content_template' => $this->templateFor($base, $slug),
                // Hay temas que son la portada vista desde otra página; sus
                // contenidos viven en `contents` y no se duplican aquí.
                'content_category' => self::CONTENT_CATEGORIES[$slug] ?? null,
            ]
        );

        $entries = $this->fetchContents($base, $tag['tagID']);

        if ($limit = $this->option('limite')) {
            $entries = array_slice($entries, 0, (int) $limit);
        }

        $this->components->info(count($entries).' contenidos encontrados.');

        $bar = $this->output->createProgressBar(count($entries));
        $bar->start();

        $created = $updated = $files = $images = $failed = 0;
        $skipped = [];
        $failures = [];
        $emptyBody = [];
        $withoutAlt = [];
        $withoutFile = [];
        $inlineImages = [];
        $incomplete = [];

        foreach ($entries as $entry) {
            try {
                $detail = $this->fetchDetail($base, $entry['friendlyName']);

                // Los temas que son la portada escriben en `contents`: una sola
                // copia de cada noticia, se lea desde donde se lea.
                if ($topic->isContentBacked()) {
                    $content = $this->storeContent($topic, $detail);

                    $content->wasRecentlyCreated ? $created++ : $updated++;

                    if (filled($detail['body'] ?? null) && blank($content->body)) {
                        $emptyBody[] = $entry['friendlyName'];
                    }

                    if (! $this->option('sin-archivos')) {
                        ['images' => $i, 'files' => $f] = $this->downloadMedia($content, $detail);
                        $images += $i;
                        $files += $f;

                        if ($content->images()->contains(fn ($image) => blank($image->alt))) {
                            $withoutAlt[] = $entry['friendlyName'];
                        }
                    }

                    $bar->advance();

                    continue;
                }

                $kind = $this->kindFor($topic, $detail);

                if ($kind === null) {
                    // Enlaces, avisos, encuestas… todavía no se publican aquí:
                    // se cuentan y se avisa, en lugar de guardarlos como lo que
                    // no son.
                    $skipped[$detail['contentType'] ?? 'desconocido'] =
                        ($skipped[$detail['contentType'] ?? 'desconocido'] ?? 0) + 1;
                    $bar->advance();

                    continue;
                }

                $item = $this->storeItem($topic, $detail, $kind);

                $item->wasRecentlyCreated ? $created++ : $updated++;

                // El saneador descarta las etiquetas que no conoce junto con su
                // contenido: si el cuerpo entra con texto y sale vacío, se dice.
                if (filled($detail['body'] ?? null) && blank($item->body)) {
                    $emptyBody[] = $entry['friendlyName'];
                }

                if (substr_count((string) ($detail['body'] ?? ''), '<img') > 0) {
                    $inlineImages[] = $entry['friendlyName'];
                }

                if (! $this->option('sin-archivos')) {
                    if ($item->isDocument()) {
                        try {
                            match ($this->download($item)) {
                                self::FILE_DOWNLOADED => $files++,
                                self::FILE_MISSING => $withoutFile[] = $entry['friendlyName'],
                                self::FILE_FAILED => $failed++,
                                default => null,
                            };
                        } catch (\Throwable) {
                            // Que se caiga el principal no puede costar los
                            // adjuntos: se cuenta y se sigue con el resto.
                            $failed++;
                        }

                        // Los demás archivos del documento. El origen publica
                        // hasta veinticinco en uno solo, y quedarse con el
                        // primero los tiraba sin decir nada.
                        // Con imagen: un documento puede traerla y el portal la
                        // pinta en la tarjeta en vez del icono del archivo, como
                        // en «ACREDITACIÓN», que es un documento con foto y sin
                        // archivo. La ficha sigue siendo texto y descargas.
                        ['files' => $f, 'failed' => $x] = $this->downloadMedia(
                            $item,
                            $detail,
                            array_slice($detail['files'] ?? [], 1, null, true)
                        );

                        $files += $f;
                        $failed += $x;
                    } else {
                        ['images' => $i, 'files' => $f, 'failed' => $x] = $this->downloadMedia($item, $detail);
                        $images += $i;
                        $files += $f;
                        $failed += $x;

                        if ($item->images()->contains(fn ($image) => blank($image->alt))) {
                            $withoutAlt[] = $entry['friendlyName'];
                        }
                    }

                    // El recuento vale para todos, no solo para los documentos:
                    // «Convocatorias» trae noventa y siete archivos repartidos
                    // entre quince convocatorias, y perderlos sería igual de
                    // silencioso que lo fue con los documentos.
                    if ($missing = $this->missingFiles($item, $detail)) {
                        $incomplete[$entry['friendlyName']] = $missing;
                    }
                }
            } catch (\Throwable $e) {
                // Se apunta para el resumen en vez de escribirlo aquí: la barra
                // de progreso se come las líneas sueltas, y quedarse con un «1
                // con problemas» sin decir cuál no sirve de nada.
                $failures[$entry['friendlyName']] = $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $topic->update(['imported_at' => now()]);

        $this->components->twoColumnDetail('Tema', $topic->name);
        $this->components->twoColumnDetail('Admite', implode(', ', $topic->supportedKinds()));
        $this->components->twoColumnDetail('Categorías', (string) $topic->categories()->count());
        $this->components->twoColumnDetail('Contenidos nuevos', (string) $created);
        $this->components->twoColumnDetail('Actualizados', (string) $updated);
        $this->components->twoColumnDetail('Archivos descargados', (string) $files);
        $this->components->twoColumnDetail('Imágenes descargadas', (string) $images);

        foreach ($skipped as $type => $count) {
            $this->components->warn("{$count} de tipo «{$type}» omitidos: este aplicativo todavía no los publica.");
        }

        if ($emptyBody !== []) {
            $this->components->error(
                'Estos contenidos se quedaron sin cuerpo al depurar el HTML: '.implode(', ', $emptyBody)
            );
        }

        if ($inlineImages !== []) {
            $this->components->warn(
                'Con imágenes incrustadas en el texto, que no se conservan: '.implode(', ', $inlineImages)
            );
        }

        if ($withoutAlt !== []) {
            $this->components->warn(
                'Las imágenes llegan sin descripción y hay que escribirla a mano: '.implode(', ', $withoutAlt)
            );
        }

        // No es un fallo ni se arregla reintentando: en el origen no hay nada
        // que traer. Se dice aparte para no invitar a reejecutar en balde, que
        // además revertiría las correcciones hechas a mano desde aquí.
        if ($withoutFile !== []) {
            $this->components->warn(
                'Sin archivo en el origen: '.implode(', ', $withoutFile)
            );
        }

        // La comprobación que faltaba: contar lo que el origen publica y lo que
        // ha quedado aquí. Un documento con veinticinco archivos del que solo se
        // guardó uno era indistinguible en la base de uno que de verdad tiene
        // uno, y así se tiraron dieciocho archivos sin que nadie se enterara.
        if ($incomplete !== []) {
            $this->components->error('Documentos a los que les faltan archivos: '.implode(
                ', ',
                array_map(fn ($estado, $name) => "{$name} ({$estado})", $incomplete, array_keys($incomplete))
            ));
        }

        foreach ($failures as $name => $message) {
            $this->components->error("«{$name}»: {$message}");
        }

        $total = $failed + count($failures) + count($incomplete);

        if ($total > 0) {
            $this->components->warn("{$total} con problemas. Vuelva a ejecutar el comando para reintentarlos.");
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
            $response = Http::timeout(30)->retry(self::RETRY_BACKOFF)
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
        return Http::timeout(30)->retry(self::RETRY_BACKOFF)
            ->get("{$base}/api/v1/contents/".rawurlencode($friendlyName))
            ->throw()
            ->json();
    }

    /**
     * La plantilla que precarga el editor al crear un contenido en el tema.
     *
     * Hay que pedirla al detalle del tema: el listado la devuelve vacía en los
     * sesenta y dos. Y llega como cadena JSON, no como HTML suelto.
     *
     * Un tema sin plantilla no debe abortar la importación, así que cualquier
     * problema aquí se traduce en «sin plantilla».
     */
    private function templateFor(string $base, string $slug): ?string
    {
        try {
            $raw = Http::timeout(30)->retry(2, 500)
                ->get("{$base}/api/v1/tags/".rawurlencode($slug))
                ->json('defaultContentTemplate');

            return RichText::clean(json_decode((string) $raw, true)['template'] ?? null);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Qué es cada contenido.
     *
     * El tipo es del elemento, no del tema: hay temas que mezclan documentos,
     * artículos, enlaces y avisos en el mismo listado. Cuando el origen no lo
     * dice, manda el tipo por defecto del tema, que sin declaración es
     * documental —lo único que existía antes—.
     *
     * @param  array<string, mixed>  $detail
     */
    private function kindFor(Topic $topic, array $detail): ?string
    {
        $type = $detail['contentType'] ?? null;

        // La correspondencia vive en Topic y solo allí: tenerla también aquí
        // dejó el tema admitiendo preguntas mientras la importación se las
        // saltaba por no conocer el tipo.
        return $type === null
            ? $topic->defaultKind()
            : Topic::kindForLegacyType($type);
    }

    /**
     * Guarda una noticia en la tabla de contenidos.
     *
     * Un «Link» del portal es una noticia cuyo destino está fuera: aquí se
     * distingue por tener `link`, que es lo que ya decide Content::url().
     *
     * @param  array<string, mixed>  $detail
     */
    private function storeContent(Topic $topic, array $detail): Content
    {
        $content = Content::firstOrNew(['legacy_content_id' => $detail['contentID']]);

        // `isFeatured` del origen NO se importa. Allí no gobierna el hueco
        // grande del bloque de noticias —su portada muestra siempre la más
        // reciente—, y traerlo colocaba arriba una noticia de 2024 sin foto
        // mientras las de esta semana quedaban en la columna. Cuál se destaca
        // aquí lo decide quien edita, desde el lápiz de la ficha.
        $content->fill([
            'category' => $topic->content_category,
            'title' => Str::limit($detail['name'], 150, ''),
            'body' => $this->bodyFrom($detail),
            'published_at' => $this->date($detail['creationDate'] ?? null),
            'modified_at' => $this->date($detail['modifiedDate'] ?? null),
            'expires_at' => $this->closingDate($detail['closingDate'] ?? null),
            'comment_wall' => (int) ($detail['commentWallType'] ?? CommentWall::NINGUNA),
            'is_active' => (bool) ($detail['published'] ?? true),
            // En el origen los 68 contenidos de «Noticias» salen en portada.
            'show_in_feed' => (bool) ($detail['showOnHome'] ?? true),
            'link' => ($detail['contentType'] ?? null) === 'Link'
                ? ($detail['embedURL'] ?? $detail['embedUrl'] ?? null)
                : null,
        ]);

        if (blank($content->slug)) {
            $content->slug = $content->uniqueSlug($detail['friendlyName'] ?: $detail['name']);
        }

        $content->save();

        $content->topicCategories()->sync(
            collect($detail['labels'] ?? [])
                ->map(fn (array $label) => $this->categoryFor($topic, $label)?->id)
                ->filter()
                ->all()
        );

        return $content;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function storeItem(Topic $topic, array $detail, string $kind): TopicItem
    {
        $item = TopicItem::firstOrNew(['legacy_content_id' => $detail['contentID']]);

        $item->fill([
            'topic_id' => $topic->id,
            'kind' => $kind,
            'title' => Str::limit($detail['name'], 150, ''),
            'body' => $this->bodyFrom($detail),
            'published_at' => $this->date($detail['creationDate'] ?? null),
            'modified_at' => $this->date($detail['modifiedDate'] ?? null),
            'is_featured' => (bool) ($detail['isFeatured'] ?? false),
            'is_active' => (bool) ($detail['published'] ?? true),
            // No se traduce a `is_hidden`: en el portal de origen, lo que no
            // sale en su portada sigue estando en el listado del tema.
            'legacy_show_on_home' => isset($detail['showOnHome']) ? (bool) $detail['showOnHome'] : null,
            'legacy_display_order' => $detail['displayOrder'] ?? null,
        ]);

        // Se apunta antes de tocarla: `download()` compara con ella para saber
        // si el origen publicó otro archivo.
        $item->previousFileUrl = $item->getOriginal('source_url');

        // Una convocatoria abre y cierra, pero cerrada se sigue leyendo: el
        // portal publica las de 2023 al lado de las de 2026. Por eso su cierre
        // va a una columna propia y NO a `expires_at`, que aquí significa «deja
        // de verse»: habría escondido cuarenta y siete de las cincuenta y dos.
        $convocation = $kind === TopicItem::KIND_CONVOCATION;
        $event = $kind === TopicItem::KIND_EVENT;

        // Un evento comparte columna con la apertura de una convocatoria: las
        // dos responden a «desde cuándo». Lugar y organizador llegan en una
        // lista de atributos aparte del cuerpo.
        $item->opens_at = $convocation || $event ? $this->date($detail['startingDate'] ?? null) : null;
        $item->closes_at = $convocation ? $this->date($detail['closingDate'] ?? null) : null;
        $item->event_location = $event ? $this->attribute($detail, 'EventLocation') : null;
        $item->event_host = $event ? $this->attribute($detail, 'EventHost') : null;

        // Los dos bloques se escriben SIEMPRE, aunque uno quede en nulo. Un
        // contenido puede cambiar de tipo en el origen, y dejar la caducidad de
        // cuando era artículo lo volvería invisible en el listado sin que nada
        // lo dijera.
        if ($kind === TopicItem::KIND_DOCUMENT) {
            $file = $detail['files'][0] ?? null;
            $name = $file['name'] ?? null;

            $item->issued_at = $this->date($detail['startingDate'] ?? null);

            // Sin archivo adjunto, un documento todavía puede tener destino:
            // «Decreto Único Reglamentario del Sector Salud» no sube el decreto,
            // apunta al PDF que publica MinSalud, y «Gaceta Departamental» a la
            // página de la gaceta. El portal los publica como a los demás, con
            // su fila y su enlace; quedarnos solo con `filePath` dejaba dos
            // fichas sin nada que ofrecer.
            $item->source_url = $file['filePath'] ?? ($detail['embedUrl'] ?? null);
            $item->file_name = $name;
            $item->file_size = $file['size'] ?? null;
            // La extensión llega siempre vacía: se deduce del nombre.
            $item->file_extension = $name ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) : null;

            // El origen retiró el archivo: el que hay en disco se queda sin
            // nadie que lo describa. Sin esto la ficha seguía ofreciendo una
            // descarga con el título por nombre y un «PDF» inventado.
            if ($file === null && $item->isDownloaded()) {
                $item->deleteFile();
                $item->file_path = null;
            }

            $item->expires_at = null;
            $item->comment_wall = CommentWall::NINGUNA;
        } else {
            $item->expires_at = $convocation
                ? null
                : $this->closingDate($detail['closingDate'] ?? null);
            $item->comment_wall = (int) ($detail['commentWallType'] ?? CommentWall::NINGUNA);

            // Dejó de ser documento: su archivo se va con él.
            if ($item->isDownloaded()) {
                $item->deleteFile();
            }

            $item->issued_at = null;
            $item->file_path = null;
            $item->file_name = null;
            $item->file_size = null;
            $item->file_extension = null;

            // Un enlace guarda su destino donde el documento guarda el suyo: es
            // el mismo campo, «dónde está esto de verdad». La API lo manda unas
            // veces como embedUrl y otras como embedURL.
            $item->source_url = $kind === TopicItem::KIND_LINK
                ? ($detail['embedUrl'] ?? $detail['embedURL'] ?? null)
                : null;
        }

        if (blank($item->slug)) {
            $item->slug = $item->uniqueSlug($detail['friendlyName'] ?: $detail['name']);
        }

        $item->save();

        // `labels` trae varias por contenido: el programa de transparencia está
        // a la vez en «Programa PTEE» y en «2025». Quedarse con la primera
        // perdía la otra.
        $item->categories()->sync(
            collect($detail['labels'] ?? [])
                ->map(fn (array $label) => $this->categoryFor($topic, $label)?->id)
                ->filter()
                ->all()
        );

        return $item;
    }

    /**
     * El origen guarda «sin fecha final» como una fecha imposible: el año 2038.
     * Importarla dejaría el editor abierto con una caducidad que nadie puso.
     */
    private function closingDate(?string $value): ?Carbon
    {
        $date = $this->date($value);

        return $date?->isAfter(now()->addYears(10)) ? null : $date;
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
     * El cuerpo viene como HTML; se guarda saneado para poder mostrarlo y
     * seguir editándolo con el mismo editor que el resto del sitio.
     *
     * normalizeLegacy antes de clean, y no al revés: el editor del portal
     * anterior emite <div>, <b> e <i>, que el saneador descarta junto con el
     * texto que envuelven.
     *
     * @param  array<string, mixed>  $detail
     */
    private function bodyFrom(array $detail): ?string
    {
        foreach (['body', 'description'] as $field) {
            if (filled($detail[$field] ?? null)) {
                return RichText::clean(RichText::normalizeLegacy((string) $detail[$field]));
            }
        }

        return filled($detail['metaDescription'] ?? null)
            ? '<p>'.e(trim((string) $detail['metaDescription'])).'</p>'
            : null;
    }

    /**
     * Un atributo suelto del contenido.
     *
     * El origen los publica como una lista de pares —«EventLocation»,
     * «EventHost»— en vez de como campos propios, así que hay que buscarlos.
     *
     * @param  array<string, mixed>  $detail
     */
    private function attribute(array $detail, string $name): ?string
    {
        foreach ($detail['attributes'] ?? [] as $attribute) {
            if (($attribute['attribute'] ?? null) === $name) {
                return filled($attribute['value'] ?? null) ? trim((string) $attribute['value']) : null;
            }
        }

        return null;
    }

    private function date(?string $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }

    /**
     * Cuántos archivos publica el origen y cuántos han quedado aquí.
     *
     * Devuelve null cuando cuadran. Es la única forma de que una importación
     * demuestre que no ha perdido nada: sin este recuento, un documento con
     * veinticinco archivos del que se guardó uno se parece en la base a uno que
     * de verdad tiene uno.
     *
     * @param  array<string, mixed>  $detail
     */
    private function missingFiles(TopicItem $item, array $detail): ?string
    {
        $expected = count($detail['files'] ?? []);

        // Se cuenta uno a uno por identificador de origen, no por tipo.
        //
        // Por identificador, porque los medios sin él los añadió alguien desde
        // el editor y no cuadran contra lo que publica el portal: contarlos
        // daba un «4 de 2» permanente que invitaba a reejecutar el comando y
        // enterraba el aviso de verdad.
        //
        // Y no por tipo, porque un adjunto que resulta ser una foto se guarda
        // como imagen —el portal las mezcla con los documentos y las marca con
        // `isImage`—, así que mirar solo las descargas haría que «Valores y
        // Principios Corporativos» avisara de «0 de 7» teniendo las siete.
        // Comparando contra esta lista la portada se queda fuera sola: su
        // identificador sale de `defaultImage` y no está en ella.
        $ids = array_filter(array_column($detail['files'] ?? [], 'fileID'));

        $actual = ($item->isDownloaded() ? 1 : 0)
            + $item->media()->whereIn('legacy_file_id', $ids ?: [0])->count();

        return $actual === $expected ? null : "{$actual} de {$expected}";
    }

    /**
     * Trae el archivo del documento al disco público.
     *
     * Se vuelve a descargar cuando el origen publica otro archivo: los metadatos
     * —nombre, peso, extensión— ya se han actualizado en storeItem(), así que
     * conservar el archivo viejo haría que la ficha anunciara una cosa y
     * entregara otra.
     */
    private function download(TopicItem $item): string
    {
        // Sin nombre de archivo no hay archivo que traer, aunque haya destino:
        // ahí `source_url` es el enlace externo del documento —el PDF de
        // MinSalud, la página de la Gaceta Departamental— y descargarlo
        // guardaría una página web haciéndose pasar por el documento.
        if (blank($item->source_url) || blank($item->file_name)) {
            return self::FILE_MISSING;
        }

        // `source_url` es la dirección del archivo en el origen: si no ha
        // cambiado, el que está en disco sigue siendo el bueno.
        if ($item->isDownloaded() && $item->source_url === $item->previousFileUrl) {
            return self::FILE_UP_TO_DATE;
        }

        $stored = $this->fetchFile(
            $item->source_url,
            'documentos/'.$item->topic_id,
            $item->file_name
        );

        if (! $stored) {
            return self::FILE_FAILED;
        }

        // El anterior se borra: quedaría ocupando disco sin que nada lo enlace.
        $item->deleteFile();

        $item->update(['file_path' => $stored['path'], 'file_size' => $stored['size']]);

        return self::FILE_DOWNLOADED;
    }

    /**
     * Imagen principal y adjuntos de un contenido.
     *
     * `$files` permite pasar una sublista: un documento guarda el primero de
     * sus archivos en columnas propias y trae por aquí el resto, y necesita que
     * se conserven las claves del origen para que las posiciones no se pisen.
     *
     * @param  Model  $item  Cualquier modelo con una relación media()
     * @param  array<string, mixed>  $detail
     * @param  array<int, array<string, mixed>>|null  $files  Por omisión, los del detalle
     * @return array{images: int, files: int, failed: int}
     */
    private function downloadMedia(Model $item, array $detail, ?array $files = null, bool $withImage = true): array
    {
        $files ??= $detail['files'] ?? [];
        $images = $downloaded = $failed = 0;
        $seen = [];

        // El origen no publica el original: la dirección sin sufijo devuelve un
        // 404 y solo responden las versiones recortadas. La mayor es el máster.
        // Tampoco da texto alternativo: se deja vacío y el comando lo avisa,
        // porque inventarlo con el título sería peor (WCAG 1.1.1).
        if ($withImage && filled($detail['defaultImage'] ?? null)) {
            $media = $this->attach($item, [
                'legacy_file_id' => $detail['fileID'] ?? null,
                'type' => ContentMedia::TYPE_IMAGE,
                'is_main' => true,
                'position' => 0,
            ], $detail['defaultImage']);

            $images += (int) ($media?->wasRecentlyCreated ?? false);
            $seen[] = $media?->id;
        }

        // Cero o muchos: «Informes a organismos de inspección, vigilancia y
        // control» trae veinticinco.
        foreach ($files as $position => $file) {
            // El origen mezcla fotos y documentos en la misma lista y los
            // distingue con `isImage`; según ese campo el portal las pinta en
            // el carrusel de la ficha o las publica como descargas. Sin mirarlo,
            // los siete JPG de «Valores y Principios Corporativos» salían aquí
            // como siete filas de «JPG · 856 Kb» en vez de como las láminas que
            // son.
            $isImage = (bool) ($file['isImage'] ?? false);

            try {
                $media = $this->attach($item, [
                    'legacy_file_id' => $file['fileID'] ?? null,
                    'type' => $isImage ? ContentMedia::TYPE_IMAGE : ContentMedia::TYPE_FILE,
                    // En una descarga `alt` es el rótulo visible y el nombre del
                    // archivo sirve. En una foto es el texto alternativo, y
                    // «valores-y-principios-respeto.jpg» no describe nada a
                    // quien no la ve (WCAG 1.1.1): se deja vacío y el resumen
                    // avisa de que hay que escribirlo.
                    'alt' => $isImage ? null : ($file['name'] ?? null),
                    'original_name' => $file['name'] ?? null,
                    'position' => $position + 1,
                ], $file['filePath'] ?? null);
            } catch (\Throwable) {
                // Que el origen se atragante con el séptimo archivo no puede
                // costar los dieciocho siguientes: se apunta y se sigue.
                $failed++;

                continue;
            }

            // Cuenta también la sustitución, no solo el alta: un archivo que el
            // origen cambia se vuelve a bajar sobre la fila que ya existía, y
            // contar solo las altas hacía que el resumen dijera «0 archivos»
            // después de haber descargado nueve.
            $traido = (int) ($media?->wasRecentlyCreated || $media?->wasChanged('path'));

            if ($isImage) {
                $images += $traido;
            } else {
                $downloaded += $traido;
            }

            $seen[] = $media?->id;
        }

        // Podar con información incompleta borraría medios que sí siguen
        // publicados y solo se han quedado sin descargar esta vez.
        if ($failed === 0) {
            $this->pruneMedia($item, array_filter($seen));
        }

        return ['images' => $images, 'files' => $downloaded, 'failed' => $failed];
    }

    /**
     * Retira los medios importados que el origen ya no publica.
     *
     * Sin esto, sustituir una foto en el portal dejaba las dos aquí —ambas
     * marcadas como principal— y un adjunto retirado seguía descargable.
     *
     * Los medios sin identificador de origen se respetan: son los que se
     * añadieron a mano desde el editor y no le pertenecen a la importación.
     *
     * @param  list<int>  $seen
     */
    private function pruneMedia(Model $item, array $seen): void
    {
        $item->media()
            ->whereNotNull('legacy_file_id')
            ->whereNotIn('id', $seen ?: [0])
            ->get()
            ->each->delete();

        // Solo una imagen puede ser la principal, y la principal es la portada:
        // la que sale de `defaultImage`, no la primera que se encuentre.
        //
        // Antes se preguntaba por mainImage(), que cuando no hay ninguna
        // marcada devuelve la primera de la lista. Daba igual mientras de
        // `files` no salieran imágenes —la única que había era la portada—,
        // pero ahora un artículo puede traer siete láminas y ninguna portada, y
        // ascender la primera la sacaba de la galería, la dejaba sin enlace ni
        // texto alternativo, la convertía en la miniatura de la tarjeta y
        // descuadraba el recuento de integridad, que avisaba de «6 de 7»
        // archivos perdidos en cada pasada teniendo los siete.
        //
        // Se marca por consulta y no con update() sobre el modelo: el que se
        // acaba de crear ya cree ser el principal, así que Eloquent no vería
        // nada que cambiar y no escribiría.
        $item->load('media');

        if ($main = $item->images()->firstWhere('is_main', true)) {
            $item->media()
                ->where('type', ContentMedia::TYPE_IMAGE)
                ->whereKeyNot($main->getKey())
                ->update(['is_main' => false]);
        }
    }

    /**
     * Trae un medio del artículo, si no estaba ya.
     *
     * @param  array<string, mixed>  $attributes
     * @return ContentMedia|null El medio, nuevo o el que ya existía
     */
    private function attach(Model $item, array $attributes, ?string $url): ?ContentMedia
    {
        // Solo se busca el que ya existía cuando el origen da identificador.
        // Buscar por «legacy_file_id = null» emparejaría entre sí todos los
        // medios sin identificador y los colapsaría en uno solo.
        $media = $attributes['legacy_file_id'] === null
            ? null
            : $item->media()->firstWhere('legacy_file_id', $attributes['legacy_file_id']);

        // Un texto alternativo escrito a mano no se borra nunca. El origen no
        // lo publica —las fotos llegan siempre sin él, y por eso el resumen
        // avisa de que hay que escribirlo—, así que dejar pasar el vacío haría
        // que cada reimportación destruyera justo lo que se pidió escribir.
        //
        // Con una excepción: cuando una descarga pasa a ser foto. Ahí el `alt`
        // que hay no lo escribió nadie, lo puso la importación anterior con el
        // nombre del fichero, que como rótulo de una descarga sirve y como
        // texto alternativo de una imagen no describe nada (WCAG 1.1.1). Ese sí
        // se limpia, y así el aviso del resumen vuelve a pedirlo.
        $reclasificada = ($attributes['type'] ?? null) === ContentMedia::TYPE_IMAGE
            && $media?->type === ContentMedia::TYPE_FILE;

        if ($media && ! $reclasificada && blank($attributes['alt'] ?? null) && filled($media->alt)) {
            unset($attributes['alt']);
        }

        // Ya está y el origen no lo ha sustituido: se refresca lo que sí puede
        // haber cambiado sin cambiar el fichero —el orden, el nombre— y se deja
        // el archivo en paz. Antes se devolvía sin tocar nada, y entonces una
        // reordenación en el origen no llegaba nunca: el listado quedaba en el
        // orden del día que se importó por primera vez.
        if ($media && filled($media->path) && $media->source_url === $url) {
            $media->update($attributes);

            return $media;
        }

        $directory = $item instanceof Content ? 'contenidos' : 'temas/'.$item->topic_id;

        $stored = $this->fetchFile($url, $directory, $attributes['original_name'] ?? null);

        if (! $stored) {
            return $media;
        }

        $attributes += ['path' => $stored['path'], 'size' => $stored['size'], 'source_url' => $url];

        if ($media === null) {
            return $item->media()->create($attributes);
        }

        // Sustitución: el origen publicó otro archivo con el mismo
        // identificador. El anterior se va, o se quedaría ocupando disco sin que
        // nada lo enlace.
        $media->deleteFile();
        $media->update($attributes);

        return $media;
    }

    /**
     * Extensiones que se aceptan del portal de origen.
     *
     * Las mismas que admite el formulario, más las de imagen que necesitan las
     * fotos de los artículos. Todo lo demás se guarda como «.bin»: lo que se
     * descarga acaba bajo el directorio que sirve el servidor web, y un «.php»
     * o un «.html» ahí dentro es ejecución de código o un XSS almacenado con el
     * origen del portal.
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'zip',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        // El portal adjunta la grabación de la audiencia pública de
        // rendición de cuentas como un MP4 más de la lista de descargas.
        'mp4',
    ];

    /**
     * Con qué extensión se guarda un archivo cuyo nombre no la trae.
     *
     * Solo tipos de la lista de arriba: esto sirve para reconocer lo que ya se
     * admite, no para colar formatos nuevos por la puerta del `Content-Type`.
     */
    private const EXTENSION_BY_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/webp' => 'webp',
        'video/mp4' => 'mp4',
    ];

    /**
     * Trae un archivo del portal anterior al disco público.
     *
     * El nombre se ensucia con unos caracteres al azar a propósito: dos
     * contenidos distintos pueden traer un «informe.pdf» y el segundo pisaría
     * al primero.
     *
     * @return array{path: string, size: int}|null
     */
    private function fetchFile(?string $url, string $directory, ?string $name = null): ?array
    {
        if (blank($url)) {
            return null;
        }

        // Archivos de varios megas: se escriben por partes para no cargarlos
        // enteros en memoria.
        // Con la misma espera creciente que la API: un tema documental dispara
        // ahora una descarga por archivo y no una por documento, y el origen
        // responde 403 cuando se le pide mucho seguido.
        $response = Http::timeout(180)->retry(self::RETRY_BACKOFF)
            ->withOptions(['stream' => true])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        $name = $name ?: basename((string) parse_url($url, PHP_URL_PATH));

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        // Sin extensión utilizable, se pregunta por el tipo que declara el
        // servidor. Las imágenes que el portal enlaza desde Google Fotos vienen
        // con nombres como «Iomj-45CjnDS6…=w1200-h630-p», sin punto ni nada
        // parecido a un formato; guardarlas como .bin las servía luego como
        // «application/octet-stream» y el navegador se las descargaba en vez de
        // pintarlas.
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $extension = self::EXTENSION_BY_MIME[strtolower(trim(
                Str::before((string) $response->header('Content-Type'), ';')
            ))] ?? null;
        }

        if ($extension === null) {
            $this->newLine();
            $this->components->warn(
                "«{$name}» llega sin una extensión que se admita y sin tipo declarado: se guarda como .bin."
            );

            $extension = 'bin';
        }

        $path = trim($directory, '/').'/'.Str::random(8).'-'
            .Str::slug(Str::limit(pathinfo($name, PATHINFO_FILENAME), 80, '')).'.'.$extension;

        Storage::disk('public')->put($path, $response->getBody()->detach());

        return ['path' => $path, 'size' => Storage::disk('public')->size($path)];
    }
}
