<?php

namespace App\Services\Monitor;

use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use App\Models\MonitorDevice;
use App\Models\MonitorEvent;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitorPoller
{
    public function __construct(private readonly SensorChecks $checks, private readonly MonitorNotifier $notifier) {}

    /** Un passage du planificateur : contrôle les capteurs dont c'est l'heure. */
    public function poll(): int
    {
        $this->expirePausesAndAcknowledgements();

        $devices = MonitorDevice::query()->with(['group', 'sensors.device'])->get()->keyBy('id');
        $blocked = $this->blockedDevices($devices);
        $due = collect();

        foreach ($devices as $device) {
            foreach ($device->sensors as $sensor) {
                $sensor->setRelation('device', $device);
                if ($sensor->isPaused() || ($sensor->next_check_at && $sensor->next_check_at->isFuture())) {
                    continue;
                }
                // Dépendances : l'équipement parent est injoignable, ou le ping de
                // l'équipement l'est (les autres capteurs en dépendent).
                $reason = $blocked[$device->id] ?? null;
                if ($reason === null && $sensor->kind !== MonitorSensorKind::Ping) {
                    $ping = $device->sensors->firstWhere('kind', MonitorSensorKind::Ping);
                    if ($ping && ! $ping->paused && in_array($ping->status, [MonitorStatus::Down, MonitorStatus::DownAcknowledged], true)) {
                        $reason = 'le ping de l’équipement ne répond pas';
                    }
                }
                if ($reason !== null) {
                    $this->pauseByDependency($sensor, $reason);

                    continue;
                }
                $due->push($sensor);
            }
        }

        $this->check($due);
        $this->notifier->run();

        return $due->count();
    }

    /** « Vérifier maintenant » depuis l'interface. */
    public function checkNow(MonitorSensor $sensor): void
    {
        $sensor->loadMissing('device.group');
        $this->check(collect([$sensor]));
        $this->notifier->run();
    }

    /** @param  Collection<int, MonitorSensor>  $sensors */
    private function check(Collection $sensors): void
    {
        foreach ($sensors->groupBy(fn (MonitorSensor $sensor) => $sensor->kind->value) as $kind => $group) {
            foreach ($group->chunk(10) as $chunk) {
                $results = $this->checks->run(MonitorSensorKind::from($kind), $chunk);
                foreach ($chunk as $sensor) {
                    $this->record($sensor, ...$results[$sensor->id]);
                }
            }
        }
    }

    /** @return array<int, string> raison par équipement bloqué par son parent */
    private function blockedDevices(Collection $devices): array
    {
        $blocked = [];
        foreach ($devices as $device) {
            $current = $device;
            $seen = [];
            while ($current->parent_device_id && ! isset($seen[$current->id])) {
                $seen[$current->id] = true;
                $parent = $devices[$current->parent_device_id] ?? null;
                if (! $parent) {
                    break;
                }
                $ping = $parent->sensors->firstWhere('kind', MonitorSensorKind::Ping);
                if ($ping && in_array($ping->status, [MonitorStatus::Down, MonitorStatus::DownAcknowledged, MonitorStatus::Paused], true) && ! $parent->isPaused()) {
                    $blocked[$device->id] = "dépend de {$parent->name}, injoignable";
                    break;
                }
                $current = $parent;
            }
        }

        return $blocked;
    }

    private function pauseByDependency(MonitorSensor $sensor, string $reason): void
    {
        $message = 'En pause : '.$reason;
        if ($sensor->status === MonitorStatus::Paused && $sensor->last_message === $message) {
            $sensor->forceFill(['next_check_at' => now()->addSeconds($sensor->interval())])->save();

            return;
        }
        $this->changeStatus($sensor, MonitorStatus::Paused, ['last_message' => $message, 'failures' => 0, 'next_check_at' => now()->addSeconds($sensor->interval())]);
    }

    public function record(MonitorSensor $sensor, bool $ok, ?float $value, string $message, ?array $state = null): void
    {
        MonitorReading::create(['sensor_id' => $sensor->id, 'checked_at' => now(), 'ok' => $ok, 'value' => $value]);

        $failures = $ok ? 0 : $sensor->failures + 1;
        if (! $ok) {
            $status = $failures >= MonitorSensor::DOWN_AFTER ? MonitorStatus::Down : MonitorStatus::Warning;
            if ($status === MonitorStatus::Warning) {
                $message .= " (échec {$failures}/".MonitorSensor::DOWN_AFTER.', nouvel essai)';
            }
        } else {
            [$status, $limitMessage] = $this->applyLimits($sensor, $value);
            $message = $limitMessage ?? $message;
            if ($status === MonitorStatus::Up && $value !== null && $this->isUnusual($sensor, $value)) {
                $status = MonitorStatus::Unusual;
                $message .= ' (inhabituel pour ce jour et cette heure)';
            }
        }

        // Une panne prise en compte le reste tant qu'elle dure.
        if ($status === MonitorStatus::Down && $sensor->status === MonitorStatus::DownAcknowledged) {
            $status = MonitorStatus::DownAcknowledged;
        }

        // Après un premier échec, PRTG réessaie tout de suite : ici au passage suivant.
        $next = $status === MonitorStatus::Warning && ! $ok ? now() : now()->addSeconds($sensor->interval());
        $this->changeStatus($sensor, $status, [
            'last_value' => $value,
            'last_message' => mb_substr($message, 0, 255),
            'failures' => $failures,
            'checked_at' => now(),
            'next_check_at' => $next,
            'state' => $state ?? $sensor->state,
        ]);
    }

    /** @return array{0: MonitorStatus, 1: string|null} */
    private function applyLimits(MonitorSensor $sensor, ?float $value): array
    {
        if ($value === null) {
            return [MonitorStatus::Up, null];
        }
        $shown = $sensor->formatValue($value);
        foreach ([
            ['error_above', '>', MonitorStatus::Down, 'au-dessus de la limite de panne'],
            ['error_below', '<', MonitorStatus::Down, 'en dessous de la limite de panne'],
            ['warn_above', '>', MonitorStatus::Warning, 'au-dessus de la limite d’avertissement'],
            ['warn_below', '<', MonitorStatus::Warning, 'en dessous de la limite d’avertissement'],
        ] as [$field, $operator, $status, $text]) {
            $limit = $sensor->{$field};
            if ($limit !== null && ($operator === '>' ? $value > $limit : $value < $limit)) {
                return [$status, "{$shown} : {$text} ({$sensor->formatValue($limit)})"];
            }
        }

        return [MonitorStatus::Up, null];
    }

    /**
     * Comme l'état « Inhabituel » de PRTG : un temps de réponse plus de trois
     * fois supérieur à la moyenne des 4 dernières semaines, même jour, même heure.
     */
    private function isUnusual(MonitorSensor $sensor, float $value): bool
    {
        if ($sensor->unitLabel() !== 'ms') {
            return false;
        }
        $history = DB::table('monitor_hourly')
            ->where('sensor_id', $sensor->id)
            ->where('hour', '>=', now()->subDays(28))
            ->whereNotNull('avg_value')
            ->get(['hour', 'avg_value'])
            ->filter(fn ($row) => ($hour = \Illuminate\Support\Carbon::parse($row->hour))->dayOfWeek === now()->dayOfWeek && $hour->hour === now()->hour);
        if ($history->count() < 3) {
            return false;
        }
        $average = $history->avg('avg_value');

        return $value > 3 * $average && $value - $average >= 50;
    }

    private function changeStatus(MonitorSensor $sensor, MonitorStatus $status, array $changes): void
    {
        $previous = $sensor->status;
        $changes['status'] = $status;
        if ($status !== $previous) {
            $changes['status_changed_at'] = now();
            if (! in_array($status, [MonitorStatus::Down, MonitorStatus::DownAcknowledged], true)) {
                $changes += ['acknowledged_by' => null, 'acknowledged_at' => null, 'acknowledged_until' => null, 'acknowledgement' => null];
            }
        }
        $sensor->forceFill($changes)->save();

        if ($status !== $previous) {
            MonitorEvent::create(['sensor_id' => $sensor->id, 'from_status' => $previous, 'to_status' => $status, 'message' => $sensor->last_message]);
            $this->notifier->statusChanged($sensor, $previous);
        }
    }

    private function expirePausesAndAcknowledgements(): void
    {
        foreach ([MonitorDevice::class, MonitorSensor::class] as $model) {
            $model::query()->where('paused', true)->whereNotNull('paused_until')->where('paused_until', '<=', now())
                ->update(['paused' => false, 'paused_until' => null, 'pause_reason' => null]);
        }

        MonitorSensor::query()->where('status', MonitorStatus::DownAcknowledged)
            ->whereNotNull('acknowledged_until')->where('acknowledged_until', '<=', now())
            ->get()->each(function (MonitorSensor $sensor) {
                $sensor->forceFill(['status' => MonitorStatus::Down, 'acknowledged_until' => null, 'notified_at' => null])->save();
                MonitorEvent::create(['sensor_id' => $sensor->id, 'from_status' => MonitorStatus::DownAcknowledged, 'to_status' => MonitorStatus::Down, 'message' => 'Fin de la prise en compte : toujours en panne']);
            });
    }
}
