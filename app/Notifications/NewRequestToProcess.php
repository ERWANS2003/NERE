<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Models\TimeOffRequest;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Prévient ceux qui doivent traiter une nouvelle demande : formulaire d'un
 * département ou demande de congé. Canal "database" (et push navigateur).
 */
class NewRequestToProcess extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    public function __construct(private readonly FormSubmission|TimeOffRequest $request) {}

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
        $employee = $this->request->user?->name ?? 'Un employé';

        if ($this->request instanceof TimeOffRequest) {
            return [
                'category' => 'time_off',
                'icon' => 'calendar',
                'title' => 'Nouvelle demande de congé',
                'message' => $employee.' · '.($this->request->policy?->name ?? 'Congé')
                    .' · du '.$this->request->start_date->format('d/m').' au '.$this->request->end_date->format('d/m'),
                'url' => route('intranet.time-off.approvals'),
                'time_off_request_id' => $this->request->id,
            ];
        }

        return [
            'category' => 'submission',
            'icon' => 'bell',
            'title' => 'Nouvelle demande : '.($this->request->form?->name ?? 'formulaire'),
            'message' => $employee.' · '.($this->request->form?->department?->name ?? ''),
            'url' => route('intranet.submissions.show', $this->request->id),
            'submission_id' => $this->request->id,
        ];
    }
}
