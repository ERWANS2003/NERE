<?php

namespace App\Enums;

enum TicketType: string
{
    case Incident = 'incident';
    case Demande = 'demande';

    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident',
            self::Demande => 'Demande',
        };
    }
}
