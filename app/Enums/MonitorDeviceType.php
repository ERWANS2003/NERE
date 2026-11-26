<?php

namespace App\Enums;

enum MonitorDeviceType: string
{
    case Camera = 'camera';
    case Printer = 'printer';
    case Server = 'server';
    case Antenna = 'antenna';

    public function label(): string
    {
        return match ($this) {
            self::Camera => 'Caméra',
            self::Printer => 'Imprimante',
            self::Server => 'Serveur',
            self::Antenna => 'Antenne',
        };
    }

    /**
     * Capteurs créés avec l'équipement : le ping pour tous, plus le service
     * propre au type d'équipement.
     *
     * @return list<array{kind: string, name: string, port: int|null, path: string|null}>
     */
    public function defaultSensors(): array
    {
        $ping = ['kind' => 'ping', 'name' => 'Ping', 'port' => null, 'path' => null];

        return match ($this) {
            self::Camera => [$ping,
                ['kind' => 'tcp', 'name' => 'Flux vidéo (RTSP)', 'port' => 554, 'path' => null],
                ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'path' => '/']],
            self::Printer => [$ping, ['kind' => 'tcp', 'name' => "Port d'impression", 'port' => 9100, 'path' => null]],
            self::Antenna => [$ping, ['kind' => 'http', 'name' => 'Interface web', 'port' => 80, 'path' => '/']],
            self::Server => [$ping],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
