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

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function __construct(private readonly MonitorSensor $sensor, private readonly bool $down) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->withMail(['database'], $notifiable);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $device = $this->sensor->device;

        return [
            'category' => 'supervision',
            'icon' => $this->down ? 'alert-triangle' : 'check',
            'title' => ($this->down ? 'En panne : ' : 'Rétabli : ').$device->name.' · '.$this->sensor->name,
            'message' => $device->host.' ('.$device->site.') : '.$this->sensor->last_message,
            'url' => route('intranet.monitor.sensors.show', $this->sensor),
        ];
    }
}
