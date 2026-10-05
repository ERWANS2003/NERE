<?php

namespace App\Policies\Intranet;

use App\Models\Intranet\Submission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubmissionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) return true;
        return null;
    }

    /* ── Helpers internes ─────────────────────────────────────── */

    private function isStaff(User $user, Submission $sub): bool
    {
        return $sub->form->service->department
            ->users()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['technician', 'director'])
            ->exists();
    }

    private function isDirector(User $user, Submission $sub): bool
    {
        return $sub->form->service->department
            ->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'director')
            ->exists();
    }

    /* ── Abilities ────────────────────────────────────────────── */

    /** Voir la fiche de la demande. */
    public function view(User $user, Submission $sub): bool
    {
        return $sub->requester_id === $user->id
            || $sub->assignee_id  === $user->id
            || $this->isStaff($user, $sub);
    }

    /** Agir sur la demande (changer statut, etc.) : staff ou requester pour annulation. */
    public function act(User $user, Submission $sub): bool
    {
        return $this->isStaff($user, $sub)
            || $sub->requester_id === $user->id;
    }

    /** Assigner à un technicien : directeur uniquement. */
    public function assign(User $user, Submission $sub): bool
    {
        return $this->isDirector($user, $sub);
    }

    /** Commenter : tout membre qui peut voir. */
    public function comment(User $user, Submission $sub): bool
    {
        return $this->view($user, $sub);
    }

    /** Poster un commentaire interne : staff seulement. */
    public function commentInternal(User $user, Submission $sub): bool
    {
        return $this->isStaff($user, $sub);
    }

    /** Escalader : staff seulement. */
    public function escalate(User $user, Submission $sub): bool
    {
        return $this->isStaff($user, $sub);
    }

    /** Annuler : demandeur (statuts précoces) ou admin géré par before(). */
    public function cancel(User $user, Submission $sub): bool
    {
        return $sub->requester_id === $user->id
            && in_array($sub->status, [
                Submission::STATUS_DRAFT,
                Submission::STATUS_SUBMITTED,
            ]);
    }

    /** Valider / rejeter (workflow validation) : directeur. */
    public function validate(User $user, Submission $sub): bool
    {
        return $this->isDirector($user, $sub);
    }
}
