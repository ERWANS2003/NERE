<?php

namespace App\Services\Monitor;

use App\Enums\MonitorDeviceType;
use App\Models\MonitorDevice;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Fiche d'un équipement : ce que le réseau en dit (nom, MAC, ports, page web),
 * puis en SNMP son identité, ses interfaces, et selon le type, consommables,
 * bacs, compteur, écran et alertes d'une imprimante, disques, mémoire et
 * processeurs d'un serveur, voisins d'un switch ou d'une antenne.
 * Gardée 10 minutes en cache.
 */
class DeviceInventory
{
    /** Ports utiles à connaître selon le type, avec ce qu'ils indiquent. */
    public const PORTS = [
        'camera' => [80 => 'Interface web', 443 => 'Interface web sécurisée', 554 => 'Flux vidéo RTSP', 8000 => 'Service Hikvision', 37777 => 'Service Dahua'],
        'printer' => [80 => 'Interface web', 443 => 'Interface web sécurisée', 515 => 'Impression LPD', 631 => 'Impression IPP', 9100 => 'Impression directe'],
        'server' => [22 => 'SSH', 80 => 'Site web', 443 => 'Site web sécurisé', 445 => 'Partage de fichiers', 1433 => 'SQL Server', 3389 => 'Bureau à distance', 5432 => 'PostgreSQL', 5985 => 'Administration à distance (WinRM)'],
        'antenna' => [22 => 'SSH', 80 => 'Interface web', 443 => 'Interface web sécurisée', 8291 => 'Winbox (MikroTik)'],
        'network' => [22 => 'SSH', 23 => 'Telnet', 80 => 'Interface web', 443 => 'Interface web sécurisée', 8291 => 'Winbox (MikroTik)'],
    ];

    public function __construct(private readonly SnmpClient $snmp, private readonly HostProbe $probe) {}

