<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Personne sans compte (ni AD ni intranet) dont le gestionnaire saisit les choix de menus. */
class MenuGuest extends Model
{
    protected $fillable = [
        'name',
        'department',
        'matricule',
        'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function employeeIdentifier(): string
    {
        return 'externe|'.$this->getKey();
    }
}
