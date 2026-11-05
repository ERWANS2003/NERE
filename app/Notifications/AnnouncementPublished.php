<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifie le public d'une annonce (toute l'entreprise ou un département)
 * lors de sa publication.
 */
class AnnouncementPublished extends Notification implements ShouldQueue
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

    public function __construct(private readonly Announcement $announcement) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
                ? 'Annonce · '.$this->announcement->department->name
                : "Annonce · Toute l'entreprise",
            'url' => route('intranet.announcements.show', $this->announcement->id),
            'announcement_id' => $this->announcement->id,
            'priority' => $this->announcement->priority,
        ];
    }
}
