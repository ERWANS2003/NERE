<?php

namespace App\Policies\Intranet;

use App\Models\Intranet\Form;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) return true;
        return null;
    }

    /** Voir le formulaire (page de remplissage). */
    public function view(User $user, Form $form): bool
    {
        if (! $form->isPublished()) return false;

        return $form->service->department
            ->users()->where('users.id', $user->id)->exists();
    }

    /** Soumettre le formulaire : tout membre du département. */
    public function submitTo(User $user, Form $form): bool
    {
        return $this->view($user, $form);
    }

    /** Modifier / publier le formulaire : directeur du département. */
    public function update(User $user, Form $form): bool
    {
        return $form->service->department
            ->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'director')
            ->exists();
    }
}
