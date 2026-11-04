<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Prévient les administrateurs d'un nouvel avis sur l'intranet. */
class FeedbackReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly Feedback $feedback) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'feedback',
            'icon' => 'megaphone',
            'title' => 'Nouvel avis : '.(Feedback::CATEGORIES[$this->feedback->category] ?? 'Avis'),
            'message' => str($this->feedback->message)->limit(80)->toString(),
            'url' => route('admin.feedback.index'),
            'feedback_id' => $this->feedback->id,
        ];
    }
}
