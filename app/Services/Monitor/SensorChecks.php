<?php

namespace App\Services\Monitor;

use App\Enums\MonitorSensorKind;
use App\Models\MonitorSensor;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Process\Pool as ProcessPool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 * Exécute les contrôles. Chaque résultat : [réussi, valeur, message, état à garder].
 */
class SensorChecks
{
    private const SYS_UPTIME = '1.3.6.1.2.1.1.3.0';

    /** Durée de marche de l'appareil (HOST-RESOURCES). sysUpTime ne compte que celle de l'agent SNMP. */
    private const HOST_UPTIME = '1.3.6.1.2.1.25.1.1.0';

    public function __construct(private readonly SnmpClient $snmp) {}

    /**
     * @param  Collection<int, MonitorSensor>  $sensors  capteurs d'un même type
     * @return array<int, array{0: bool, 1: float|null, 2: string, 3?: array}>
     */
    public function run(MonitorSensorKind $kind, Collection $sensors): array
    {
        return match ($kind) {
            MonitorSensorKind::Ping => $this->ping($sensors),
            MonitorSensorKind::Http => $this->http($sensors),
            default => $sensors->mapWithKeys(function (MonitorSensor $sensor) use ($kind) {
                try {
                    return [$sensor->id => $this->single($kind, $sensor)];
                } catch (RuntimeException $exception) {
                    return [$sensor->id => [false, null, $exception->getMessage()]];
                }
            })->all(),
        };
    }

    private function single(MonitorSensorKind $kind, MonitorSensor $sensor): array
    {
        $host = $sensor->device->host;

        return match ($kind) {
            MonitorSensorKind::Tcp => $this->tcp($host, (int) $sensor->port),
            MonitorSensorKind::Dns => $this->dns($host),
            MonitorSensorKind::Ssl => $this->ssl($host, (int) ($sensor->port ?: 443)),
            default => $this->snmp($kind, $sensor),
        };
    }

    /**
     * Pings en parallèle par petits lots. Sur un serveur chargé, lancer un
     * processus peut prendre plusieurs secondes : un ping trop long compte comme
     * sans réponse au lieu d'interrompre tout le lot.
     *
     * @param  list<string>  $hosts
     * @return array<string, string> sortie de ping par hôte
     */
    public static function pingMany(array $hosts, int $waitMs = 1000): array
    {
        $command = fn (string $host) => PHP_OS_FAMILY === 'Windows'
            ? ['ping', '-n', '1', '-w', (string) $waitMs, $host]
            : ['ping', '-n', '-c', '1', '-W', (string) max(1, intdiv($waitMs, 1000)), $host];
        $outputs = [];
        foreach (array_chunk(array_values(array_unique($hosts)), 10) as $chunk) {
            try {
                $pool = Process::pool(function (ProcessPool $pool) use ($chunk, $command) {
                    foreach ($chunk as $host) {
                        $pool->as($host)->timeout(30)->command($command($host));
                    }
                })->start()->wait();
                foreach ($chunk as $host) {
                    $outputs[$host] = $pool[$host]->output();
                }
            } catch (ProcessTimedOutException) {
                foreach ($chunk as $host) {
                    try {
                        $outputs[$host] = Process::timeout(30)->run($command($host))->output();
                    } catch (ProcessTimedOutException) {
                        $outputs[$host] = '';
                    }
                }
            }
        }

        return $outputs;
    }

    private function ping(Collection $sensors): array
    {
        $outputs = self::pingMany($sensors->map(fn (MonitorSensor $sensor) => $sensor->device->host)->all());

        $results = [];
        foreach ($sensors as $sensor) {
            $output = $outputs[$sensor->device->host] ?? '';
            // Sous Windows, « Impossible de joindre l'hôte » sort aussi avec le code 0 :
            // seule une vraie réponse contient TTL.
            if (stripos($output, 'ttl=') === false) {
                $results[$sensor->id] = [false, null, 'Aucune réponse au ping'];

                continue;
            }
            $ms = preg_match('/(?:temps|time)\s*[=<]\s*([\d.,]+)\s*ms/i', $output, $match) === 1
                ? round((float) str_replace(',', '.', $match[1]))
                : 0.0;
            $results[$sensor->id] = [true, $ms, 'Réponse en '.(int) $ms.' ms'];
        }

        return $results;
    }

    private function tcp(string $host, int $port): array
    {
        $start = microtime(true);
        $socket = @fsockopen($host, $port, $errno, $error, 2);
        $ms = round((microtime(true) - $start) * 1000);
        if ($socket === false) {
            return [false, null, "Port {$port} fermé ou injoignable"];
        }
        fclose($socket);

        return [true, $ms, "Port {$port} ouvert en ".(int) $ms.' ms'];
    }

