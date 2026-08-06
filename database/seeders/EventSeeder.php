<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Seeder;

/**
 * Agenda inicial: traslada a la base de datos los eventos que estaban en
 * config/huv.php. Idempotente: identifica cada evento por su título.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['2025', 'Educación', 'Participación Social en Salud'])
            ->mapWithKeys(fn (string $name): array => [
                $name => EventCategory::firstOrCreate(['name' => $name])->id,
            ]);

        foreach ($this->events() as $event) {
            $model = Event::firstOrCreate(
                ['title' => $event['title']],
                collect($event)->except('categories')->all()
            );

            if ($model->wasRecentlyCreated) {
                $model->categories()->sync(
                    collect($event['categories'])->map(fn (string $name) => $categories[$name])->all()
                );
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(): array
    {
        return [
            [
                'title' => 'II Congreso Internacional de Neurociencias',
                'starts_at' => '2026-08-05 07:00',
                'ends_at' => '2026-08-05 18:00',
                'place' => 'Arena USC, Universidad Santiago de Cali',
                'categories' => ['Educación'],
            ],
            [
                'title' => 'Jornada de donación de sangre',
                'starts_at' => '2026-08-07 08:00',
                'ends_at' => '2026-08-07 16:00',
                'place' => 'Banco de Sangre — sede principal',
                'categories' => ['Participación Social en Salud'],
            ],
            [
                'title' => 'Comité de ética en investigación',
                'starts_at' => '2026-08-12 14:00',
                'ends_at' => '2026-08-12 17:00',
                'place' => 'Sala de juntas, tercer piso',
                'categories' => ['Educación'],
            ],
        ];
    }
}
