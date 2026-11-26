<?php

namespace App\Enums;

enum MonitorSensorKind: string
{
    case Ping = 'ping';
    case Tcp = 'tcp';
    case Http = 'http';

    public function label(): string
    {
        return match ($this) {
            self::Ping => 'Ping',
            self::Tcp => 'Port TCP',
            self::Http => 'Page web (HTTP/HTTPS)',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])->all();
    }
}
