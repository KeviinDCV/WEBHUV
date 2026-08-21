<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Support\ResponsiveImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Genera las versiones reducidas de los banners que ya estaban subidos.
 *
 * Los que se suban a partir de ahora las generan solos al guardarse; esto es
 * para los que ya estaban. Se puede volver a ejecutar cuantas veces haga falta:
 * rehace las derivadas desde el original, que nunca se toca.
 */
class GenerateBannerDerivatives extends Command
{
    protected $signature = 'huv:derivadas {--rehacer : Vuelve a generarlas aunque ya existan}';

    protected $description = 'Genera las versiones reducidas en WebP de los banners';

    public function handle(): int
    {
        $banners = Banner::whereNotNull('image_path')->orderBy('position')->get();

        if ($banners->isEmpty()) {
            $this->components->warn('No hay banners con imagen.');

            return self::SUCCESS;
        }

        $almacen = Storage::disk('public');
        $antes = 0;
        $despues = 0;

        foreach ($banners as $banner) {
            $ruta = (string) $banner->image_path;

            if (! $almacen->exists($ruta)) {
                $this->components->warn($ruta.': el archivo no está en disco.');

                continue;
            }

            if ($this->option('rehacer')) {
                ResponsiveImage::forget($ruta);
            }

            $original = $almacen->size($ruta);
            $anchos = ResponsiveImage::generate($ruta);

            if ($anchos === []) {
                $this->components->warn(basename($ruta).': no se pudo leer como imagen.');

                continue;
            }

            // La primera derivada es la que de verdad se descarga en un móvil,
            // que es donde duele el peso.
            $menor = $almacen->size(ResponsiveImage::derivativePath($ruta, $anchos[0]));

            $antes += $original;
            $despues += $menor;

            $this->components->twoColumnDetail(
                basename($ruta).'  <fg=gray>'.implode(', ', $anchos).'</>',
                $this->kb($original).' → '.$this->kb($menor)
            );
        }

        if ($antes > 0) {
            $this->newLine();
            $this->components->info(sprintf(
                'De %s a %s: un %d %% menos en la primera descarga.',
                $this->kb($antes),
                $this->kb($despues),
                round(100 - $despues / $antes * 100)
            ));
        }

        return self::SUCCESS;
    }

    private function kb(int $bytes): string
    {
        return number_format($bytes / 1024, 0, ',', '.').' KB';
    }
}
