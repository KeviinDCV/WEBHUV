<?php

namespace App\Console\Commands;

use App\Models\ContentMedia;
use App\Support\ResponsiveImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Genera las miniaturas de las imágenes que ya estaban subidas.
 *
 * Las que se suban a partir de ahora las generan solas; esto es para las que ya
 * estaban, que en su mayoría vinieron de la importación del portal anterior a
 * su tamaño original. Se puede repetir cuantas veces haga falta.
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'huv:miniaturas
        {--rehacer : Vuelve a generarlas aunque ya existan}
        {--limite= : Solo las primeras N, útil para probar}';

    protected $description = 'Genera las miniaturas en WebP de las imágenes de los contenidos';

    public function handle(): int
    {
        $imagenes = ContentMedia::query()
            ->where('type', ContentMedia::TYPE_IMAGE)
            ->whereNotNull('path')
            ->orderBy('id')
            ->when($this->option('limite'), fn ($q) => $q->limit((int) $this->option('limite')))
            ->get();

        if ($imagenes->isEmpty()) {
            $this->components->warn('No hay imágenes que procesar.');

            return self::SUCCESS;
        }

        $almacen = Storage::disk('public');
        $barra = $this->output->createProgressBar($imagenes->count());
        $barra->start();

        $hechas = 0;
        $sinArchivo = 0;
        $ilegibles = 0;
        $yaPequenas = 0;
        $antes = 0;
        $despues = 0;

        foreach ($imagenes as $imagen) {
            $ruta = (string) $imagen->path;

            if (! $almacen->exists($ruta)) {
                $sinArchivo++;
                $barra->advance();

                continue;
            }

            if ($this->option('rehacer')) {
                ResponsiveImage::forget($ruta, 'public', ResponsiveImage::CARD_WIDTHS);
            }

            $anchos = ResponsiveImage::generate($ruta, 'public', ResponsiveImage::CARD_WIDTHS);

            if ($anchos === []) {
                // Sin derivadas puede ser por dos motivos muy distintos: que la
                // imagen no se pueda leer, o que ya sea más pequeña que la
                // miniatura y no haga falta reducirla. Lo segundo no es un
                // problema y no debe contarse como tal.
                $legible = @imagecreatefromstring($almacen->get($ruta)) !== false;

                $legible ? $yaPequenas++ : $ilegibles++;
                $barra->advance();

                continue;
            }

            $antes += $almacen->size($ruta);
            $despues += $almacen->size(ResponsiveImage::derivativePath($ruta, $anchos[0]));
            $hechas++;

            $barra->advance();
        }

        $barra->finish();
        $this->newLine(2);

        $this->components->twoColumnDetail('Imágenes con miniatura', (string) $hechas);

        if ($sinArchivo > 0) {
            $this->components->twoColumnDetail('<fg=yellow>Sin archivo en disco</>', (string) $sinArchivo);
        }

        if ($yaPequenas > 0) {
            $this->components->twoColumnDetail('Ya eran menores que la miniatura', (string) $yaPequenas);
        }

        if ($ilegibles > 0) {
            $this->components->twoColumnDetail('<fg=yellow>No se pudieron leer</>', (string) $ilegibles);
        }

        if ($antes > 0) {
            $this->newLine();
            $this->components->info(sprintf(
                'De %s a %s en un listado: un %d %% menos.',
                $this->mb($antes),
                $this->mb($despues),
                round(100 - $despues / $antes * 100)
            ));
        }

        return self::SUCCESS;
    }

    private function mb(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1, ',', '.').' MB'
            : number_format($bytes / 1024, 0, ',', '.').' KB';
    }
}
