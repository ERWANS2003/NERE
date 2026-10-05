<?php

namespace App\Notifications\Intranet;

use App\Models\Intranet\Submission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Submission $submission,
        public readonly User       $actor,
        public readonly string     $fromStatus,
        public readonly string     $toStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $labels = [
            'soumise'       => 'Soumise',
            'en_validation' => 'En validation',
            'assignee'      => 'Assignée',
            'en_cours'      => 'En cours',
            'en_attente'    => 'En attente',
            'escaladee'     => 'Escaladée',
            'resolue'       => 'Résolue',
            'cloturee'      => 'Clôturée',
            'rejetee'       => 'Rejetée',
            'annulee'       => 'Annulée',
        ];
        $toLabel = $labels[$this->toStatus] ?? $this->toStatus;

        return (new MailMessage)
            ->subject("Demande {$this->submission->reference} — {$toLabel}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La demande **{$this->submission->reference}** ({$this->submission->form->name}) a changé de statut.")
            ->line("Nouveau statut : **{$toLabel}**")
            ->line("Effectué par : {$this->actor->name}")
            ->action('Voir la demande', route('intranet.submissions.show', $this->submission))
            ->line('Cordialement, Intranet Néré Mining');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id'  => $this->submission->id,
            'reference'      => $this->submission->reference,
            'from_status'    => $this->fromStatus,
            'to_status'      => $this->toStatus,
            'actor_name'     => $this->actor->name,
            'url'            => route('intranet.submissions.show', $this->submission),
        ];
    }
}
