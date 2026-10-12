<?php

namespace App\Services\Intranet;

use App\Models\CalendarEvent;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Agrège tout ce qui apparaît dans le calendrier intranet :
 *
 *  - les événements saisis (table calendar_events) ;
 *  - les anniversaires, calculés depuis users.birth_date ;
 *  - les arrivées, calculées depuis users.hire_date.
 *
 * Anniversaires et arrivées ne sont volontairement pas stockés : une ligne par
 * année et par salarié serait périmée dès qu'une date change sur la fiche.
 *
 * Confidentialité : un anniversaire n'expose que le jour et le mois, jamais
 * l'année de naissance ni l'âge.
 *
 * Le format de sortie est celui qu'attend FullCalendar (Event Object).
 */
class CalendarFeed
{
    /**
     * Événements sur [debut, fin[ (la fin est exclusive, comme dans FullCalendar).
     *
     * @return list<array<string, mixed>>
     */
    public function between(CarbonInterface $start, CarbonInterface $end): array
    {
        $from = Carbon::instance($start)->startOfDay();
        $to = Carbon::instance($end)->startOfDay();

        $events = [
            ...$this->storedEvents($from, $to),
            ...$this->birthdays($from, $to),
            ...$this->arrivals($from, $to),
            ...$this->timeOff($from, $to),
        ];

        usort($events, fn (array $a, array $b) => strcmp($a['start'], $b['start']));

        return $events;
    }

    /**
     * Les prochains événements à partir d'aujourd'hui.
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(int $limit = 4, int $days = 60): array
    {
        $today = Carbon::today();

        return array_slice($this->between($today, $today->copy()->addDays($days)), 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function storedEvents(Carbon $from, Carbon $to): array
    {
        return CalendarEvent::with('department')
            ->where('event_date', '>=', $from->toDateString())
            ->where('event_date', '<', $to->toDateString())
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->map(function (CalendarEvent $event) {
                $time = $event->all_day || ! $event->event_time
                    ? null
                    : substr((string) $event->event_time, 0, 5);

                return $this->format(
                    id: 'event-'.$event->id,
                    type: $event->type,
                    title: $event->title,
                    date: $event->event_date,
                    time: $time,
                    description: $event->description,
                    department: $event->department?->name,
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function birthdays(Carbon $from, Carbon $to): array
    {
        $events = [];

        $users = User::where('actif', true)->whereNotNull('birth_date')->with('departments')->get();

        foreach ($users as $user) {
            // La plage peut chevaucher deux années (vue de décembre à janvier).
            for ($year = $from->year; $year <= $to->year; $year++) {
                $month = $user->birth_date->month;
                // Un 29 février tombe le 28 les années non bissextiles.
                $day = min($user->birth_date->day, Carbon::create($year, $month, 1)->daysInMonth);
                $date = Carbon::create($year, $month, $day)->startOfDay();

                if ($date->lt($from) || $date->gte($to)) {
                    continue;
                }

                $events[] = $this->format(
                    id: 'birthday-'.$user->id.'-'.$year,
                    type: 'birthday',
                    title: 'Anniversaire de '.$user->name,
                    date: $date,
                    time: null,
                    description: null,
                    department: $user->departments->pluck('name')->implode(', ') ?: null,
                );
            }
        }

        return $events;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function arrivals(Carbon $from, Carbon $to): array
    {
        return User::where('actif', true)
            ->whereNotNull('hire_date')
            ->where('hire_date', '>=', $from->toDateString())
            ->where('hire_date', '<', $to->toDateString())
            ->with('departments')
            ->get()
            ->map(fn (User $user) => $this->format(
                id: 'arrival-'.$user->id,
                type: 'arrival',
                title: 'Arrivée de '.$user->name,
                date: $user->hire_date,
                time: null,
                description: $user->job_title,
                department: $user->departments->pluck('name')->implode(', ') ?: null,
            ))
            ->all();
    }

    /**
     * Congés approuvés qui chevauchent la plage. Un événement multi-jours par
     * demande. Le motif reste privé : on n'affiche que le nom de l'absent.
     *
     * @return list<array<string, mixed>>
     */
    private function timeOff(Carbon $from, Carbon $to): array
    {
        return TimeOffRequest::with('user.departments')
            ->where('status', 'approved')
            // Chevauchement : la demande commence avant la fin de la plage ET
            // finit à partir du début de la plage.
            ->whereDate('start_date', '<', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get()
            ->filter(fn (TimeOffRequest $r) => $r->user !== null)
            ->map(fn (TimeOffRequest $r) => $this->format(
                id: 'timeoff-'.$r->id,
                type: 'time_off',
                title: 'Absence · '.$r->user->name,
                date: $r->start_date,
                time: null,
                description: null,
                department: $r->user->departments->pluck('name')->implode(', ') ?: null,
                endDate: $r->end_date,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(
        string $id,
        ?string $type,
        string $title,
        CarbonInterface $date,
        ?string $time,
        ?string $description,
        ?string $department,
        ?CarbonInterface $endDate = null,
    ): array {
        $types = config('intranet.calendar.event_types', []);
        // Un type inconnu (ancienne donnée) retombe sur « Événement ».
        $config = $types[$type] ?? $types['event'] ?? ['name' => 'Événement', 'color' => '#1A1A1A', 'text' => '#FFFFFF'];

        $event = [
            'id' => $id,
            'title' => $title,
            'start' => $time ? $date->toDateString().'T'.$time.':00' : $date->toDateString(),
            'allDay' => $time === null,
            'backgroundColor' => $config['color'],
            'borderColor' => $config['color'],
            'textColor' => $config['text'] ?? '#FFFFFF',
            'extendedProps' => [
                'type' => $type,
                'typeLabel' => $config['name'],
                'color' => $config['color'],
                'textColor' => $config['text'] ?? '#FFFFFF',
                'date' => $date->toDateString(),
                'time' => $time,
                'description' => $description,
                'department' => $department,
            ],
        ];

        // Événement multi-jours : FullCalendar attend une fin EXCLUSIVE, d'où le +1 jour.
        if ($endDate !== null) {
            $event['end'] = Carbon::instance($endDate)->copy()->addDay()->toDateString();
            $event['extendedProps']['endDate'] = Carbon::instance($endDate)->toDateString();
        }

        return $event;
    }
}
