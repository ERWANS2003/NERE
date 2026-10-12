<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;

/**
 * Qui publie quoi :
 *  - l'administrateur général publie pour toute l'entreprise ou un département ;
 *  - un directeur de département publie uniquement pour le(s) département(s)
 *    qu'il dirige (jamais pour « toute l'entreprise ») ;
 *  - les autres employés consultent seulement.
 *
 * Le rôle « manager » évoqué dans la feuille de route n'a pas d'équivalent
 * dans App\Enums\DepartmentRole (user/technician/director) : il n'existe
 * donc pas de workflow de suggestion/validation ici, seulement admin + directeur.
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /** Une annonce désactivée ne reste visible qu'à qui peut la gérer. */
    public function view(User $user, Announcement $announcement): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $announcement->is_active || $this->update($user, $announcement);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->is_super_admin || $this->directsAnyDepartment($user));
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        return $announcement->department_id !== null
            && $user->canManageDepartment($announcement->department_id);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    private function directsAnyDepartment(User $user): bool
    {
        return Department::active()->get()
            ->contains(fn (Department $d) => $user->canManageDepartment($d->id));
    }
}
