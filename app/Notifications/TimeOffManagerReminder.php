<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TimeOffManagerReminder extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

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

    public function __construct(private readonly TimeOffRequest $request) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->withMail(['database'], $notifiable);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $employee = $this->request->user?->name ?? 'Un collaborateur';
        $policy = $this->request->policy?->name ?? 'Congé';

        return [
            'category' => 'time_off',
            'icon' => 'clock',
            'title' => 'Demande de congé en attente depuis 48 h',
            'message' => "{$employee} · {$policy} · ".$this->period(),
            'url' => route('intranet.time-off.approvals'),
            'time_off_request_id' => $this->request->id,
            'status' => $this->request->status,
        ];
    }

    private function period(): string
    {
        return $this->request->start_date->format('d/m/Y').' au '.$this->request->end_date->format('d/m/Y');
    }
}
