<?php

namespace App\Services\Monitor;

use App\Models\MonitorDevice;
use Illuminate\Process\Pool as ProcessPool;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use RuntimeException;

/**
 * Découverte automatique : balayage ping d'un sous-réseau, ports ouverts et
 * description SNMP pour deviner le type d'équipement. Les ordinateurs du
 * personnel sont repérés pour être laissés de côté.
 */
class NetworkScanner
{
    private const PORTS = [22, 80, 135, 443, 445, 554, 3389, 9100];

    public function __construct(private readonly SnmpClient $snmp) {}

    /** @return list<string> */
    public static function hosts(string $cidr): array
    {
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})$/', trim($cidr), $m) !== 1
            || filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new InvalidArgumentException('Saisissez un sous-réseau, par exemple 192.168.10.0/24.');
        }
        $prefix = (int) $m[2];
        if ($prefix < 24 || $prefix > 32) {
            throw new InvalidArgumentException('Le sous-réseau doit contenir au plus 256 adresses (/24 à /32).');
        }
        if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
            throw new InvalidArgumentException('Seuls les réseaux internes (privés) peuvent être balayés.');
        }
        $mask = -1 << (32 - $prefix) & 0xFFFFFFFF;
        $network = ip2long($m[1]) & $mask;
        $size = 2 ** (32 - $prefix);
        $first = $size > 2 ? 1 : 0;
        $last = $size > 2 ? $size - 2 : $size - 1;

        return array_map(fn ($i) => long2ip($network + $i), range($first, $last));
    }

    /** @return list<array<string, mixed>> */
    public function scan(string $cidr, string $community): array
    {
        $alive = [];
        foreach (array_chunk(self::hosts($cidr), 25) as $chunk) {
            $pool = Process::pool(function (ProcessPool $pool) use ($chunk) {
                foreach ($chunk as $ip) {
                    $pool->as($ip)->timeout(5)->command(PHP_OS_FAMILY === 'Windows' ? ['ping', '-n', '1', '-w', '700', $ip] : ['ping', '-n', '-c', '1', '-W', '1', $ip]);
                }
            })->start()->wait();
            foreach ($chunk as $ip) {
                if (stripos($pool[$ip]->output(), 'ttl=') !== false) {
                    $alive[] = $ip;
                }
            }
        }

        $known = MonitorDevice::query()->pluck('host')->all();

        return array_map(function (string $ip) use ($community, $known) {
            $ports = array_values(array_filter(self::PORTS, function (int $port) use ($ip) {
                $socket = @fsockopen($ip, $port, $errno, $error, 0.4);
                if ($socket) {
                    fclose($socket);
                }

                return (bool) $socket;
            }));
            try {
                $description = (string) ($this->snmp->get($ip, $community, ['1.3.6.1.2.1.1.1.0'])['1.3.6.1.2.1.1.1.0'] ?? '');
            } catch (RuntimeException) {
                $description = '';
            }
            $name = gethostbyaddr($ip);
            [$type, $workstation] = self::classify($ports, $description);

            return [
                'ip' => $ip,
                'name' => $name && $name !== $ip ? $name : null,
                'ports' => $ports,
                'description' => mb_substr($description, 0, 200),
                'type' => $type,
                'workstation' => $workstation,
                'monitored' => in_array($ip, $known, true),
            ];
        }, $alive);
    }

    /**
     * @param  list<int>  $ports
     * @return array{0: string|null, 1: bool} type deviné, et « probablement un poste de travail »
     */
    public static function classify(array $ports, string $description): array
    {
        $d = strtolower($description);
        $has = fn (int ...$wanted) => array_intersect($wanted, $ports) !== [];

        return match (true) {
            $has(9100) || str_contains($d, 'printer') || str_contains($d, 'laserjet') || str_contains($d, 'imprim') => ['printer', false],
            $has(554) || preg_match('/hikvision|dahua|axis|camera|ipcam|nvr|dvr/', $d) === 1 => ['camera', false],
            preg_match('/airos|ubiquiti|cambium|mikrotik|routeros|airmax|ligowave|mimosa/', $d) === 1 => ['antenna', false],
            preg_match('/cisco|switch|aruba|procurve|netgear|tp-link|fortigate|juniper/', $d) === 1 => ['network', false],
            str_contains($d, 'windows') && str_contains($d, 'server') => ['server', false],
            $has(22) && str_contains($d, 'linux') => ['server', false],
            // Partage Windows sans SNMP serveur : très probablement un PC du personnel.
            $has(135, 445) => [null, true],
            $has(22) => ['server', false],
            $has(80, 443) => ['antenna', false],
            default => [null, false],
        };
    }
}
