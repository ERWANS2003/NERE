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

        return $announcement->is_active || $this->update($user, $announcement);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->is_super_admin;
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->is_super_admin;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
