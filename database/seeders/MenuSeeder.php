<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Vuelca el menú de la configuración a la base para poder editarlo.
 *
 * Se puede ejecutar cuantas veces haga falta: si un área ya tiene filas, se
 * deja como está. Eso no es prudencia de más —es lo que impide que una
 * reinstalación se lleve por delante el menú que el hospital haya organizado—,
 * y por eso no hay opción de forzarlo: para volver al de fábrica se vacía la
 * tabla a mano y se vuelve a sembrar, que es una decisión que hay que tomar
 * mirando, no de pasada.
 *
 *     php artisan db:seed --class=MenuSeeder
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            MenuItem::AREA_BAR => (array) config('huv.nav'),
            MenuItem::AREA_MEGA => (array) config('huv.mega_menu'),
        ] as $area => $entradas) {
            if (MenuItem::query()->where('area', $area)->exists()) {
                $this->aviso($area, 'ya estaba, no se toca');

                continue;
            }

            $this->cargar($area, $entradas);

            $this->aviso($area, MenuItem::query()->where('area', $area)->count().' entradas');
        }

        MenuItem::forget();
    }

    private function aviso(string $area, string $texto): void
    {
        $this->command?->line('  Menú «'.$area.'»: '.$texto);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entradas
     */
    private function cargar(string $area, array $entradas, ?int $padre = null): void
    {
        foreach ($entradas as $posicion => $entrada) {
            // Los grupos del menú completo llaman 'title' a su rótulo y 'links'
            // a sus hijos; el resto, 'label' y 'children'.
            $hijos = $entrada['children'] ?? $entrada['links'] ?? [];

            $item = MenuItem::create([
                'parent_id' => $padre,
                'area' => $area,
                'key' => $entrada['key'] ?? null,
                'label' => $entrada['label'] ?? $entrada['title'] ?? '',
                'i18n' => $entrada['i18n'] ?? null,
                'path' => $entrada['path'] ?? null,
                'url' => $entrada['url'] ?? null,
                'columns' => $entrada['columns'] ?? null,
                'narrow' => (bool) ($entrada['narrow'] ?? false),
                'position' => $posicion + 1,
            ]);

            if ($hijos !== []) {
                $this->cargar($area, $hijos, $item->id);
            }
        }
    }
}
