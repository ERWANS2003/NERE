<?php

namespace App\Notifications\Intranet;

use App\Models\Intranet\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionSlaBreach extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Submission $submission,
        public readonly bool       $isWarning = false, // true = 75%, false = dépassé
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isWarning
            ? "SLA à 75% — {$this->submission->reference}"
            : "SLA DÉPASSÉ — {$this->submission->reference}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Attention {$notifiable->name},")
            ->line($this->isWarning
                ? "La demande {$this->submission->reference} approche son délai de résolution (75% écoulé)."
                : "La demande {$this->submission->reference} a dépassé son délai de résolution.")
            ->action('Traiter la demande', route('intranet.submissions.show', $this->submission))
            ->line('Intranet Néré Mining');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'reference'     => $this->submission->reference,
            'is_warning'    => $this->isWarning,
            'due_at'        => $this->submission->due_at?->toIso8601String(),
            'url'           => route('intranet.submissions.show', $this->submission),
        ];
    }
}
