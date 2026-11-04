<?php

namespace App\Notifications;

use App\Models\FormSubmission;
use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient ceux qui doivent traiter une nouvelle demande : formulaire d'un
 * département ou demande de congé. Canal "database" (et push navigateur).
 */
class NewRequestToProcess extends Notification
{
    use Queueable;

    public function __construct(private readonly FormSubmission|TimeOffRequest $request) {}

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
