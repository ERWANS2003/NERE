<?php

namespace App\Notifications;

use App\Models\MenuCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Menu du personnel : publication d'une semaine, ou rappel avant la clôture
 * aux employés qui n'ont pas encore choisi. Canal "database" uniquement.
 */
class MenuCampaignNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MenuCampaign $campaign,
        private readonly bool $reminder = false,
    ) {}

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
        $closes = $this->campaign->registration_closes_at?->format('d/m à H:i');

        return [
            'category' => 'menu',
            'icon' => $this->reminder ? 'bell' : 'calendar',
            'title' => ($this->reminder ? 'Rappel · ' : 'Menus publiés · ').$this->campaign->title,
            'message' => $this->reminder
                ? "Vous n'avez pas encore choisi vos plats. Clôture le {$closes}."
                : 'Choisissez vos plats'.($closes ? " avant le {$closes}." : '.'),
            'url' => route('menu.registration.show', $this->campaign->link_token),
            'menu_campaign_id' => $this->campaign->id,
        ];
    }
}
