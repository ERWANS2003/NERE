<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketSlaNotification extends Notification
{
    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $metric,
        private readonly string $stage,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $metricLabel = $this->metric === 'response' ? 'première réponse' : 'résolution';
        $stageLabel = $this->stage === 'warning' ? 'à risque' : 'dépassé';
        $title = "SLA {$metricLabel} {$stageLabel} · {$this->ticket->number}";
        $message = $this->stage === 'warning'
            ? "Le délai de {$metricLabel} de ce ticket atteint 75 %."
            : "Le délai de {$metricLabel} de ce ticket est dépassé.";

        return [
            'category' => 'ticket',
            'icon' => 'alert-triangle',
            'title' => $title,
            'message' => $message,
            'url' => route('intranet.tickets.show', $this->ticket),
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'sla_metric' => $this->metric,
            'sla_stage' => $this->stage,
        ];
    }
}
