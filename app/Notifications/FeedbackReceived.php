<?php

namespace App\Notifications;

use App\Models\Feedback;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Prévient les administrateurs d'un nouvel avis sur l'intranet. */
class FeedbackReceived extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    public function __construct(private readonly Feedback $feedback) {}

    /**
     * La notification intranet est enregistrée tout de suite (connexion
     * synchrone) ; l'e-mail part en file d'attente pour ne jamais ralentir
     * ni bloquer l'action de l'utilisateur.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->withMail(['database'], $notifiable);
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
