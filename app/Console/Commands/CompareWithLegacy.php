<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicItem;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Qué se ha publicado en el portal anterior y todavía no está aquí.
 *
 * Mientras dure la migración, el portal de origen sigue vivo y el hospital
 * sigue publicando en él. Hasta ahora la forma de enterarse era mirar su
 * portada a ojo, y la portada solo enseña lo último: la última vez se veían
 * seis fichas nuevas y en realidad faltaban veintiséis, repartidas en cuatro
 * temas. Cuatro no se veían por ninguna parte.
 *
 * Esto pregunta tema a tema cuántos contenidos declara el origen y cuántos
 * tenemos, y dice qué hay que importar. Solo lee: no escribe en la base, no
 * toca el origen y no importa nada por su cuenta — decide una persona.
 *
 *     php artisan huv:comparar
 *     php artisan huv:comparar --tema=planes
 */
class CompareWithLegacy extends Command
{
    protected $signature = 'huv:comparar
        {--tema=* : Solo estos temas, por su slug; sin esto, los compara todos}';

    protected $description = 'Compara el contenido de cada tema con el del portal anterior';

    /** Lo que la API de origen admite por página. */
    private const PAGE_SIZE = 20;

    public function handle(): int
    {
        $base = rtrim((string) config('huv.legacy_base'), '/');

        if ($base === '') {
            $this->components->error('No hay portal de origen configurado (huv.legacy_base).');

            return self::FAILURE;
        }

        $this->components->info("Comparando con {$base}");

        $etiquetas = $this->tags($base);

        if ($etiquetas->isEmpty()) {
            $this->components->error('El origen no devolvió ninguna etiqueta. ¿Está accesible?');

            return self::FAILURE;
        }

        $temas = Topic::query()
            ->when($this->option('tema'), fn ($q) => $q->whereIn('slug', $this->option('tema')))
            ->orderBy('slug')
            ->get();

        if ($temas->isEmpty()) {
            $this->components->warn('No hay temas que comparar.');

            return self::SUCCESS;
        }

        $barra = $this->output->createProgressBar($temas->count());
        $barra->start();

        $pendientes = [];
        $sobrantes = [];
        $sinEtiqueta = [];
        $mudos = [];

        foreach ($temas as $tema) {
            $barra->advance();

            $etiqueta = $etiquetas->get($tema->slug);

            if ($etiqueta === null) {
                $sinEtiqueta[] = $tema->slug;

                continue;
            }

            $enOrigen = $this->countFor($base, $etiqueta);

            if ($enOrigen === null) {
                $mudos[] = $tema->slug;

                continue;
            }

            $nuestro = $this->countHere($tema);
            $diferencia = $enOrigen - $nuestro;

            if ($diferencia > 0) {
                $pendientes[$tema->slug] = $diferencia;
            } elseif ($diferencia < 0) {
                $sobrantes[$tema->slug] = -$diferencia;
            }
        }

        $barra->finish();
        $this->newLine(2);

        return $this->report($temas->count(), $pendientes, $sobrantes, $sinEtiqueta, $mudos);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Las etiquetas del origen, por su slug.
     *
     * @return Collection<string, int>
     */
    private function tags(string $base): Collection
    {
        $mapa = collect();
        $pagina = 0;

        do {
            $r = $this->get($base.'/api/v1/tags/', ['pageSize' => self::PAGE_SIZE, 'page' => $pagina]);

            foreach ($r['results'] ?? [] as $tag) {
                if (isset($tag['friendlyName'], $tag['tagID'])) {
                    $mapa[$tag['friendlyName']] = (int) $tag['tagID'];
                }
            }

            $pagina++;
        } while (($r['meta']['hasNextPage'] ?? false) && $pagina < 20);

        return $mapa;
    }

    /** Cuántos contenidos declara el origen para una etiqueta. */
    private function countFor(string $base, int $tagId): ?int
    {
        // pageSize=1 porque solo interesa el total, que viene en la cabecera de
        // la respuesta: no hace falta traerse los contenidos para contarlos.
        $r = $this->get($base.'/api/v1/contents', ['tags' => $tagId, 'pageSize' => 1, 'page' => 0]);

        return isset($r['meta']['totalCount']) ? (int) $r['meta']['totalCount'] : null;
    }

    /**
     * Cuántos tenemos nosotros.
     *
     * Un tema respaldado por contenidos —«Noticias», «Notificaciones
     * Judiciales»— no guarda elementos propios: sus fichas viven en `contents`
     * con esa categoría. Contarlas en `topic_items` daría cero y el tema
     * parecería vacío siempre.
     */
    private function countHere(Topic $tema): int
    {
        return $tema->content_category !== null
            ? Content::where('category', $tema->content_category)->count()
            : TopicItem::where('topic_id', $tema->id)->count();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $url, array $query): ?array
    {
        try {
            return Http::timeout(30)->retry(2, 300)->get($url, $query)->throw()->json();
        } catch (Throwable) {
            return null;
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param  array<string, int>  $pendientes
     * @param  array<string, int>  $sobrantes
     * @param  list<string>  $sinEtiqueta
     * @param  list<string>  $mudos
     */
    private function report(int $total, array $pendientes, array $sobrantes, array $sinEtiqueta, array $mudos): int
    {
        $this->components->twoColumnDetail('Temas comparados', (string) $total);

        if ($sinEtiqueta !== []) {
            $this->components->twoColumnDetail(
                '<fg=yellow>Sin equivalente en el origen</>',
                count($sinEtiqueta).': '.implode(', ', array_slice($sinEtiqueta, 0, 6))
            );
        }

        if ($mudos !== []) {
            $this->components->twoColumnDetail(
                '<fg=yellow>El origen no respondió</>',
                implode(', ', $mudos)
            );
        }

        // Tener de más no es un fallo: puede ser contenido escrito aquí, que es
        // el sentido de la migración. Se dice, y no se propone hacer nada.
        if ($sobrantes !== []) {
            $this->newLine();
            $this->components->info('Temas con más contenido aquí que en el origen (probablemente propio):');

            foreach ($sobrantes as $slug => $n) {
                $this->components->twoColumnDetail('  '.$slug, '+'.$n.' aquí');
            }
        }

        $this->newLine();

        if ($pendientes === []) {
            $this->components->info('Ningún tema tiene contenido nuevo en el origen.');

            return self::SUCCESS;
        }

        arsort($pendientes);

        $this->components->warn('Hay contenido nuevo en el origen. Para traerlo:');
        $this->newLine();

        foreach ($pendientes as $slug => $n) {
            $this->line(sprintf('  php artisan huv:importar %-44s <fg=gray># faltan %d</>', $slug, $n));
        }

        $this->newLine();

        // Código 1 a propósito: así una tarea programada puede avisar sola de
        // que hay algo que traer, sin que nadie tenga que leer la salida.
        return self::FAILURE;
    }
}
