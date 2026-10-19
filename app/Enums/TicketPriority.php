<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
            self::Urgent => 'Urgente',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $priority) => [$priority->value => $priority->label()])->all();
    }
}
