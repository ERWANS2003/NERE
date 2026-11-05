<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Prévient l'employé de la décision prise sur sa demande de congé
 * (approuvée ou refusée).
 */
class TimeOffReviewed extends Notification implements ShouldQueue
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

    public function __construct(private readonly TimeOffRequest $request) {}

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
        $approuvee = $this->request->status === 'approved';
        $type = $this->request->policy?->name ?? 'Congé';

        return [
            'category' => 'time_off',
            'icon' => $approuvee ? 'check' : 'x',
            'title' => ($approuvee ? 'Congé approuvé' : 'Congé refusé').' · '.$type,
            'message' => $this->request->start_date->format('d/m/Y').' au '.$this->request->end_date->format('d/m/Y')
                .($this->request->approval_comment ? ' · '.Str::limit($this->request->approval_comment, 40) : ''),
            'url' => route('intranet.time-off.index'),
            'time_off_request_id' => $this->request->id,
            'status' => $this->request->status,
        ];
    }
}
