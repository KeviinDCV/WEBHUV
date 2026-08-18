<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendario de la agenda institucional.
 *
 * Resuelve en el servidor el periodo visible —una semana o un mes— y reparte
 * los eventos por día. La navegación viaja en la URL (?vista=&periodo=), de
 * modo que el calendario funciona sin JavaScript y cada semana o mes tiene su
 * propia dirección que se puede compartir o enlazar.
 *
 * Los eventos son elementos de un tema, no una tabla aparte: en el portal la
 * agenda ES el tema «Calendario de actividades», y sus ciento cuarenta y un
 * eventos se editan con el mismo formulario que cualquier otro contenido. Aquí
 * se hacía con una tabla propia, y eso dejaba dos agendas que no se veían entre
 * sí y dos formularios distintos para lo mismo.
 */
class EventCalendar
{
    /** Límite de navegación: un año hacia cada lado. */
    private const MAX_OFFSET = 52;

    /**
     * @param  iterable<\App\Models\TopicItem>  $events
     */
    private function __construct(
        public readonly string $view,
        public readonly int $offset,
        private readonly iterable $events,
    ) {}

    /**
     * Los parámetros llegan de la URL: pueden ser cualquier cosa, incluso un
     * array si alguien escribe ?vista[]=x. Se normalizan a un valor seguro en
     * lugar de confiar en el tipado.
     *
     * @param  iterable<\App\Models\TopicItem>  $events
     */
    public static function make(iterable $events, mixed $view = null, mixed $offset = 0): self
    {
        $view = is_string($view) && in_array($view, ['semana', 'mes'], true) ? $view : 'semana';
        $offset = is_numeric($offset) ? (int) $offset : 0;

        return new self(
            $view,
            max(-self::MAX_OFFSET, min(self::MAX_OFFSET, $offset)),
            $events,
        );
    }

    public function isWeekly(): bool
    {
        return $this->view === 'semana';
    }

    /** Primer día de la rejilla (siempre un lunes). */
    public function gridStart(): Carbon
    {
        return $this->isWeekly()
            ? Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->offset)
            : $this->monthStart()->startOfWeek(Carbon::MONDAY);
    }

    /** Último día de la rejilla (siempre un domingo). */
    public function gridEnd(): Carbon
    {
        return $this->isWeekly()
            ? $this->gridStart()->endOfWeek(Carbon::SUNDAY)
            : $this->monthStart()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
    }

    private function monthStart(): Carbon
    {
        return Carbon::now()->startOfMonth()->addMonths($this->offset);
    }

    /** Rótulo del periodo: «03 agosto – 09 agosto» o «agosto». */
    public function label(): string
    {
        if (! $this->isWeekly()) {
            return $this->monthStart()->translatedFormat('F');
        }

        return $this->gridStart()->translatedFormat('d F')
            .' – '
            .$this->gridEnd()->translatedFormat('d F');
    }

    public function year(): string
    {
        return $this->isWeekly()
            ? $this->gridStart()->format('Y')
            : $this->monthStart()->format('Y');
    }

    /**
     * Días del periodo con sus eventos.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function days(): Collection
    {
        $start = $this->gridStart();
        $end = $this->gridEnd();
        $monthStart = $this->isWeekly() ? null : $this->monthStart();

        return collect(range(0, $start->diffInDays($end)))
            ->map(function (int $dayOffset) use ($start, $monthStart): array {
                $date = $start->copy()->addDays($dayOffset);

                return [
                    'date' => $date,
                    'today' => $date->isToday(),
                    'outside' => $monthStart !== null && ! $date->isSameMonth($monthStart),
                    'events' => $this->eventsOn($date),
                ];
            });
    }

    /**
     * Eventos que ocupan ese día.
     *
     * Un evento de varios días aparece en todos ellos, no solo en el primero.
     *
     * @return list<\App\Models\TopicItem>
     */
    private function eventsOn(Carbon $date): array
    {
        return collect($this->events)
            ->filter(fn ($event): bool => $event->startsAt() !== null && $date->betweenIncluded(
                $event->startsAt()->copy()->startOfDay(),
                $event->endsAt()->copy()->endOfDay(),
            ))
            ->sortBy(fn ($event) => $event->startsAt())
            ->values()
            ->all();
    }

    public function isEmpty(): bool
    {
        return $this->days()->every(fn (array $day): bool => $day['events'] === []);
    }

    /** Parámetros de consulta para saltar un periodo. */
    public function queryFor(int $step): array
    {
        return ['vista' => $this->view, 'periodo' => $this->offset + $step];
    }
}
