<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * L'e-mail part en file d'attente : un serveur SMTP lent ou injoignable ne
     * bloque plus l'action de l'utilisateur. La notification intranet reste
     * immédiate (connexion synchrone) pour apparaître tout de suite dans la cloche.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $title,
        private readonly string $message,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title.' · '.$this->ticket->number)
            ->greeting('Bonjour '.$notifiable->first_name.',')
            ->line($this->message)
            ->line('Ticket : '.$this->ticket->subject)
            ->action('Voir le ticket', route('intranet.tickets.show', $this->ticket));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'ticket',
            'icon' => 'check',
            'title' => $this->title.' · '.$this->ticket->number,
            'message' => $this->message,
            'url' => route('intranet.tickets.show', $this->ticket),
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
        ];
    }
}
