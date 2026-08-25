<?php

namespace App\Console\Commands;

use App\Models\Visit;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Borra las visitas viejas.
 *
 * La tabla crece una fila por página vista y no deja de crecer sola. Con año y
 * medio guardado se puede comparar un mes con el mismo mes del año anterior,
 * que es la comparación que de verdad se hace en un portal público; más atrás
 * no lo mira nadie y solo ocupa.
 *
 * Conviene dejarlo programado una vez al mes:
 *     php artisan huv:visitas-purgar
 */
class PruneVisits extends Command
{
    protected $signature = 'huv:visitas-purgar
        {--dias=550 : Cuántos días de historia se conservan}';

    protected $description = 'Borra del recuento de visitas lo más antiguo';

    public function handle(): int
    {
        $dias = max(30, (int) $this->option('dias'));
        $corte = Carbon::today()->subDays($dias);

        $cuantas = Visit::query()->where('visited_on', '<', $corte->toDateString())->count();

        if ($cuantas === 0) {
            $this->components->info('No hay nada más viejo que el '.$corte->toDateString().'.');

            return self::SUCCESS;
        }

        // Por tandas: un DELETE de varios millones de filas bloquea la tabla, y
        // esa tabla se escribe en cada visita.
        $borradas = 0;

        do {
            $tanda = Visit::query()
                ->where('visited_on', '<', $corte->toDateString())
                ->limit(5000)
                ->delete();

            $borradas += $tanda;
        } while ($tanda > 0);

        $this->components->info(sprintf(
            'Borradas %s visitas anteriores al %s.',
            number_format($borradas, 0, ',', '.'),
            $corte->toDateString()
        ));

        return self::SUCCESS;
    }
}
