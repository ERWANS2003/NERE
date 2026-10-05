<?php

namespace App\Enums;

enum DepartmentRole: string
{
    case User = 'user';
    case Technician = 'technician';
    case Director = 'director';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur',
            self::Technician => 'Technicien',
            self::Director => 'Directeur',
        };
    }

    /** Le directeur herite des droits technicien dans son departement. */
    public function canHandleSubmissions(): bool
    {
        return $this === self::Technician || $this === self::Director;
    }

    /** Seul le directeur (ou l'admin general) valide, rejette et assigne. */
    public function canValidate(): bool
    {
        return $this === self::Director;
    }

    /** Seul le directeur gere services, formulaires et equipe. */
    public function canManageDepartment(): bool
    {
        return $this === self::Director;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
