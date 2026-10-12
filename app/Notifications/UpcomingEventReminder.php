<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rappel d'un événement du calendrier qui approche (envoyé la veille par la
 * commande intranet:rappels-evenements). Canal "database" uniquement : un
 * rappel interne ne justifie pas un e-mail.
 */
class UpcomingEventReminder extends Notification
{
    use Queueable;

    public function __construct(private readonly CalendarEvent $event)
    {
    }

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
        $heure = $this->event->all_day || ! $this->event->event_time
            ? 'toute la journée'
            : \Illuminate\Support\Carbon::parse($this->event->event_time)->format('H:i');

        return [
            'category' => 'event',
            'icon' => 'calendar',
            'title' => 'Rappel · ' . $this->event->title,
            'message' => 'Demain, ' . $heure
                . ($this->event->department ? ' · ' . $this->event->department->name : ''),
            'url' => route('intranet.calendar', ['date' => $this->event->event_date->toDateString()]),
            'event_id' => $this->event->id,
        ];
    }
}
