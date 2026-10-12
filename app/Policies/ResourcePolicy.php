<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Resource;
use App\Models\User;

/**
 * Qui gère la bibliothèque de ressources :
 *  - tout employé actif consulte et télécharge ;
 *  - l'administrateur général ajoute/retire n'importe quelle ressource ;
 *  - un directeur de département ajoute une ressource (rattachée à un
 *    département qu'il dirige) et ne peut retirer que celles de ses
 *    départements.
 *
 * Même modèle de droits que les annonces : pas de rôle « contributeur »
 * intermédiaire dans App\Enums\DepartmentRole, donc admin + directeur.
 */
class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Resource $resource): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $resource->is_active || $this->manage($user, $resource);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->is_super_admin || $this->directsAnyDepartment($user));
    }

    public function update(User $user, Resource $resource): bool
    {
        return $this->manage($user, $resource);
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $this->manage($user, $resource);
    }

    /**
     * Un directeur gère les ressources d'un département qu'il dirige.
     * Une ressource « toute l'entreprise » (department_id null) reste
     * réservée à l'administrateur général.
     */
    private function manage(User $user, Resource $resource): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        return $resource->department_id !== null
            && $user->canManageDepartment($resource->department_id);
    }

    private function directsAnyDepartment(User $user): bool
    {
        return Department::active()->get()
            ->contains(fn (Department $d) => $user->canManageDepartment($d->id));
    }
}