    /** @return array<string, mixed> */
    public function get(MonitorDevice $device, bool $refresh = false): array
    {
        $key = "supervision-fiche-v2-{$device->id}";
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
        $network = $this->network($device);
        try {
            $system = $this->snmp->get($host, $community, [
                '1.3.6.1.2.1.1.1.0', '1.3.6.1.2.1.1.3.0', '1.3.6.1.2.1.1.4.0', '1.3.6.1.2.1.1.5.0', '1.3.6.1.2.1.1.6.0',
                '1.3.6.1.2.1.25.3.2.1.3.1', '1.3.6.1.2.1.43.5.1.1.17.1', '1.3.6.1.2.1.25.1.6.0', '1.3.6.1.2.1.25.2.2.0', '1.3.6.1.2.1.25.1.1.0',
                '1.3.6.1.2.1.25.1.5.0',
            ]);
        } catch (RuntimeException $exception) {
            return ['network' => $network, 'snmp_error' => $exception->getMessage()];
        }
        if (array_filter($system, fn ($value) => $value !== null) === []) {
            return ['network' => $network, 'snmp_error' => 'l’équipement ne publie aucune information'];
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
                'Sessions ouvertes' => $device->type === MonitorDeviceType::Server && is_numeric($system['1.3.6.1.2.1.25.1.5.0']) ? (string) $system['1.3.6.1.2.1.25.1.5.0'] : null,
                'Mémoire installée' => is_numeric($system['1.3.6.1.2.1.25.2.2.0']) ? number_format($system['1.3.6.1.2.1.25.2.2.0'] / 1048576, 1, ',', ' ').' Go' : null,
            ]),
            'interfaces' => $this->interfaces($host, $community),
        ];
        $network['mac'] ??= collect($info['interfaces'])->pluck('mac')->filter()->first();
        $info['network'] = $network;

        if ($device->type === MonitorDeviceType::Printer) {
            $info += $this->printer($host, $community);
        }
        if ($device->type === MonitorDeviceType::Server) {
            $info['storage'] = $this->storage($host, $community);
            $loads = array_values(array_filter($this->snmp->walk($host, $community, '1.3.6.1.2.1.25.3.3.1.2', 128), 'is_numeric'));
            $info['cpu'] = $loads;
        }
        if (in_array($device->type, [MonitorDeviceType::Network, MonitorDeviceType::Antenna], true)) {
            $info['neighbors'] = $this->neighbors($host, $community);
        }

        return $info;
    }

    /** @return array{name: string|null, mac: string|null, ports: array<int, string>, web: array{url: string, title: string|null}|null} */
    private function network(MonitorDevice $device): array
    {
        $known = self::PORTS[$device->type->value];
        $open = $this->probe->openPorts($device->host, array_keys($known));
        $scheme = in_array(443, $open, true) ? 'https' : (in_array(80, $open, true) ? 'http' : null);
        $url = $scheme ? $scheme.'://'.$device->host.'/' : null;

        return [
            'name' => $this->probe->name($device->host),
            'mac' => $this->probe->mac($device->host),
            'ports' => array_intersect_key($known, array_flip($open)),
            'web' => $url ? ['url' => $url, 'title' => $this->probe->webTitle($url)] : null,
        ];
    }

    /**
     * Voisins annoncés en LLDP : quel équipement est branché, et sur quel port.
     *
     * @return list<array{name: string, port: string|null}>
     */
    private function neighbors(string $host, string|array $community): array
    {
        $names = $this->snmp->walk($host, $community, '1.0.8802.1.1.2.1.4.1.1.9', 48);
        $ports = $this->snmp->walk($host, $community, '1.0.8802.1.1.2.1.4.1.1.8', 48);
        $rows = [];
        foreach ($names as $oid => $name) {
            $port = $ports['1.0.8802.1.1.2.1.4.1.1.8.'.substr($oid, strlen('1.0.8802.1.1.2.1.4.1.1.9.'))] ?? null;
            $rows[] = ['name' => trim((string) $name), 'port' => filled($port) ? (string) $port : null];
        }

        return array_values(array_filter($rows, fn ($row) => $row['name'] !== '' && mb_check_encoding($row['name'], 'UTF-8')));
    }

    /** @return list<array{index: int, name: string, up: bool, speed: string|null, mac: string|null}> */
    private function interfaces(string $host, string|array $community): array
    {
        $names = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.2', 48);
        $states = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.8', 48);
        $speeds = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.5', 48);
        $macs = $this->snmp->walk($host, $community, '1.3.6.1.2.1.2.2.1.6', 48);
        $rows = [];
        foreach ($names as $oid => $name) {
            $index = substr($oid, strlen('1.3.6.1.2.1.2.2.1.2.'));
            $speed = $speeds['1.3.6.1.2.1.2.2.1.5.'.$index] ?? null;
            $rows[] = [
                'index' => (int) $index,
                'name' => (string) $name,
                'up' => ($states['1.3.6.1.2.1.2.2.1.8.'.$index] ?? null) === 1,
                'speed' => is_numeric($speed) && $speed > 0 ? ($speed >= 1e9 ? round($speed / 1e9).' Gbit/s' : round($speed / 1e6).' Mbit/s') : null,
                'mac' => self::mac($macs['1.3.6.1.2.1.2.2.1.6.'.$index] ?? null),
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
        $alerts = collect($this->snmp->walk($host, $community, '1.3.6.1.2.1.43.18.1.1.8', 10))
            ->map(fn ($alert) => trim((string) $alert))
            ->filter(fn ($alert) => $alert !== '' && mb_check_encoding($alert, 'UTF-8'))
            ->unique()->values()->all();

        return ['supplies' => $supplies, 'trays' => $trays, 'pages' => is_numeric($pages) ? (int) $pages : null, 'display' => $display, 'alerts' => $alerts];
    }

    /** Adresse MAC lue en SNMP (6 octets bruts). */
    private static function mac(mixed $raw): ?string
    {
        return is_string($raw) && strlen($raw) === 6 && trim($raw, "\0") !== ''
            ? implode(':', str_split(strtoupper(bin2hex($raw)), 2))
            : null;
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
