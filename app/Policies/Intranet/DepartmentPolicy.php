<?php

namespace App\Policies\Intranet;

use App\Models\Intranet\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /** Super admin : accès total sans passer par les autres méthodes. */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) return true;
        return null;
    }

    /** Voir la page d'espace département. */
    public function view(User $user, Department $department): bool
    {
        return $department->users()->where('users.id', $user->id)->exists();
    }

    /** Gérer (services, formulaires, équipe) : directeur seulement. */
    public function manage(User $user, Department $department): bool
    {
        return $department->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'director')
            ->exists();
    }
}
