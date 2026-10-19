<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::InProgress => 'En cours',
            self::Waiting => 'En attente',
            self::Resolved => 'Résolu',
            self::Closed => 'Fermé',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
