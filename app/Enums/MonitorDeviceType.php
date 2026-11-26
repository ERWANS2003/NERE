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

    /** Tracé SVG (24×24, trait) de l'icône du type, pour les cartes. */
    public function iconPath(): string
    {
        return match ($this) {
            self::Camera => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
            self::Printer => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
            self::Server => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
            self::Antenna => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
            self::Network => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
