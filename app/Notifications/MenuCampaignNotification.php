<?php

namespace App\Notifications;

use App\Models\MenuCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Menu du personnel : publication d'une semaine, ou rappel avant la clôture
 * aux employés qui n'ont pas encore choisi.
 *
 * Canal database : notif dans la cloche.
 * Canal mail     : activé si $sendMail est vrai.
 */
class MenuCampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MenuCampaign $campaign,
        private readonly bool $reminder = false,
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
        $closes  = $this->campaign->registration_closes_at?->locale('fr')->translatedFormat('d F Y à H:i');
        $portal  = route('menu.registration.show', $this->campaign->link_token);

        // Construction du résumé des menus
        $days = $this->campaign->days()->orderBy('date')->get();
        $menuLines = '';
        foreach ($days as $day) {
            $dateLabel = \Illuminate\Support\Carbon::parse($day->date)->locale('fr')->translatedFormat('l d F');
            $menuLines .= "**{$dateLabel}**\n"
                ."  — Plat 1 : ".e($day->menu_one)."\n"
                ."  — Plat 2 : ".e($day->menu_two)."\n"
                ."  — Dessert : ".e($day->dessert)."\n\n";
        }

        if ($this->reminder) {
            return (new MailMessage)
                ->subject('[NÉRÉ Mining] 🔔 Rappel · Vos choix de menus sont attendus')
                ->greeting('Bonjour '.$notifiable->name.' 👋')
                ->line('Vous n\'avez pas encore enregistré vos choix de plats pour **'.$this->campaign->title.'**.')
                ->when($closes, fn (MailMessage $m) => $m->line('⏰ **Date limite** : '.$closes))
                ->line('---')
                ->line(new \Illuminate\Support\HtmlString(
                    '<pre style="font-family:inherit;font-size:14px;color:#374151;line-height:1.75;white-space:pre-wrap;">'
                    .$menuLines.'</pre>'
                ))
                ->action('Choisir mes plats maintenant', $portal)
                ->line('Après la date de clôture, il ne sera plus possible de modifier vos choix.')
                ->salutation('L\'équipe NÉRÉ Mining');
        }

        return (new MailMessage)
            ->subject('[NÉRÉ Mining] 🍽️ Les menus de la semaine sont publiés')
            ->greeting('Bonjour '.$notifiable->name.' 👋')
            ->line('Les menus de **'.$this->campaign->title.'** sont disponibles. Inscrivez-vous dès maintenant.')
            ->when($closes, fn (MailMessage $m) => $m->line('📅 **Clôture des inscriptions** : '.$closes))
            ->line('---')
            ->line(new \Illuminate\Support\HtmlString(
                '<pre style="font-family:inherit;font-size:14px;color:#374151;line-height:1.75;white-space:pre-wrap;">'
                .$menuLines.'</pre>'
            ))
            ->line('---')
            ->action('Choisir mes plats', $portal)
            ->line('Vous pourrez modifier votre choix jusqu\'à la date de clôture.')
            ->salutation('L\'équipe NÉRÉ Mining');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $closes = $this->campaign->registration_closes_at?->format('d/m à H:i');

        return [
            'category'           => 'menu',
            'icon'               => $this->reminder ? 'bell' : 'calendar',
            'title'              => ($this->reminder ? 'Rappel · ' : 'Menus publiés · ').$this->campaign->title,
            'message'            => $this->reminder
                ? "Vous n'avez pas encore choisi vos plats. Clôture le {$closes}."
                : 'Choisissez vos plats'.($closes ? " avant le {$closes}." : '.'),
            'url'                => route('menu.registration.show', $this->campaign->link_token),
            'menu_campaign_id'   => $this->campaign->id,
        ];
    }
}
