<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le public cible lors de la création d'un événement au calendrier.
 *
 * Canal database : visible dans la cloche dès la création (connexion synchrone).
 * Canal mail     : activé uniquement si $sendMail est vrai.
 */
class CalendarEventPublished extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function __construct(
        private readonly CalendarEvent $event,
        private readonly bool $sendMail = false,
    ) {}

    /** @return list<string> */
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
        $config   = config('intranet.calendar.event_types.'.$this->event->type, []);
        $typeLabel = $config['name'] ?? ucfirst($this->event->type);
        $dept      = $this->event->department?->name;

        $dateFormatted = $this->event->event_date->locale('fr')->translatedFormat('l d F Y');
        $timeFormatted = $this->event->all_day
            ? 'Journée entière'
            : ($this->event->event_time ? substr((string) $this->event->event_time, 0, 5) : '');

        $intro = $dept
            ? "Un événement a été ajouté à l'agenda du département **{$dept}**."
            : "Un événement a été ajouté à l'agenda de l'entreprise.";

        return (new MailMessage)
            ->subject('[NÉRÉ Mining] 📅 Nouvel événement · '.$this->event->title)
            ->greeting('Bonjour '.$notifiable->name.' 👋')
            ->line($intro)
            ->line('---')
            ->line('### '.$this->event->title)
            ->line('📅 **Date** : '.$dateFormatted.($timeFormatted ? ' · '.$timeFormatted : ''))
            ->line('🏷️ **Type** : '.$typeLabel.($dept ? ' · '.$dept : ''))
            ->when(filled($this->event->description), function (MailMessage $m) {
                $desc = nl2br(e($this->event->description));
                return $m->line(new \Illuminate\Support\HtmlString(
                    '<p style="color:#374151;margin:16px 0;line-height:1.65;">'.$desc.'</p>'
                ));
            })
            ->line('---')
            ->action('Voir l\'agenda', route('intranet.calendar').'?date='.$this->event->event_date->format('Y-m-d'))
            ->line('Ajouté par **'.$this->event->creator?->name.'**.')
            ->line('Pour ne plus recevoir ces notifications, gérez vos préférences dans votre profil.')
            ->salutation('L\'équipe NÉRÉ Mining');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $config    = config('intranet.calendar.event_types.'.$this->event->type, []);
        $typeLabel = $config['name'] ?? ucfirst($this->event->type);

        return [
            'category'         => 'calendar',
            'icon'             => 'calendar',
            'title'            => 'Nouvel événement · '.$this->event->title,
            'message'          => $typeLabel.' · '.$this->event->event_date->locale('fr')->translatedFormat('d F Y'),
            'url'              => route('intranet.calendar').'?date='.$this->event->event_date->format('Y-m-d'),
            'calendar_event_id' => $this->event->id,
        ];
    }
}
