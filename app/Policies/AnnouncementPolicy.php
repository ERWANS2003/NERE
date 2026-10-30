<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

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

        if ($this->update($user, $announcement)) {
            return true;
        }

        return $announcement->is_active
            && ($announcement->department_id === null || $user->roleIn($announcement->department_id) !== null);
    }

    public function create(User $user): bool
    {
        return $user->is_active && ($user->is_super_admin || $user->isDepartmentHead());
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->is_super_admin
            || ($announcement->created_by === $user->id && $user->isDepartmentHead());
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
