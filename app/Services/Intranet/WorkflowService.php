<?php

namespace App\Services\Intranet;

use App\Models\Intranet\Submission;
use App\Models\Intranet\SubmissionEvent;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Intranet\SubmissionStatusChanged;

/**
 * Service dédié aux transitions de workflow d'une soumission intranet.
 *
 * Toutes les transitions autorisées par rôle sont vérifiées via les
 * Policies (déjà appelées depuis le contrôleur). Ce service ne fait
 * que persister l'état, enregistrer l'événement et déclencher les
 * notifications.
 */
class WorkflowService
{
    /** Matrice des transitions autorisées. */
    private const TRANSITIONS = [
        Submission::STATUS_DRAFT       => [Submission::STATUS_SUBMITTED, Submission::STATUS_CANCELLED],
        Submission::STATUS_SUBMITTED   => [Submission::STATUS_VALIDATING, Submission::STATUS_ASSIGNED, Submission::STATUS_CANCELLED],
        Submission::STATUS_VALIDATING  => [Submission::STATUS_ASSIGNED, Submission::STATUS_REJECTED],
        Submission::STATUS_ASSIGNED    => [Submission::STATUS_IN_PROGRESS, Submission::STATUS_ESCALATED, Submission::STATUS_CANCELLED],
        Submission::STATUS_IN_PROGRESS => [Submission::STATUS_WAITING, Submission::STATUS_ESCALATED, Submission::STATUS_RESOLVED],
        Submission::STATUS_WAITING     => [Submission::STATUS_IN_PROGRESS, Submission::STATUS_CANCELLED],
        Submission::STATUS_ESCALATED   => [Submission::STATUS_IN_PROGRESS, Submission::STATUS_RESOLVED],
        Submission::STATUS_RESOLVED    => [Submission::STATUS_CLOSED, Submission::STATUS_IN_PROGRESS],
        // Terminaux : aucune transition sortante
        Submission::STATUS_CLOSED    => [],
        Submission::STATUS_REJECTED  => [],
        Submission::STATUS_CANCELLED => [],
    ];

    /** Vérifie si la transition est autorisée selon la machine d'états. */
    public function canTransition(Submission $sub, string $toStatus): bool
    {
        return in_array($toStatus, self::TRANSITIONS[$sub->status] ?? [], true);
    }

    /**
     * Applique la transition, enregistre l'événement, notifie.
     *
     * @throws \InvalidArgumentException si la transition est invalide.
     */
    public function transition(Submission $sub, string $toStatus, User $actor, array $payload = []): Submission
    {
        throw_unless(
            $this->canTransition($sub, $toStatus),
            \InvalidArgumentException::class,
            "Transition invalide : {$sub->status} → {$toStatus}"
        );

        $from = $sub->status;

        $sub->update([
            'status'    => $toStatus,
            'closed_at' => in_array($toStatus, Submission::TERMINAL_STATUSES) ? now() : null,
        ]);

        SubmissionEvent::create([
            'submission_id' => $sub->id,
            'actor_id'      => $actor->id,
            'action'        => 'status_changed',
            'payload'       => array_merge(['from' => $from, 'to' => $toStatus], $payload),
        ]);

        // Notifications asynchrones via queue
        $this->notify($sub, $actor, $from, $toStatus);

        return $sub->fresh(['form.service.department', 'requester', 'assignee', 'currentStep']);
    }

    /* ── Notifications ────────────────────────────────────────── */

    private function notify(Submission $sub, User $actor, string $from, string $to): void
    {
        // Notifier le demandeur sur tout changement visible
        $recipients = collect([$sub->requester]);

        // Notifier l'assigné si présent
        if ($sub->assignee && $sub->assignee_id !== $actor->id) {
            $recipients->push($sub->assignee);
        }

        // Sur assignation, notifier le nouveau assigné
        if ($to === Submission::STATUS_ASSIGNED && $sub->assignee) {
            $recipients->push($sub->assignee);
        }

        $recipients = $recipients->unique('id')->filter(fn ($u) => $u && $u->id !== $actor->id);

        if ($recipients->isEmpty()) return;

        Notification::send(
            $recipients,
            new SubmissionStatusChanged($sub, $actor, $from, $to)
        );
    }
}
