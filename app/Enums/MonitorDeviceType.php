<?php

namespace App\Enums;

enum MonitorDeviceType: string
{
    case Camera = 'camera';
    case Printer = 'printer';
    case Server = 'server';
    case Antenna = 'antenna';
    case Network = 'network';

    public function label(): string
    {
        return match ($this) {
            self::Camera => 'Caméra',
            self::Printer => 'Imprimante',
            self::Server => 'Serveur',
            self::Antenna => 'Antenne',
            self::Network => 'Switch / routeur',
        };
    }

    /**
     * Modèle d'équipement : capteurs créés avec lui. Les capteurs SNMP ne sont
     * ajoutés que si on le demande, l'équipement devant avoir SNMP activé.
     *
     * @return list<array{kind: string, name: string, port?: int, parameter?: string}>
     */
    public function template(bool $snmp): array
    {
        $sensors = match ($this) {
            self::Camera => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'tcp', 'name' => 'Flux vidéo (RTSP)', 'port' => 554],
                ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'parameter' => '/'],
            ],
            self::Printer => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'tcp', 'name' => "Port d'impression", 'port' => 9100],
            ],
            self::Server => [['kind' => 'ping', 'name' => 'Ping']],
            self::Antenna, self::Network => [
                ['kind' => 'ping', 'name' => 'Ping'],
                ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'parameter' => '/'],
            ],
        };

        if ($snmp) {
            $sensors = [...$sensors, ...match ($this) {
                self::Server => [
                    ['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement'],
                    ['kind' => 'snmp_cpu', 'name' => 'Processeur'],
                    ['kind' => 'snmp_memory', 'name' => 'Mémoire'],
                    ['kind' => 'snmp_disk', 'name' => 'Disque'],
                ],
                self::Printer => [
                    ['kind' => 'snmp_printer_status', 'name' => 'État de l’imprimante'],
                    ['kind' => 'snmp_printer', 'name' => 'Consommables'],
                    ['kind' => 'snmp_pages', 'name' => 'Compteur de pages'],
                ],
                self::Camera => [['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement']],
                self::Antenna, self::Network => [
                    ['kind' => 'snmp_uptime', 'name' => 'Durée de fonctionnement'],
                    ['kind' => 'snmp_traffic', 'name' => 'Trafic'],
                ],
            }];
        }

        return $sensors;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
