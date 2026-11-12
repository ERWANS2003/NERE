<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alerte les administrateurs d'un nouveau problème technique (page Santé).
 * Canal "database" seul : il doit arriver même quand l'e-mail est en panne.
 */
class SystemHealthAlert extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    /**
     * @param  array<string, string>  $problems
     */
    public function __construct(private readonly array $problems) {}

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