    private function http(Collection $sensors): array
    {
        $responses = Http::pool(function (Pool $pool) use ($sensors) {
            foreach ($sensors as $sensor) {
                // Les caméras et antennes ont souvent un certificat auto-signé.
                $pool->as((string) $sensor->id)->timeout(10)->withoutVerifying()->get($sensor->url());
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
            $ms = $time === null ? null : round($time * 1000);
            // Une page qui demande un mot de passe (401, 403) prouve que le service répond.
            $results[$sensor->id] = $response->status() < 500
                ? [true, $ms, 'HTTP '.$response->status().($ms === null ? '' : ' en '.(int) $ms.' ms')]
                : [false, $ms, 'Erreur HTTP '.$response->status()];
        }

        return $results;
    }

    private function dns(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $start = microtime(true);
            $name = gethostbyaddr($host);
            $ms = round((microtime(true) - $start) * 1000);

            return $name && $name !== $host
                ? [true, $ms, "Nom inverse : {$name}"]
                : [false, null, 'Aucun nom DNS pour cette adresse'];
        }
        $start = microtime(true);
        $ip = gethostbyname($host);
        $ms = round((microtime(true) - $start) * 1000);

        return $ip !== $host ? [true, $ms, "Résolu en {$ip} en ".(int) $ms.' ms'] : [false, null, 'Nom introuvable dans le DNS'];
    }

    private function ssl(string $host, int $port): array
    {
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $error, 5, STREAM_CLIENT_CONNECT, $context);
        if ($socket === false) {
            return [false, null, "Connexion sécurisée impossible sur le port {$port}"];
        }
        $certificate = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
        fclose($socket);
        $info = $certificate ? openssl_x509_parse($certificate) : false;
        if (! $info) {
            return [false, null, 'Certificat illisible'];
        }
        $days = floor(($info['validTo_time_t'] - time()) / 86400);
        $subject = $info['subject']['CN'] ?? $host;

        return $days < 0
            ? [true, $days, "Certificat {$subject} expiré depuis ".abs($days).' jour(s)']
            : [true, $days, "Certificat {$subject} valable encore {$days} jour(s)"];
    }

