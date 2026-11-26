<?php

namespace App\Services\Monitor;

use App\Enums\MonitorDeviceType;
use App\Models\MonitorDevice;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Fiche d'un équipement lue en SNMP : identité, interfaces réseau, et selon le
 * type, consommables, bacs, compteur et écran d'une imprimante, ou disques,
 * mémoire et processeurs d'un serveur. Gardée 10 minutes en cache.
 */
class DeviceInventory
{
    public function __construct(private readonly SnmpClient $snmp) {}

    /** @return array<string, mixed> */
    public function get(MonitorDevice $device, bool $refresh = false): array
    {
        $key = "supervision-fiche-{$device->id}";
        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(10), fn () => $this->read($device) + ['read_at' => now()->toIso8601String()]);
    }

    /** @return array<string, mixed> */
    private function read(MonitorDevice $device): array
    {
        $host = $device->host;
        $community = $device->snmpCredentials();
        try {
            $system = $this->snmp->get($host, $community, [
                '1.3.6.1.2.1.1.1.0', '1.3.6.1.2.1.1.3.0', '1.3.6.1.2.1.1.4.0', '1.3.6.1.2.1.1.5.0', '1.3.6.1.2.1.1.6.0',
                '1.3.6.1.2.1.25.3.2.1.3.1', '1.3.6.1.2.1.43.5.1.1.17.1', '1.3.6.1.2.1.25.1.6.0', '1.3.6.1.2.1.25.2.2.0', '1.3.6.1.2.1.25.1.1.0',
            ]);
        } catch (RuntimeException $exception) {
            return ['error' => $exception->getMessage()];
        }

        // Durée de marche de l'appareil si publiée, sinon celle de l'agent SNMP.
        $ticks = is_numeric($system['1.3.6.1.2.1.25.1.1.0']) ? $system['1.3.6.1.2.1.25.1.1.0'] : $system['1.3.6.1.2.1.1.3.0'];
        $text = fn (string $oid) => is_string($system[$oid]) && trim($system[$oid]) !== '' && mb_check_encoding($system[$oid], 'UTF-8') ? trim($system[$oid]) : null;
        $info = [
            'identity' => array_filter([
                'Modèle' => $text('1.3.6.1.2.1.25.3.2.1.3.1'),
                'Description' => $text('1.3.6.1.2.1.1.1.0'),
                'N° de série' => $text('1.3.6.1.2.1.43.5.1.1.17.1'),
                'Nom réseau' => $text('1.3.6.1.2.1.1.5.0'),
                'Emplacement' => $text('1.3.6.1.2.1.1.6.0'),
                'Contact' => $text('1.3.6.1.2.1.1.4.0'),
                'En marche depuis' => is_numeric($ticks) ? SensorChecks::duration((int) ($ticks / 100)) : null,
                'Processus' => is_numeric($system['1.3.6.1.2.1.25.1.6.0']) ? (string) $system['1.3.6.1.2.1.25.1.6.0'] : null,
                'Mémoire installée' => is_numeric($system['1.3.6.1.2.1.25.2.2.0']) ? number_format($system['1.3.6.1.2.1.25.2.2.0'] / 1048576, 1, ',', ' ').' Go' : null,
            ]),
            'interfaces' => $this->interfaces($host, $community),
        ];

        if ($device->type === MonitorDeviceType::Printer) {
            $info += $this->printer($host, $community);
        }
        if ($device->type === MonitorDeviceType::Server) {
            $info['storage'] = $this->storage($host, $community);
            $loads = array_values(array_filter($this->snmp->walk($host, $community, '1.3.6.1.2.1.25.3.3.1.2', 128), 'is_numeric'));
            $info['cpu'] = $loads;
        }

        return $info;
    }

    /** @return list<array{index: int, name: string, up: bool, speed: string|null}> */
    private function interfaces(string $host, string|array $community): array
    {
        $names = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.2', 48);
        $states = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.8', 48);
        $speeds = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.5', 48);
        $rows = [];
        foreach ($names as $oid => $name) {
            $index = substr($oid, strlen('1.3.6.1.2.1.2.2.1.2.'));
            $speed = $speeds['1.3.6.1.2.1.2.2.1.5.'.$index] ?? null;
            $rows[] = [
                'index' => (int) $index,
                'name' => (string) $name,
                'up' => ($states['1.3.6.1.2.1.2.2.1.8.'.$index] ?? null) === 1,
                'speed' => is_numeric($speed) && $speed > 0 ? ($speed >= 1e9 ? round($speed / 1e9).' Gbit/s' : round($speed / 1e6).' Mbit/s') : null,
            ];
        }

        // Les interfaces virtuelles (boucle locale, tunnels) n'aident pas à diagnostiquer.
        return array_values(array_filter($rows, fn ($row) => preg_match('/loopback|^lo$|pseudo|tunnel|isatap|teredo|6to4|miniport|filter|qos|wfp/i', $row['name']) !== 1));
    }

    /** @return array<string, mixed> */
    private function printer(string $host, string|array $community): array
    {
        $column = fn (string $base) => collect($this->snmp->walk($host, $community, $base, 40))->mapWithKeys(fn ($v, $oid) => [substr($oid, strlen($base) + 1) => $v]);
        $supplyNames = $column('1.3.6.1.2.1.43.11.1.1.6');
        $supplyMax = $column('1.3.6.1.2.1.43.11.1.1.8');
        $supplyLevel = $column('1.3.6.1.2.1.43.11.1.1.9');
        $supplies = $supplyNames->map(fn ($name, $i) => [
            'name' => (string) $name,
            'percent' => is_numeric($supplyLevel[$i] ?? null) && ($supplyMax[$i] ?? 0) > 0 && $supplyLevel[$i] >= 0 ? (int) round(100 * $supplyLevel[$i] / $supplyMax[$i]) : null,
            'state' => match ((int) ($supplyLevel[$i] ?? -2)) { -3 => 'Il en reste', -2 => 'Inconnu', default => null },
        ])->values()->all();

        $trayMax = $column('1.3.6.1.2.1.43.8.2.1.9');
        $trayLevel = $column('1.3.6.1.2.1.43.8.2.1.10');
        $trayNames = $column('1.3.6.1.2.1.43.8.2.1.13');
        $position = 0;
        $trays = $trayMax->map(function ($max, $i) use ($trayLevel, $trayNames, &$position) {
            $position++;

            return [
                'name' => filled($trayNames[$i] ?? null) ? (string) $trayNames[$i] : 'Bac '.$position,
                'level' => is_numeric($trayLevel[$i] ?? null) ? (int) $trayLevel[$i] : null,
                'max' => is_numeric($max) ? (int) $max : null,
            ];
        })->values()->all();

        $pages = $this->snmp->get($host, $community, ['1.3.6.1.2.1.43.10.2.1.4.1.1'])['1.3.6.1.2.1.43.10.2.1.4.1.1'];
        $display = array_values(array_filter(array_map('strval', $this->snmp->walk($host, $community, '1.3.6.1.2.1.43.16.5.1.2', 10))));

        return ['supplies' => $supplies, 'trays' => $trays, 'pages' => is_numeric($pages) ? (int) $pages : null, 'display' => $display];
    }

    /** @return list<array{name: string, percent: float, free: string, total: string}> */
    private function storage(string $host, string|array $community): array
    {
        $base = '1.3.6.1.2.1.25.2.3.1.';
        $rows = [];
        foreach ([2 => 'type', 3 => 'descr', 4 => 'units', 5 => 'size', 6 => 'used'] as $column => $name) {
            foreach ($this->snmp->walk($host, $community, $base.$column, 64) as $oid => $value) {
                $rows[substr($oid, strlen($base.$column) + 1)][$name] = $value;
            }
        }
        $human = fn (float $bytes) => $bytes >= 1099511627776 ? number_format($bytes / 1099511627776, 1, ',', ' ').' To' : number_format($bytes / 1073741824, 1, ',', ' ').' Go';

        return collect($rows)
            ->filter(fn ($row) => in_array($row['type'] ?? null, ['1.3.6.1.2.1.25.2.1.4', '1.3.6.1.2.1.25.2.1.2'], true) && ($row['size'] ?? 0) > 0)
            ->map(fn ($row) => [
                'name' => ($row['type'] === '1.3.6.1.2.1.25.2.1.2' ? 'Mémoire vive' : trim(explode(' Label', (string) ($row['descr'] ?? 'Disque'))[0])),
                'percent' => round(100 * $row['used'] / $row['size'], 1),
                'free' => $human(($row['size'] - $row['used']) * ($row['units'] ?? 1)),
                'total' => $human($row['size'] * ($row['units'] ?? 1)),
            ])->values()->all();
    }
}
