<?php

namespace App\Notifications;

use App\Models\MonitorSensor;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MonitorAlert extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    private readonly string $title;

    private readonly string $message;

    private readonly string $url;

    /** @param  'down'|'still_down'|'escalated'|'recovered'  $kind */
    public function __construct(MonitorSensor $sensor, public readonly string $kind)
    {
        // Le texte est figé à l'envoi : la notification part en file d'attente.
        $device = $sensor->device;
        $this->title = match ($kind) {
            'down' => 'En panne : ',
            'still_down' => 'Toujours en panne : ',
            'escalated' => 'Panne non prise en compte : ',
            'recovered' => 'Rétabli : ',
        }.$device->name.' · '.$sensor->name;
        $duration = $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE);
        $this->message = $device->host.' : '.$sensor->last_message.match ($kind) {
            'still_down', 'escalated' => " (depuis {$duration})",
            default => '',
        };
        $this->url = route('intranet.monitor.sensors.show', $sensor);
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->withMail(['database'], $notifiable);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'supervision',
            'icon' => $this->kind === 'recovered' ? 'check' : 'alert-triangle',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
