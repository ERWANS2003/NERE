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

    /** Pastille de couleur pleine (sans texte). */
    public function dot(): string
    {
        return explode(' ', $this->color())[0];
    }

    /** Étiquette discrète : neutre quand tout va bien, teintée seulement en cas de problème. */
    public function soft(): string
    {
        return match ($this) {
            self::Down => 'bg-red-50 text-red-800 ring-1 ring-inset ring-red-200',
            self::DownAcknowledged => 'bg-red-50/60 text-red-700 ring-1 ring-inset ring-red-100',
            self::Warning => 'bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-200',
            self::Unusual => 'bg-orange-50 text-orange-900 ring-1 ring-inset ring-orange-200',
            self::Up => 'bg-white text-graphite-700 ring-1 ring-inset ring-graphite-200',
            self::Paused => 'bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-200',
            self::Unknown => 'bg-graphite-50 text-graphite-600 ring-1 ring-inset ring-graphite-200',
        };
    }

    /** Bordure gauche des lignes d'alarme. */
    public function accent(): string
    {
        return match ($this) {
            self::Down => 'border-l-red-600',
            self::DownAcknowledged => 'border-l-red-300',
            self::Warning => 'border-l-amber-400',
            self::Unusual => 'border-l-orange-500',
            self::Up => 'border-l-green-600',
            self::Paused => 'border-l-blue-600',
            self::Unknown => 'border-l-graphite-300',
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
