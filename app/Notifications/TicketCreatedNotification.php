<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public string $evenement = 'ticket_cree') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $action = $this->evenement === 'ticket_assigne' ? 'a été affecté à votre équipe' : 'a été créé';

        return new DatabaseMessage([
            'titre' => "Ticket {$this->ticket->reference}",
            'message' => "{$this->ticket->titre} {$action}.",
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'evenement' => $this->evenement,
        ]);
    }
}
