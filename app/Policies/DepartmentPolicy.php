<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /** Les chefs et les administrateurs systeme peuvent consulter le referentiel. */
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->is_super_admin || $user->isDepartmentHead());
    }

    public function view(User $user, Department $department): bool
    {
        return $user->is_active && ($user->is_super_admin || $user->isDepartmentHead());
    }

    /** Seuls les administrateurs gerent le referentiel des departements. */
    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, Department $department): bool
    {
        return $user->is_active
            && ($user->is_super_admin || $user->canManageDepartment($department->getKey()));
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->is_super_admin;
    }
}
