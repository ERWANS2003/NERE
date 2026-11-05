<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

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

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $title,
        private readonly string $message,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
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
