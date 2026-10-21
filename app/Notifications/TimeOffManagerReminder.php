<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffManagerReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly TimeOffRequest $request) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->request->user?->name ?? 'Un collaborateur';
        $policy = $this->request->policy?->name ?? 'Congé';

        return (new MailMessage)
            ->subject("Rappel : demande de {$policy} en attente depuis 48 h")
            ->greeting('Bonjour '.$notifiable->first_name.',')
            ->line("La demande de {$policy} de {$employee} attend une décision depuis 48 heures.")
            ->line('Période : '.$this->period())
            ->action('Traiter les demandes', route('intranet.time-off.approvals'));
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