    private function snmp(MonitorSensorKind $kind, MonitorSensor $sensor): array
    {
        $host = $sensor->device->host;
        $community = (string) $sensor->device->setting('snmp_community');
        $snmp = $this->snmp;

        switch ($kind) {
            case MonitorSensorKind::SnmpUptime:
                $uptimes = $snmp->get($host, $community, [self::HOST_UPTIME, self::SYS_UPTIME]);
                $ticks = is_numeric($uptimes[self::HOST_UPTIME]) ? $uptimes[self::HOST_UPTIME] : $uptimes[self::SYS_UPTIME];
                if ($ticks === null) {
                    throw new RuntimeException('SNMP : durée de fonctionnement non publiée');
                }
                $days = round($ticks / 8_640_000, 1);
                $previous = $sensor->state['ticks'] ?? null;
                $message = 'En marche depuis '.self::duration((int) ($ticks / 100));

                return [true, $days, $previous !== null && $ticks < $previous ? $message.' (redémarré récemment)' : $message, ['ticks' => $ticks]];

            case MonitorSensorKind::SnmpCpu:
                $loads = array_filter($snmp->walk($host, $community, '1.3.6.1.2.1.25.3.3.1.2'), 'is_numeric');
                if (! $loads) {
                    throw new RuntimeException('SNMP : charge processeur non publiée');
                }
                $value = round(array_sum($loads) / count($loads), 1);

                return [true, $value, "Charge moyenne {$value} % sur ".count($loads).' cœur(s)'];

            case MonitorSensorKind::SnmpMemory:
            case MonitorSensorKind::SnmpDisk:
                $storage = $this->storage($host, $community);
                $wanted = $kind === MonitorSensorKind::SnmpMemory ? '1.3.6.1.2.1.25.2.1.2' : '1.3.6.1.2.1.25.2.1.4';
                $rows = array_filter($storage, fn (array $row) => $row['type'] === $wanted && $row['size'] > 0);
                if ($kind === MonitorSensorKind::SnmpDisk && filled($sensor->parameter)) {
                    $rows = array_filter($rows, fn (array $row) => stripos($row['descr'], (string) $sensor->parameter) === 0);
                }
                if (! $rows) {
                    throw new RuntimeException($kind === MonitorSensorKind::SnmpMemory ? 'SNMP : mémoire non publiée' : 'SNMP : disque introuvable');
                }
                usort($rows, fn ($a, $b) => $b['used'] / $b['size'] <=> $a['used'] / $a['size']);
                $row = $rows[0];
                $value = round(100 * $row['used'] / $row['size'], 1);
                $free = self::bytes(($row['size'] - $row['used']) * $row['units']);

                return [true, $value, trim(explode(' ', $row['descr'])[0] ?: 'Mémoire')." : {$value} % utilisé, {$free} libres"];

            case MonitorSensorKind::SnmpPrinter:
                $levels = $snmp->walk($host, $community, '1.3.6.1.2.1.43.11.1.1.9');
                $maxima = $snmp->walk($host, $community, '1.3.6.1.2.1.43.11.1.1.8');
                $names = $snmp->walk($host, $community, '1.3.6.1.2.1.43.11.1.1.6');
                $supplies = [];
                foreach ($levels as $oid => $level) {
                    $suffix = substr($oid, strlen('1.3.6.1.2.1.43.11.1.1.9'));
                    $max = $maxima['1.3.6.1.2.1.43.11.1.1.8'.$suffix] ?? null;
                    // -3 = « il en reste », -2 = inconnu : seuls les niveaux chiffrés comptent.
                    if (is_numeric($level) && is_numeric($max) && $max > 0 && $level >= 0) {
                        $supplies[] = ['name' => (string) ($names['1.3.6.1.2.1.43.11.1.1.6'.$suffix] ?? 'Consommable'), 'percent' => round(100 * $level / $max)];
                    }
                }
                if (! $supplies) {
                    throw new RuntimeException('SNMP : niveaux des consommables non publiés');
                }
                usort($supplies, fn ($a, $b) => $a['percent'] <=> $b['percent']);

                return [true, (float) $supplies[0]['percent'], implode(', ', array_map(fn ($s) => $s['name'].' '.$s['percent'].' %', array_slice($supplies, 0, 4)))];

            case MonitorSensorKind::SnmpPrinterStatus:
                [$severity, $message] = self::printerState(
                    array_values(array_filter(array_map('strval', $snmp->walk($host, $community, '1.3.6.1.2.1.43.16.5.1.2', 10)))),
                    (string) ($snmp->get($host, $community, ['1.3.6.1.2.1.25.3.5.1.2.1'])['1.3.6.1.2.1.25.3.5.1.2.1'] ?? ''),
                );

                return [true, (float) $severity, $message];

            case MonitorSensorKind::SnmpPages:
                $oid = '1.3.6.1.2.1.43.10.2.1.4.1.1';
                $pages = $snmp->get($host, $community, [$oid])[$oid];
                if (! is_numeric($pages)) {
                    throw new RuntimeException('SNMP : compteur de pages non publié');
                }
                $first = $sensor->readings()->where('checked_at', '>=', now()->startOfDay())->whereNotNull('value')->orderBy('checked_at')->value('value');
                $today = $first === null ? null : max(0, (int) ($pages - $first));

                return [true, (float) $pages, number_format((float) $pages, 0, ',', ' ').' pages au total'.($today === null ? '' : ' · '.number_format($today, 0, ',', ' ').' aujourd’hui')];

            case MonitorSensorKind::SnmpTraffic:
                $index = filled($sensor->parameter) ? (int) $sensor->parameter : ($sensor->state['index'] ?? $this->busiestInterface($host, $community));
                $in = "1.3.6.1.2.1.31.1.1.1.6.{$index}";
                $out = "1.3.6.1.2.1.31.1.1.1.10.{$index}";
                $values = $snmp->get($host, $community, [$in, $out, self::SYS_UPTIME]);
                if ($values[$in] === null || $values[$out] === null) {
                    throw new RuntimeException("SNMP : compteurs de l’interface {$index} non publiés");
                }
                $state = ['index' => $index, 'in' => $values[$in], 'out' => $values[$out], 'at' => microtime(true)];
                $previous = $sensor->state ?? [];
                if (! isset($previous['in'], $previous['at']) || ($previous['index'] ?? null) !== $index || $values[$in] < $previous['in']) {
                    return [true, null, "Interface {$index} : première mesure, le débit s’affichera au prochain contrôle", $state];
                }
                $seconds = max(1, $state['at'] - $previous['at']);
                $inKbps = ($values[$in] - $previous['in']) * 8 / 1000 / $seconds;
                $outKbps = ($values[$out] - $previous['out']) * 8 / 1000 / $seconds;

                return [true, round($inKbps + $outKbps, 1), "Interface {$index} : entrant ".self::rate($inKbps).', sortant '.self::rate($outKbps), $state];

            default:
                $oid = ltrim((string) $sensor->parameter, '.');
                $value = $snmp->get($host, $community, [$oid])[$oid];
                if ($value === null) {
                    throw new RuntimeException("SNMP : l’OID {$oid} ne renvoie rien");
                }

                return is_numeric($value)
                    ? [true, (float) $value, "Valeur : {$value} ".($sensor->unit ?? '')]
                    : [true, null, 'Valeur : '.mb_substr((string) $value, 0, 200)];
        }
    }

