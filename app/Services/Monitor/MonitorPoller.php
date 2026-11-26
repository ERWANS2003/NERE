<?php

namespace App\Services\Monitor;

use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use App\Models\Department;
use App\Models\MonitorEvent;
use App\Models\MonitorReading;
use App\Models\MonitorSensor;
use App\Models\User;
use App\Notifications\MonitorAlert;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Process\Pool as ProcessPool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;

class MonitorPoller
{
    /** Vérifie chaque capteur actif au plus une fois par minute. */
    public function poll(): int
    {
        $sensors = MonitorSensor::query()
            ->with('device')
            ->where('paused', false)
            ->whereHas('device', fn ($query) => $query->where('paused', false))
            ->where(fn ($query) => $query->whereNull('checked_at')->orWhere('checked_at', '<=', now()->subSeconds(50)))
            ->orderBy('checked_at')
            ->get();

        foreach ($sensors->groupBy(fn (MonitorSensor $sensor) => $sensor->kind->value) as $kind => $group) {
            foreach ($group->chunk(10) as $chunk) {
                $this->checkAll(MonitorSensorKind::from($kind), $chunk);
            }
        }

        return $sensors->count();
    }

    public function checkNow(MonitorSensor $sensor): void
    {
        $this->checkAll($sensor->kind, collect([$sensor]));
    }

    /** @param  Collection<int, MonitorSensor>  $sensors */
    private function checkAll(MonitorSensorKind $kind, Collection $sensors): void
    {
        $results = match ($kind) {
            MonitorSensorKind::Ping => $this->ping($sensors),
            MonitorSensorKind::Tcp => $this->tcp($sensors),
            MonitorSensorKind::Http => $this->http($sensors),
        };

        foreach ($sensors as $sensor) {
            $this->record($sensor, ...$results[$sensor->id]);
        }
    }

    /**
     * @param  Collection<int, MonitorSensor>  $sensors
     * @return array<int, array{0: bool, 1: int|null, 2: string}>
     */
    private function ping(Collection $sensors): array
    {
        $command = fn (string $host) => PHP_OS_FAMILY === 'Windows'
            ? ['ping', '-n', '1', '-w', '1000', $host]
            : ['ping', '-n', '-c', '1', '-W', '1', $host];

        $pool = Process::pool(function (ProcessPool $pool) use ($sensors, $command) {
            foreach ($sensors as $sensor) {
                $pool->as((string) $sensor->id)->timeout(5)->command($command($sensor->device->host));
            }
        })->start()->wait();

        $results = [];
        foreach ($sensors as $sensor) {
            $output = $pool[(string) $sensor->id]->output();
            // Sous Windows, « Impossible de joindre l'hôte » sort aussi avec le code 0 :
            // seule une vraie réponse contient TTL.
            if (stripos($output, 'ttl=') === false) {
                $results[$sensor->id] = [false, null, 'Aucune réponse au ping'];

                continue;
            }
            $ms = preg_match('/(?:temps|time)\s*[=<]\s*([\d.,]+)\s*ms/i', $output, $match) === 1
                ? (int) round((float) str_replace(',', '.', $match[1]))
                : 0;
            $results[$sensor->id] = [true, $ms, "Réponse en {$ms} ms"];
        }

        return $results;
    }

    /**
     * @param  Collection<int, MonitorSensor>  $sensors
     * @return array<int, array{0: bool, 1: int|null, 2: string}>
     */
    private function tcp(Collection $sensors): array
    {
        $results = [];
        foreach ($sensors as $sensor) {
            $start = microtime(true);
            $socket = @fsockopen($sensor->device->host, (int) $sensor->port, $errno, $error, 2);
            $ms = (int) round((microtime(true) - $start) * 1000);
            if ($socket === false) {
                $results[$sensor->id] = [false, null, "Port {$sensor->port} fermé ou injoignable"];

                continue;
            }
            fclose($socket);
            $results[$sensor->id] = [true, $ms, "Port {$sensor->port} ouvert en {$ms} ms"];
        }

        return $results;
    }

    /**
     * @param  Collection<int, MonitorSensor>  $sensors
     * @return array<int, array{0: bool, 1: int|null, 2: string}>
     */
    private function http(Collection $sensors): array
    {
        $responses = Http::pool(function (Pool $pool) use ($sensors) {
            foreach ($sensors as $sensor) {
                // Les caméras et antennes ont souvent un certificat auto-signé.
                $pool->as((string) $sensor->id)->timeout(5)->withoutVerifying()->get($sensor->url());
            }
        });

        $results = [];
        foreach ($sensors as $sensor) {
            $response = $responses[(string) $sensor->id];
            if (! $response instanceof Response) {
                $results[$sensor->id] = [false, null, 'Page web injoignable'];

                continue;
            }
            $time = $response->transferStats?->getTransferTime();
            $ms = $time === null ? null : (int) round($time * 1000);
            // Une page qui demande un mot de passe (401, 403) prouve que le service répond.
            $results[$sensor->id] = $response->status() < 500
                ? [true, $ms, 'HTTP '.$response->status().($ms === null ? '' : " en {$ms} ms")]
                : [false, $ms, 'Erreur HTTP '.$response->status()];
        }

        return $results;
    }

    private function record(MonitorSensor $sensor, bool $ok, ?int $ms, string $message): void
    {
        MonitorReading::create(['sensor_id' => $sensor->id, 'checked_at' => now(), 'ok' => $ok, 'ms' => $ms]);

        $failures = $ok ? 0 : $sensor->failures + 1;
        if ($ok) {
            $slow = $sensor->warning_ms !== null && $ms !== null && $ms > $sensor->warning_ms;
            $status = $slow ? MonitorStatus::Warning : MonitorStatus::Up;
            if ($slow) {
                $message = "Lent : {$ms} ms (seuil {$sensor->warning_ms} ms)";
            }
        } else {
            $status = $failures >= MonitorSensor::DOWN_AFTER ? MonitorStatus::Down : MonitorStatus::Warning;
            if ($status === MonitorStatus::Warning) {
                $message .= " (échec {$failures}/".MonitorSensor::DOWN_AFTER.')';
            }
        }

        $previous = $sensor->status;
        $changes = ['status' => $status, 'last_ms' => $ms, 'last_message' => $message, 'failures' => $failures, 'checked_at' => now()];
        if ($status !== $previous) {
            $changes['status_changed_at'] = now();
            if ($status !== MonitorStatus::Down) {
                $changes += ['acknowledged_by' => null, 'acknowledged_at' => null, 'acknowledgement' => null];
            }
        }
        $sensor->forceFill($changes)->save();

        if ($status === $previous) {
            return;
        }

        MonitorEvent::create(['sensor_id' => $sensor->id, 'from_status' => $previous, 'to_status' => $status, 'message' => $message]);

        if ($status === MonitorStatus::Down || $previous === MonitorStatus::Down) {
            Notification::send($this->recipients(), new MonitorAlert($sensor, down: $status === MonitorStatus::Down));
        }
    }

    /** @return Collection<int, User> */
    private function recipients(): Collection
    {
        $it = Department::query()->where('code', 'IT')->where('is_active', true)->first();
        $handlers = $it ? User::query()->where('actif', true)->handlersOf($it->id)->get() : collect();

        return $handlers->merge(User::query()->where('actif', true)->where('is_super_admin', true)->get())->unique('id');
    }
}
