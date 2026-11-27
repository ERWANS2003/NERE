<?php

namespace App\Services\Monitor;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Ce qu'on apprend d'un équipement sans SNMP : nom DNS, adresse MAC (table ARP
 * du serveur, l'équipement étant sur le même réseau), ports ouverts et titre de
 * son interface web.
 */
class HostProbe
{
    public function name(string $host): ?string
    {
        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return gethostbyname($host) !== $host ? $host : null;
        }
        $name = @gethostbyaddr($host);

        return $name && $name !== $host ? $name : null;
    }

    public function mac(string $host): ?string
    {
        $ip = filter_var($host, FILTER_VALIDATE_IP) !== false ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }
        $result = Process::timeout(5)->run(['arp', '-a', $ip]);

        return preg_match('/\b'.preg_quote($ip, '/').'\s+((?:[0-9a-f]{2}[-:]){5}[0-9a-f]{2})/i', $result->output(), $m) === 1
            ? strtoupper(str_replace('-', ':', $m[1]))
            : null;
    }

    /**
     * @param  list<int>  $ports
     * @return list<int>
     */
    public function openPorts(string $host, array $ports): array
    {
        return array_values(array_filter($ports, function (int $port) use ($host) {
            $socket = @fsockopen($host, $port, $errno, $error, 0.5);
            if ($socket) {
                fclose($socket);
            }

            return (bool) $socket;
        }));
    }

    public function webTitle(string $url): ?string
    {
        try {
            $body = Http::timeout(4)->connectTimeout(2)->withoutVerifying()->get($url)->body();
        } catch (Throwable) {
            return null;
        }
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m) !== 1) {
            return null;
        }
        $title = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $title !== '' && mb_check_encoding($title, 'UTF-8') ? mb_substr($title, 0, 120) : null;
    }
}