    /** @return list<array{type: string, descr: string, units: int, size: int, used: int}> */
    private function storage(string $host, string $community): array
    {
        $base = '1.3.6.1.2.1.25.2.3.1.';
        $columns = [];
        foreach ([2 => 'type', 3 => 'descr', 4 => 'units', 5 => 'size', 6 => 'used'] as $column => $name) {
            foreach ($this->snmp->walk($host, $community, $base.$column) as $oid => $value) {
                $columns[substr($oid, strlen($base.$column) + 1)][$name] = $value;
            }
        }

        return array_values(array_filter(array_map(fn ($row) => isset($row['type'], $row['size'], $row['used'])
            ? ['type' => (string) $row['type'], 'descr' => (string) ($row['descr'] ?? ''), 'units' => (int) ($row['units'] ?? 1), 'size' => (int) $row['size'], 'used' => (int) $row['used']]
            : null, $columns)));
    }

    private function busiestInterface(string $host, string $community): int
    {
        $counters = array_filter($this->snmp->walk($host, $community, '1.3.6.1.2.1.31.1.1.1.6'), 'is_numeric');
        if (! $counters) {
            throw new RuntimeException('SNMP : aucune interface réseau publiée');
        }
        arsort($counters);

        return (int) substr((string) array_key_first($counters), strlen('1.3.6.1.2.1.31.1.1.1.6.'));
    }

    /**
     * Gravité d'une imprimante d'après le texte de son écran et ses erreurs
     * déclarées (hrPrinterDetectedErrorState) : 0 prête, 1 à surveiller, 2 bloquée.
     *
     * @param  list<string>  $display
     * @return array{0: int, 1: string}
     */
    public static function printerState(array $display, string $errorBits): array
    {
        $blocking = [0 => [0x40 => 'plus de papier', 0x10 => 'plus de toner', 0x08 => 'porte ouverte', 0x04 => 'bourrage', 0x02 => 'hors ligne'], 1 => [0x80 => 'bac absent', 0x20 => 'consommable absent', 0x08 => 'bac de sortie plein']];
        $watch = [0 => [0x80 => 'papier bas', 0x20 => 'toner bas', 0x01 => 'intervention demandée'], 1 => [0x10 => 'bac de sortie presque plein', 0x04 => 'bac vide', 0x02 => 'maintenance en retard']];
        $problems = [];
        $severity = 0;
        foreach ([2 => $blocking, 1 => $watch] as $level => $bitsByByte) {
            foreach ($bitsByByte as $byte => $bits) {
                $value = isset($errorBits[$byte]) ? ord($errorBits[$byte]) : 0;
                foreach ($bits as $bit => $label) {
                    if ($value & $bit) {
                        $problems[] = $label;
                        $severity = max($severity, $level);
                    }
                }
            }
        }

        $text = trim(implode(' · ', $display));
        if (preg_match('/bourrage|jam|fermer|ouvert|porte|capot|couvercle|cover|door|erreur|error|appel|service|toner vide|plus de toner|remplacer.*toner|no toner|replace toner|charger.*papier|ajouter.*papier|plus de papier|load paper|add paper|out of paper|paper out/iu', $text) === 1) {
            $severity = 2;
        } elseif (preg_match('/faible|bas\b|bientôt|presque|low|nearly|soon|maintenance/iu', $text) === 1) {
            $severity = max($severity, 1);
        }

        $message = $text !== '' ? 'Écran : '.$text : 'Écran non publié';
        if ($problems) {
            $message .= ' · '.ucfirst(implode(', ', array_unique($problems)));
        }

        return [$severity, $message];
    }

    public static function duration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $days > 0 ? "{$days} j {$hours} h" : ($hours > 0 ? "{$hours} h {$minutes} min" : "{$minutes} min");
    }

    private static function bytes(float $bytes): string
    {
        foreach (['o', 'Ko', 'Mo', 'Go', 'To'] as $unit) {
            if ($bytes < 1024) {
                return number_format($bytes, $unit === 'o' ? 0 : 1, ',', ' ').' '.$unit;
            }
            $bytes /= 1024;
        }

        return number_format($bytes, 1, ',', ' ').' Po';
    }

    private static function rate(float $kbps): string
    {
        return $kbps >= 1000 ? number_format($kbps / 1000, 1, ',', ' ').' Mbit/s' : number_format($kbps, 0, ',', ' ').' kbit/s';
    }
}
