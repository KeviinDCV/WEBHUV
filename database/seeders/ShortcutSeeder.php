<?php

namespace Database\Seeders;

use App\Models\Shortcut;
use App\Models\ShortcutBlock;
use Illuminate\Database\Seeder;

/**
 * Barras de accesos directos de la portada.
 *
 * Las rutas que empiezan por «/» se sirven del portal actual hasta que la
 * sección exista aquí (ver huv.legacy_base). Idempotente: se identifica cada
 * barra por su nombre.
 */
class ShortcutSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->blocks() as $position => $block) {
            $model = ShortcutBlock::firstOrCreate(
                ['name' => $block['name']],
                ['theme' => $block['theme'], 'position' => $position + 1]
            );

            if ($model->shortcuts()->exists()) {
                continue;
            }

            foreach ($block['shortcuts'] as $index => $shortcut) {
                $model->shortcuts()->create($shortcut + ['position' => $index + 1]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(): array
    {
        return [
            [
                'name' => 'Barra huv',
                'theme' => 'navy',
                'shortcuts' => [
                    ['label' => 'Citas', 'icon' => 'calendar-check', 'url' => 'https://citas.huv.gov.co/login'],
                    ['label' => 'Of. Coord. Académica', 'icon' => 'graduation', 'url' => 'https://edu.huv.gov.co'],
                    ['label' => 'Oficina Internacional', 'icon' => 'map-pin', 'url' => 'https://internacional.huv.gov.co'],
                    ['label' => 'Ver Resultados Lab', 'icon' => 'lab', 'url' => 'https://laboratorio.huv.gov.co/ConsultaWebPacientes/'],
                    ['label' => 'Pagos PSE', 'icon' => 'payment', 'url' => '/tema/pagos-en-linea'],
                ],
            ],
            [
                'name' => 'Atención al Ciudadano',
                'theme' => 'navy',
                'shortcuts' => [
                    ['label' => 'PQRSF', 'icon' => 'inbox', 'url' => 'https://acortar.link/OUtyCS'],
                    // Rutas recortadas en la captura del portal actual: conviene
                    // confirmarlas desde su administrador.
                    ['label' => 'Rendición de cuentas', 'icon' => 'chart', 'url' => '/tema/rendicion-de-cuentas'],
                    ['label' => 'CIAU', 'icon' => 'info', 'url' => '/tema/ciau'],
                    ['label' => 'Notif. Judiciales', 'icon' => 'gavel', 'url' => '/tema/notificaciones-judiciales'],
                    ['label' => 'Plan Anticorrupción', 'icon' => 'megaphone', 'url' => '/planes/plan-anticorrupcion'],
                ],
            ],
        ];
    }
}
