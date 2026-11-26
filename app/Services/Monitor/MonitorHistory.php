<?php

namespace App\Services\Monitor;

use App\Models\MonitorEvent;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonitorHistory
{
    /** Mesures détaillées gardées 7 jours, moyennes horaires et événements 13 mois. */
    public const RAW_DAYS = 7;

    public const HOURLY_DAYS = 400;

    /** @var array<string, array{label: string, hours: int, bucket: int}> */
    public const PERIODS = [
        '24h' => ['label' => '24 heures', 'hours' => 24, 'bucket' => 5],
        '7j' => ['label' => '7 jours', 'hours' => 168, 'bucket' => 60],
        '30j' => ['label' => '30 jours', 'hours' => 720, 'bucket' => 360],
    ];

    /** Consolide les heures terminées depuis la dernière consolidation, puis purge. */
    public function rollup(): int
    {
        $last = DB::table('monitor_hourly')->max('hour');
        $hour = $last ? Carbon::parse($last)->addHour() : now()->subHours(48)->startOfHour();
        $hour = $hour->max(now()->subDays(self::RAW_DAYS)->startOfHour());
        $current = now()->startOfHour();
        $done = 0;

        while ($hour->lt($current)) {
            $rows = MonitorReading::query()
                ->where('checked_at', '>=', $hour)
                ->where('checked_at', '<', $hour->copy()->addHour())
                ->groupBy('sensor_id')
                ->selectRaw('sensor_id, count(*) as samples, sum(case when ok then 0 else 1 end) as failures, avg(ms) as avg_ms, max(ms) as max_ms')
                ->get()
                ->map(fn ($row) => [
                    'sensor_id' => $row->sensor_id,
                    'hour' => $hour->copy(),
                    'samples' => (int) $row->samples,
                    'failures' => (int) $row->failures,
                    'avg_ms' => $row->avg_ms === null ? null : (int) round($row->avg_ms),
                    'max_ms' => $row->max_ms === null ? null : (int) $row->max_ms,
                ])
                ->all();
            if ($rows !== []) {
                DB::table('monitor_hourly')->upsert($rows, ['sensor_id', 'hour'], ['samples', 'failures', 'avg_ms', 'max_ms']);
            }
            $hour->addHour();
            $done++;
        }

        MonitorReading::query()->where('checked_at', '<', now()->subDays(self::RAW_DAYS))->delete();
        DB::table('monitor_hourly')->where('hour', '<', now()->subDays(self::HOURLY_DAYS))->delete();
        MonitorEvent::query()->where('created_at', '<', now()->subDays(self::HOURLY_DAYS))->delete();

        return $done;
    }

    /**
     * Points du graphique (temps de réponse moyen et échecs par tranche) et
     * statistiques de la période.
     *
     * @return array{points: list<array{at: Carbon, ms: int|null, failed: bool}>, uptime: float|null, avg_ms: int|null, max_ms: int|null, samples: int}
     */
    public function series(MonitorSensor $sensor, string $period): array
    {
        ['hours' => $hours, 'bucket' => $bucket] = self::PERIODS[$period];
        $from = now()->subHours($hours);

        // Les 24 dernières heures viennent des mesures détaillées, au-delà des moyennes horaires.
        $rows = $hours <= 24
            ? MonitorReading::query()->where('sensor_id', $sensor->id)->where('checked_at', '>=', $from)->orderBy('checked_at')
                ->get()->map(fn (MonitorReading $r) => ['at' => $r->checked_at, 'samples' => 1, 'failures' => $r->ok ? 0 : 1, 'avg' => $r->ms, 'max' => $r->ms])
            : DB::table('monitor_hourly')->where('sensor_id', $sensor->id)->where('hour', '>=', $from)->orderBy('hour')
                ->get()->map(fn ($r) => ['at' => Carbon::parse($r->hour), 'samples' => $r->samples, 'failures' => $r->failures, 'avg' => $r->avg_ms, 'max' => $r->max_ms]);

        $points = $rows
            ->groupBy(fn (array $row) => intdiv($row['at']->timestamp, $bucket * 60))
            ->map(function ($group, $slot) use ($bucket) {
                $timed = $group->filter(fn (array $row) => $row['avg'] !== null);
                $weight = $timed->sum('samples');

                return [
                    'at' => Carbon::createFromTimestamp($slot * $bucket * 60, config('app.timezone')),
                    'ms' => $weight > 0 ? (int) round($timed->sum(fn (array $row) => $row['avg'] * $row['samples']) / $weight) : null,
                    'failed' => $group->sum('failures') > 0,
                ];
            })
            ->values()
            ->all();

        $samples = (int) $rows->sum('samples');
        $timed = $rows->filter(fn (array $row) => $row['avg'] !== null);
        $weight = $timed->sum('samples');

        return [
            'points' => $points,
            'samples' => $samples,
            'uptime' => $samples > 0 ? round(100 * ($samples - $rows->sum('failures')) / $samples, 2) : null,
            'avg_ms' => $weight > 0 ? (int) round($timed->sum(fn (array $row) => $row['avg'] * $row['samples']) / $weight) : null,
            'max_ms' => $timed->max('max'),
        ];
    }
}
