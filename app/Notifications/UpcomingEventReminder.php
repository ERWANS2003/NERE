<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Rappel d'un événement du calendrier qui approche (envoyé la veille par la
 * commande intranet:rappels-evenements). Canal "database" uniquement : un
 * rappel interne ne justifie pas un e-mail.
 */
class UpcomingEventReminder extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    public function __construct(private readonly CalendarEvent $event) {}

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
        $heure = $this->event->all_day || ! $this->event->event_time
            ? 'toute la journée'
            : Carbon::parse($this->event->event_time)->format('H:i');

        return [
            'category' => 'event',
            'icon' => 'calendar',
            'title' => 'Rappel · '.$this->event->title,
            'message' => 'Demain, '.$heure
                .($this->event->department ? ' · '.$this->event->department->name : ''),
            'url' => route('intranet.calendar', ['date' => $this->event->event_date->toDateString()]),
            'event_id' => $this->event->id,
        ];
    }
}
