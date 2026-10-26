<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Prévient l'employé de la décision prise sur sa demande de congé
 * (approuvée ou refusée). Canal e-mail systématique : réponse personnelle.
 */
class TimeOffReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * L'e-mail part en file d'attente : un serveur SMTP lent ou injoignable ne
     * bloque plus l'action de l'utilisateur. La notification intranet reste
     * immédiate (connexion synchrone) pour apparaître tout de suite dans la cloche.
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
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approuvee = $this->request->status === 'approved';
        $type = $this->request->policy?->name ?? 'Congé';
        $periode = $this->request->start_date->format('d/m/Y').' au '.$this->request->end_date->format('d/m/Y');

        $mail = (new MailMessage)
            ->subject(($approuvee ? '[Approuvé] ' : '[Refusé] ').$type)
            ->greeting('Bonjour '.$notifiable->first_name.',')
            ->line("Votre demande de « {$type} » du {$periode} a été ".($approuvee ? 'approuvée.' : 'refusée.'));

        if ($this->request->approval_comment) {
            $mail->line('Commentaire : '.$this->request->approval_comment);
        }

        return $mail->action('Voir mes congés', route('intranet.time-off.index'));
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
