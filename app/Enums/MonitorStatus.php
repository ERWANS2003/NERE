<?php

namespace App\Enums;

/** États d'un capteur, du plus grave au moins grave (même ordre que PRTG). */
enum MonitorStatus: string
{
    case Down = 'down';
    case DownAcknowledged = 'down_ack';
    case Warning = 'warning';
    case Unusual = 'unusual';
    case Up = 'up';
    case Paused = 'paused';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Down => 'En panne',
            self::DownAcknowledged => 'En panne (pris en compte)',
            self::Warning => 'Avertissement',
            self::Unusual => 'Inhabituel',
            self::Up => 'OK',
            self::Paused => 'En pause',
            self::Unknown => 'Inconnu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Down => 'bg-red-600 text-white',
            self::DownAcknowledged => 'bg-red-300 text-red-950',
            self::Warning => 'bg-amber-400 text-graphite-950',
            self::Unusual => 'bg-orange-500 text-white',
            self::Up => 'bg-green-600 text-white',
            self::Paused => 'bg-blue-600 text-white',
            self::Unknown => 'bg-graphite-400 text-white',
        };
    }

    public function isAlarm(): bool
    {
        return in_array($this, [self::Down, self::DownAcknowledged, self::Warning, self::Unusual], true);
    }

    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }

    /** @param  iterable<self>  $statuses */
    public static function worst(iterable $statuses): self
    {
        $worst = null;
        foreach ($statuses as $status) {
            if ($worst === null || $status->rank() < $worst->rank()) {
                $worst = $status;
            }
        }

        return $worst ?? self::Unknown;
    }
}
