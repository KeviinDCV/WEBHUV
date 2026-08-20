<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\TopicItem;
use App\Models\Translation;
use App\Support\Translator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Traduce al inglés el contenido que llegó del portal.
 *
 *   php artisan huv:traducir --simular    (cuenta y estima el coste, no gasta)
 *   php artisan huv:traducir
 *   php artisan huv:traducir --modelo=contenidos --limite=20
 *
 * Se puede interrumpir y retomar: cada campo se guarda con la huella del texto
 * que se tradujo, así que una segunda pasada solo manda lo que falta o lo que
 * cambió en el portal. Traducir dos veces lo mismo cuesta dinero, y por eso el
 * comando trabaja así y no de una tacada.
 */
class TranslateContent extends Command
{
    protected $signature = 'huv:traducir
        {--idioma=en : Idioma de destino}
        {--modelo= : Solo uno: contenidos, elementos, temas o categorias}
        {--limite= : Traduce como mucho N campos, útil para probar}
        {--simular : Cuenta lo que haría y estima el coste, sin llamar a la API}';

    protected $description = 'Traduce el contenido importado al inglés con la API de Google';

    /** Lo que se traduce, y en qué orden se ve el avance. */
    private const MODELOS = [
        'contenidos' => Content::class,
        'elementos' => TopicItem::class,
        'temas' => Topic::class,
        'categorias' => TopicCategory::class,
    ];

    /** Tarifa pública de Cloud Translation, en dólares por millón de caracteres. */
    private const USD_POR_MILLON = 20.0;

    public function handle(): int
    {
        $idioma = (string) $this->option('idioma');

        if ($idioma === config('huv.content_locale')) {
            $this->error('El idioma de destino es el mismo en el que está escrito el contenido.');

            return self::FAILURE;
        }

        $traductor = Translator::make();

        if (! $this->option('simular') && ! $traductor->configured()) {
            $this->error('Falta GOOGLE_TRANSLATE_KEY. Con --simular se puede ver el coste sin clave.');

            return self::FAILURE;
        }

        $pendientes = $this->pending($idioma);

        if ($pendientes === []) {
            $this->components->info('No hay nada que traducir: todo está al día.');

            return self::SUCCESS;
        }

        $caracteres = array_sum(array_map(fn (array $p): int => mb_strlen($p['texto']), $pendientes));

        $this->components->twoColumnDetail('Campos por traducir', (string) count($pendientes));
        $this->components->twoColumnDetail('Caracteres', number_format($caracteres));
        $this->components->twoColumnDetail(
            'Coste estimado',
            '$'.number_format($caracteres / 1000000 * self::USD_POR_MILLON, 2).' USD'
        );

        if ($this->option('simular')) {
            $this->components->warn('Simulación: no se ha llamado a la API ni se ha guardado nada.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($pendientes));
        $bar->start();

        $guardados = 0;

        // De cien en cien: si algo falla a mitad, lo ya traducido queda guardado
        // y la siguiente pasada arranca donde se quedó.
        foreach (array_chunk($pendientes, 100) as $tanda) {
            try {
                $traducidos = $traductor->translate(array_column($tanda, 'texto'), $idioma);
            } catch (\Throwable $e) {
                $bar->finish();
                $this->newLine(2);
                $this->error('La API falló: '.$e->getMessage());
                $this->components->warn("Se guardaron {$guardados} campos. Vuelva a ejecutar para continuar.");

                return self::FAILURE;
            }

            foreach ($tanda as $i => $campo) {
                Translation::updateOrCreate(
                    [
                        'translatable_type' => $campo['tipo'],
                        'translatable_id' => $campo['id'],
                        'locale' => $idioma,
                        'field' => $campo['campo'],
                    ],
                    [
                        'value' => $traducidos[$i],
                        'source_hash' => Translation::hashOf($campo['texto']),
                    ]
                );

                $guardados++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->components->info("{$guardados} campos traducidos al «{$idioma}».");

        return self::SUCCESS;
    }

    /**
     * Campos sin traducir, o cuya traducción se quedó vieja.
     *
     * @return list<array{tipo: class-string, id: int, campo: string, texto: string}>
     */
    private function pending(string $idioma): array
    {
        $modelo = $this->option('modelo');
        $clases = $modelo
            ? array_intersect_key(self::MODELOS, [$modelo => true])
            : self::MODELOS;

        if ($clases === []) {
            $this->error('Modelo desconocido. Son: '.implode(', ', array_keys(self::MODELOS)).'.');

            return [];
        }

        $limite = (int) $this->option('limite');
        $pendientes = [];

        foreach ($clases as $clase) {
            /** @var Model $instancia */
            $instancia = new $clase;

            $clase::with('translations')->chunkById(200, function ($filas) use (&$pendientes, $idioma, $limite): void {
                foreach ($filas as $fila) {
                    foreach ($fila->translatableFields() as $campo) {
                        if ($limite && count($pendientes) >= $limite) {
                            return;
                        }

                        $texto = (string) $fila->getAttribute($campo);

                        if (trim($texto) === '') {
                            continue;
                        }

                        $ya = $fila->translations->first(
                            fn (Translation $t): bool => $t->locale === $idioma && $t->field === $campo
                        );

                        // Al día: misma huella, misma traducción. No se repite.
                        if ($ya && $ya->source_hash === Translation::hashOf($texto)) {
                            continue;
                        }

                        $pendientes[] = [
                            'tipo' => $fila->getMorphClass(),
                            'id' => $fila->getKey(),
                            'campo' => $campo,
                            'texto' => $texto,
                        ];
                    }
                }
            });

            if ($limite && count($pendientes) >= $limite) {
                break;
            }
        }

        return $pendientes;
    }
}
