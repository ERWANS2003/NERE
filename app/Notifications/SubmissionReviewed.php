<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Prévient le demandeur que sa demande (soumission de formulaire) a été
 * approuvée ou rejetée.
 */
class SubmissionReviewed extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    /**
     * La notification est enregistrée tout de suite (connexion synchrone)
     * pour apparaître immédiatement dans la cloche.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function __construct(private readonly FormSubmission $submission) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->withMail(['database'], $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approuvee = $this->submission->status === 'approved';

        return [
            'category' => 'submission',
            'icon' => $approuvee ? 'check' : 'x',
            'title' => ($approuvee ? 'Demande approuvée' : 'Demande rejetée')
                .' · '.($this->submission->form?->name ?? ''),
            'message' => $this->submission->review_comment
                ? Str::limit($this->submission->review_comment, 60)
                : ($approuvee ? 'Votre demande a été acceptée.' : 'Votre demande a été refusée.'),
            'url' => route('intranet.submissions.show', $this->submission->id),
            'submission_id' => $this->submission->id,
            'status' => $this->submission->status,
        ];
    }
}
