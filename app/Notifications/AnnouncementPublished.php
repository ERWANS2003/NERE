<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le public d'une annonce (toute l'entreprise ou un département)
 * lors de sa publication.
 *
 * Si $sendMail est vrai, un e-mail riche est envoyé en plus de la notification
 * base de données.
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

    public function __construct(
        private readonly Announcement $announcement,
        private readonly bool $sendMail = false,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($this->sendMail && filled($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $audience = $this->announcement->department?->name
            ? 'Département · '.$this->announcement->department->name
            : "Toute l'entreprise";

        $priority = match ($this->announcement->priority) {
            'urgent'    => '🔴 URGENT',
            'important' => '🟡 Important',
            default     => 'ℹ️ Information',
        };

        $body = nl2br(e($this->announcement->body));

        return (new MailMessage)
            ->subject('[NÉRÉ Mining] '.$priority.' · '.$this->announcement->title)
            ->greeting('Bonjour '.$notifiable->name.' 👋')
            ->line($priority.' — '.$audience)
            ->line('---')
            ->line('**'.$this->announcement->title.'**')
            ->line(new \Illuminate\Support\HtmlString('<p style="color:#374151;margin:16px 0;line-height:1.65;">'.$body.'</p>'))
            ->line('---')
            ->action('Lire l\'annonce complète', route('intranet.announcements.show', $this->announcement))
            ->line('Publié par **'.$this->announcement->creator?->name.'** le **'
                .$this->announcement->published_at?->locale('fr')->translatedFormat('d F Y à H:i').'**.')
            ->line('Pour gérer vos préférences de notification, rendez-vous dans votre profil intranet.')
            ->salutation('L\'équipe NÉRÉ Mining');
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
