<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Down = 'down';
    case Warning = 'warning';
    case Up = 'up';
    case Paused = 'paused';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Down => 'En panne',
            self::Warning => 'Avertissement',
            self::Up => 'OK',
            self::Paused => 'En pause',
            self::Unknown => 'Inconnu',
        };
    }

    /** Couleur pleine, comme les pastilles d'état de PRTG. */
    public function color(): string
    {
        return match ($this) {
            self::Down => 'bg-red-600 text-white',
            self::Warning => 'bg-amber-400 text-graphite-950',
            self::Up => 'bg-green-600 text-white',
            self::Paused => 'bg-blue-600 text-white',
            self::Unknown => 'bg-graphite-400 text-white',
        };
    }

    /** Plus le chiffre est petit, plus l'état est grave. */
    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }

    /** @param  iterable<self>  $statuses */
    public static function worst(iterable $statuses): self
    {
        $worst = self::Unknown;
        $seen = false;
        foreach ($statuses as $status) {
            if (! $seen || $status->rank() < $worst->rank()) {
                $worst = $status;
                $seen = true;
            }
        }

        return $worst;
    }
}
