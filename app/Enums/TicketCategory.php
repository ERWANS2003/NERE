<?php

namespace App\Enums;

enum TicketCategory: string
{
    case Hardware = 'hardware';
    case Software = 'software';
    case Access = 'access';
    case Network = 'network';
    case Backup = 'backup';
    case Security = 'security';
    case Email = 'email';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hardware => 'Matériel',
            self::Software => 'Logiciel',
            self::Access => 'Accès et comptes',
            self::Network => 'Réseau',
            self::Backup => 'Sauvegarde et restauration',
            self::Security => 'Sécurité informatique',
            self::Email => 'Messagerie',
            self::Other => 'Autre',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $category) => [$category->value => $category->label()])->all();
    }
}
