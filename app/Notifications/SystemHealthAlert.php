<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerte les administrateurs d'un nouveau problème technique (page Santé).
 * Canal "database" seul : il doit arriver même quand l'e-mail est en panne.
 */
class SystemHealthAlert extends Notification
{
    use Queueable;

    /**
     * @param  array<string, string>  $problems
     */
    public function __construct(private readonly array $problems) {}

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
        return [
            'category' => 'system_health',
            'icon' => 'x',
            'title' => count($this->problems) > 1
                ? count($this->problems).' problèmes sur le serveur'
                : 'Problème sur le serveur',
            'message' => implode(' ', $this->problems),
            'url' => route('admin.health'),
            'problems' => array_keys($this->problems),
        ];
    }
}
