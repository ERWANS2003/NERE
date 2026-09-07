<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SlaAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public int $seuil) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'titre' => "Alerte SLA {$this->seuil}% · {$this->ticket->reference}",
            'message' => "Le ticket {$this->ticket->titre} a atteint {$this->seuil}% de son délai de résolution.",
            'ticket_id' => $this->ticket->id,
            'reference' => $this->ticket->reference,
            'evenement' => 'sla_alerte_' . $this->seuil,
        ]);
    }
}
