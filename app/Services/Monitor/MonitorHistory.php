<?php

namespace App\Services\Monitor;

use App\Enums\MonitorStatus;
use App\Models\MonitorEvent;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitorHistory
{
    /** Périodes des graphiques, comme les onglets de PRTG (bucket en minutes). */
    public const PERIODS = [
        'direct' => ['label' => 'En direct (1 h)', 'hours' => 1, 'bucket' => 1],
        '2j' => ['label' => '2 jours', 'hours' => 48, 'bucket' => 15],
        '30j' => ['label' => '30 jours', 'hours' => 720, 'bucket' => 240],
        '365j' => ['label' => '365 jours', 'hours' => 8760, 'bucket' => 1440],
    ];

    /** Consolide les heures terminées par moyennes horaires, puis purge. */
    public function rollup(): int
    {
        $rawDays = (int) config('monitor.raw_days', 7);
        $last = DB::table('monitor_hourly')->max('hour');
        $hour = ($last ? Carbon::parse($last)->addHour() : now()->subHours(48)->startOfHour())
            ->max(now()->subDays($rawDays)->startOfHour());
        $current = now()->startOfHour();
        $done = 0;

        while ($hour->lt($current)) {
            $rows = MonitorReading::query()
                ->where('checked_at', '>=', $hour)
                ->where('checked_at', '<', $hour->copy()->addHour())
                ->groupBy('sensor_id')
                ->selectRaw('sensor_id, count(*) as samples, sum(case when ok then 0 else 1 end) as failures, avg(value) as avg_value, min(value) as min_value, max(value) as max_value')
                ->get()
                ->map(fn ($row) => [
                    'sensor_id' => $row->sensor_id,
                    'hour' => $hour->copy(),
                    'samples' => (int) $row->samples,
                    'failures' => (int) $row->failures,
                    'avg_value' => $row->avg_value === null ? null : (float) $row->avg_value,
                    'min_value' => $row->min_value === null ? null : (float) $row->min_value,
                    'max_value' => $row->max_value === null ? null : (float) $row->max_value,
                ])
                ->all();
            if ($rows !== []) {
                DB::table('monitor_hourly')->upsert($rows, ['sensor_id', 'hour'], ['samples', 'failures', 'avg_value', 'min_value', 'max_value']);
            }
            $hour->addHour();
            $done++;
        }

        MonitorReading::query()->where('checked_at', '<', now()->subDays($rawDays))->delete();
        DB::table('monitor_hourly')->where('hour', '<', now()->subDays((int) config('monitor.hourly_days', 400)))->delete();
        MonitorEvent::query()->where('created_at', '<', now()->subDays((int) config('monitor.hourly_days', 400)))->delete();

        return $done;
    }

    /**
     * Mesures entre deux dates : détaillées si elles sont encore gardées, sinon moyennes horaires.
     *
     * @return Collection<int, array{at: Carbon, samples: int, failures: int, avg: float|null, max: float|null}>
     */
    public function rows(MonitorSensor $sensor, Carbon $from, Carbon $to): Collection
    {
        $raw = fn (Carbon $start) => MonitorReading::query()->where('sensor_id', $sensor->id)->whereBetween('checked_at', [$start, $to])->orderBy('checked_at')->get()
            ->map(fn (MonitorReading $r) => ['at' => $r->checked_at, 'samples' => 1, 'failures' => $r->ok ? 0 : 1, 'avg' => $r->value, 'max' => $r->value]);

        if ($from->gte(now()->subDays((int) config('monitor.raw_days', 7))->addHour())) {
            return $raw($from);
        }

        // Au-delà : moyennes horaires, complétées par les mesures pas encore consolidées.
        $hourly = DB::table('monitor_hourly')->where('sensor_id', $sensor->id)->whereBetween('hour', [$from, $to])->orderBy('hour')->get()
            ->map(fn ($r) => ['at' => Carbon::parse($r->hour), 'samples' => (int) $r->samples, 'failures' => (int) $r->failures, 'avg' => $r->avg_value === null ? null : (float) $r->avg_value, 'max' => $r->max_value === null ? null : (float) $r->max_value]);
        $consolidatedUntil = $hourly->isEmpty() ? $from : $hourly->last()['at']->copy()->addHour();

        return $hourly->concat($raw($consolidatedUntil->max($from)))->values();
    }

    /**
     * Points du graphique et statistiques de la période.
     *
     * @return array{points: list<array{at: Carbon, value: float|null, failed: bool}>, uptime: float|null, avg: float|null, max: float|null, samples: int}
     */
    public function series(MonitorSensor $sensor, string $period): array
    {
        ['hours' => $hours, 'bucket' => $bucket] = self::PERIODS[$period];
        $rows = $this->rows($sensor, now()->subHours($hours), now());

        $points = $rows
            ->groupBy(fn (array $row) => intdiv($row['at']->timestamp, $bucket * 60))
            ->map(fn (Collection $group, $slot) => [
                'at' => Carbon::createFromTimestamp($slot * $bucket * 60, config('app.timezone')),
                'value' => self::weightedAverage($group),
                'failed' => $group->sum('failures') > 0,
            ])
            ->values()
            ->all();

        return ['points' => $points] + $this->stats($rows);
    }

    /** @return array{uptime: float|null, avg: float|null, max: float|null, samples: int} */
    public function stats(Collection $rows): array
    {
        $samples = (int) $rows->sum('samples');

        return [
            'samples' => $samples,
            'uptime' => $samples > 0 ? round(100 * ($samples - $rows->sum('failures')) / $samples, 2) : null,
            'avg' => self::weightedAverage($rows),
            'max' => $rows->max('max'),
        ];
    }

    /**
     * Rapport de disponibilité d'une liste de capteurs sur une période.
     *
     * @param  Collection<int, MonitorSensor>  $sensors
     * @return list<array{sensor: MonitorSensor, uptime: float|null, avg: float|null, max: float|null, samples: int, outages: int, downtime_minutes: int}>
     */
    public function report(Collection $sensors, Carbon $from, Carbon $to): array
    {
        return $sensors->map(function (MonitorSensor $sensor) use ($from, $to) {
            $events = MonitorEvent::query()->where('sensor_id', $sensor->id)->where('created_at', '<=', $to)->orderBy('created_at')->get();
            $down = [MonitorStatus::Down, MonitorStatus::DownAcknowledged];
            $outages = 0;
            $downtime = 0;
            $downSince = null;
            foreach ($events as $event) {
                $isDown = in_array($event->to_status, $down, true);
                $wasDown = in_array($event->from_status, $down, true);
                if ($isDown && ! $wasDown) {
                    $downSince = $event->created_at;
                    if ($event->created_at->gte($from)) {
                        $outages++;
                    }
                } elseif (! $isDown && $wasDown && $downSince) {
                    $downtime += max(0, $downSince->max($from)->diffInMinutes($event->created_at->min($to), true));
                    $downSince = null;
                }
            }
            if ($downSince) {
                $downtime += max(0, $downSince->max($from)->diffInMinutes($to->copy()->min(now()), true));
            }

            return ['sensor' => $sensor, 'outages' => $outages, 'downtime_minutes' => (int) round($downtime)] + $this->stats($this->rows($sensor, $from, $to));
        })->all();
    }

    private static function weightedAverage(Collection $rows): ?float
    {
        $timed = $rows->filter(fn (array $row) => $row['avg'] !== null);
        $weight = $timed->sum('samples');

        return $weight > 0 ? round($timed->sum(fn (array $row) => $row['avg'] * $row['samples']) / $weight, 1) : null;
    }
}
