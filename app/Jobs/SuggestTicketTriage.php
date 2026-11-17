<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketAiTriage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SuggestTicketTriage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $backoff = 300;

    public int $timeout;

    public function __construct(public int $ticketId)
    {
        $this->onQueue(config('services.ticket_ai.queue'));
        $this->timeout = config('services.ticket_ai.timeout') + 60;
    }

    public function handle(TicketAiTriage $triage): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (! $ticket || ! TicketAiTriage::enabled()) {
            return;
        }

        $suggestion = $triage->suggest($ticket);
        if ($suggestion !== null) {
            $ticket->forceFill(['ai_suggestion' => $suggestion, 'ai_suggested_at' => now()])->saveQuietly();
        }
    }
}
