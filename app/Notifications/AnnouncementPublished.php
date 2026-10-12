<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le public d'une annonce (toute l'entreprise ou un département)
 * lors de sa publication.
 *
 * Les annonces urgentes partent aussi par e-mail : les autres restent sur le
 * canal "database" (badge de l'en-tête), pour ne pas noyer les boîtes mail
 * sous des communications routinières.
 */
class AnnouncementPublished extends Notification
{
    use Queueable;

    public function __construct(private readonly Announcement $announcement)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->announcement->priority === 'urgent'
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Urgent] ' . $this->announcement->title)
            ->greeting('Bonjour ' . $notifiable->first_name . ',')
            ->line("Une annonce urgente vient d'être publiée sur l'intranet NÉRÉ Mining :")
            ->line('**' . $this->announcement->title . '**')
            ->action("Lire l'annonce", route('intranet.announcements.show', $this->announcement->id))
            ->line("Merci d'en prendre connaissance rapidement.");
    }

    /**
     * Format `data` normalisé partagé par toutes les notifications intranet :
     * category / title / message / url / icon permettent au dropdown de
     * l'en-tête d'afficher n'importe quel type sans connaître l'émetteur.
     * Les clés spécifiques (announcement_id, priority) restent disponibles
     * pour le marquage ciblé.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'announcement',
            'icon' => 'megaphone',
            'title' => $this->announcement->title,
            'message' => $this->announcement->department?->name
                ? 'Annonce · ' . $this->announcement->department->name
                : "Annonce · Toute l'entreprise",
            'url' => route('intranet.announcements.show', $this->announcement->id),
            'announcement_id' => $this->announcement->id,
            'priority' => $this->announcement->priority,
        ];
    }
}
