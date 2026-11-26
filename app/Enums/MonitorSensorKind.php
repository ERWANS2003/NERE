<?php

namespace App\Enums;

enum MonitorSensorKind: string
{
    case Ping = 'ping';
    case Tcp = 'tcp';
    case Http = 'http';
    case Dns = 'dns';
    case Ssl = 'ssl';
    case SnmpUptime = 'snmp_uptime';
    case SnmpTraffic = 'snmp_traffic';
    case SnmpCpu = 'snmp_cpu';
    case SnmpMemory = 'snmp_memory';
    case SnmpDisk = 'snmp_disk';
    case SnmpPrinter = 'snmp_printer';
    case SnmpCustom = 'snmp_custom';

    public function label(): string
    {
        return match ($this) {
            self::Ping => 'Ping',
            self::Tcp => 'Port TCP',
            self::Http => 'Page web (HTTP/HTTPS)',
            self::Dns => 'Résolution DNS',
            self::Ssl => 'Certificat SSL',
            self::SnmpUptime => 'SNMP : durée de fonctionnement',
            self::SnmpTraffic => 'SNMP : trafic réseau',
            self::SnmpCpu => 'SNMP : charge processeur',
            self::SnmpMemory => 'SNMP : mémoire',
            self::SnmpDisk => 'SNMP : espace disque',
            self::SnmpPrinter => 'SNMP : consommables d’imprimante',
            self::SnmpCustom => 'SNMP : valeur personnalisée (OID)',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Ping, self::Tcp, self::Http, self::Dns => 'ms',
            self::Ssl, self::SnmpUptime => 'jours',
            self::SnmpTraffic => 'kbit/s',
            self::SnmpCpu, self::SnmpMemory, self::SnmpDisk, self::SnmpPrinter => '%',
            self::SnmpCustom => '',
        };
    }

    public function isSnmp(): bool
    {
        return str_starts_with($this->value, 'snmp_');
    }

    public function needsPort(): bool
    {
        return in_array($this, [self::Tcp, self::Http, self::Ssl], true);
    }

    /** Libellé du champ « paramètre » quand le contrôle en a besoin. */
    public function parameterLabel(): ?string
    {
        return match ($this) {
            self::Http => 'Chemin de la page (ex. /login)',
            self::SnmpTraffic => 'Numéro d’interface (vide = la plus chargée)',
            self::SnmpDisk => 'Disque (ex. C:, vide = le plus rempli)',
            self::SnmpCustom => 'OID (ex. 1.3.6.1.4.1.…)',
            default => null,
        };
    }

    /** Seuils par défaut, comme les limites de canal de PRTG. */
    public function defaultLimits(): array
    {
        return match ($this) {
            self::Ssl => ['warn_below' => 30, 'error_below' => 7],
            self::SnmpCpu => ['warn_above' => 80, 'error_above' => 95],
            self::SnmpMemory, self::SnmpDisk => ['warn_above' => 85, 'error_above' => 95],
            self::SnmpPrinter => ['warn_below' => 15, 'error_below' => 5],
            default => [],
        };
    }

    public function defaultPort(): ?int
    {
        return match ($this) {
            self::Http => 80,
            self::Ssl => 443,
            default => null,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])->all();
    }
}
