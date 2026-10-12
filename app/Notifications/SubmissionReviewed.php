<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient le demandeur que sa demande (soumission de formulaire) a été
 * approuvée ou rejetée. Canal e-mail systématique : contrairement à une
 * annonce de masse, c'est une réponse personnelle attendue.
 */
class SubmissionReviewed extends Notification
{
    use Queueable;

    public function __construct(private readonly FormSubmission $submission)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approuvee = $this->submission->status === 'approved';
        $formulaire = $this->submission->form?->name ?? 'Votre demande';

        $mail = (new MailMessage)
            ->subject(($approuvee ? '[Approuvée] ' : '[Rejetée] ') . $formulaire)
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Votre demande « {$formulaire} » a été " . ($approuvee ? 'approuvée.' : 'rejetée.'));

        if ($this->submission->review_comment) {
            $mail->line('Commentaire : ' . $this->submission->review_comment);
        }

        return $mail->action('Voir la demande', route('intranet.submissions.show', $this->submission->id));
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
                . ' · ' . ($this->submission->form?->name ?? ''),
            'message' => $this->submission->review_comment
                ? \Illuminate\Support\Str::limit($this->submission->review_comment, 60)
                : ($approuvee ? 'Votre demande a été acceptée.' : 'Votre demande a été refusée.'),
            'url' => route('intranet.submissions.show', $this->submission->id),
            'submission_id' => $this->submission->id,
            'status' => $this->submission->status,
        ];
    }
}
