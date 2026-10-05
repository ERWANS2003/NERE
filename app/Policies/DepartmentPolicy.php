<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Tout utilisateur authentifie voit la page d'accueil. Les cartes qu'il a
     * le droit de voir sont filtrees en base par Department::scopeVisibleTo(),
     * pas ici : une policy repond a « cette carte est-elle accessible », pas
     * « quelles cartes existe-t-il ».
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Department $department): bool
    {
        return $user->is_active && $user->isMemberOf($department->getKey());
    }

    /** Seuls les administrateurs gerent le referentiel des departements. */
    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, Department $department): bool
    {
        return $user->is_super_admin;
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->is_super_admin;
    }
}
