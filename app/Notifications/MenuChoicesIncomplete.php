<?php

namespace App\Notifications;

use App\Models\MenuCampaign;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Avertit un employé qui n'a pas choisi de plat pour certains jours de la semaine. */
class MenuChoicesIncomplete extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    /**
     * @param  list<string>  $missingDays  libellés des jours sans plat (ex. « mardi 17 novembre »)
     */
    public function __construct(
        private readonly MenuCampaign $campaign,
        private readonly array $missingDays,
    ) {}

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        // Envoi décidé par le gestionnaire pour cette personne : l'e-mail part toujours.
        return filled($notifiable->email ?? null) ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $closes = $this->campaign->registration_closes_at?->format('d/m à H:i');

        return [
            'category' => 'menu',
            'icon' => 'bell',
            'title' => 'Choix incomplets · '.$this->campaign->title,
            'message' => 'Vous n’avez pas choisi de plat pour : '.implode(', ', $this->missingDays).'. '
                .'Si vous ne déjeunez pas ces jours-là, ignorez ce message ; sinon complétez vos choix'
                .($closes ? " avant le {$closes}." : '.'),
            'url' => route('menu.registration.show', $this->campaign->link_token),
            'menu_campaign_id' => $this->campaign->id,
        ];
    }
}
