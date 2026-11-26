<?php

namespace App\Notifications;

use App\Models\MonitorSensor;
use App\Notifications\Concerns\SendsMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alertes de la supervision, regroupées : une seule notification (et un seul
 * e-mail) par passage du contrôle, quel que soit le nombre de capteurs touchés.
 */
class MonitorAlert extends Notification implements ShouldQueue
{
    use Queueable, SendsMail;

    public const LABELS = [
        'down' => 'En panne',
        'still_down' => 'Toujours en panne',
        'escalated' => 'Panne non prise en compte',
        'recovered' => 'Rétabli',
    ];

    /** @var list<array{kind: string, title: string, message: string, url: string}> */
    public readonly array $items;

    /** @param  list<array{0: MonitorSensor, 1: string}>  $alerts  paires [capteur, type d'alerte] */
    public function __construct(array $alerts)
    {
        // Le texte est figé à l'envoi : la notification part en file d'attente.
        $this->items = array_map(function (array $alert) {
            [$sensor, $kind] = $alert;
            $device = $sensor->device;
            $since = $sensor->status_changed_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE);

            return [
                'kind' => $kind,
                'title' => self::LABELS[$kind].' : '.$device->name.' · '.$sensor->name,
                'message' => $device->host.' : '.$sensor->last_message.(in_array($kind, ['still_down', 'escalated'], true) ? " (depuis {$since})" : ''),
                'url' => route('intranet.monitor.sensors.show', $sensor),
            ];
        }, $alerts);
    }

    /** @return list<string> */
    public function kinds(): array
    {
        return array_column($this->items, 'kind');
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
        if (count($this->items) === 1) {
            $item = $this->items[0];

            return [
                'category' => 'supervision',
                'icon' => $item['kind'] === 'recovered' ? 'check' : 'alert-triangle',
                'title' => $item['title'],
                'message' => $item['message'],
                'url' => $item['url'],
            ];
        }

        $counts = collect($this->kinds())->countBy();

        return [
            'category' => 'supervision',
            'icon' => $counts->keys()->diff(['recovered'])->isEmpty() ? 'check' : 'alert-triangle',
            'title' => 'Supervision : '.$counts->map(fn ($n, $kind) => $n.' '.mb_strtolower(self::LABELS[$kind]))->join(', '),
            'message' => implode(' · ', array_column($this->items, 'title')),
            'url' => route('intranet.monitor.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);
        $mail = (new MailMessage)
            ->subject('[Intranet NÉRÉ Mining] '.$data['title'])
            ->greeting('Bonjour '.($notifiable->first_name ?? $notifiable->name ?? '').',');
        foreach ($this->items as $item) {
            $mail->line('**'.$item['title'].'** : '.$item['message']);
        }

        return $mail
            ->action('Ouvrir la supervision', $data['url'])
            ->line('Les coupures brèves ne déclenchent pas d’e-mail : elles restent visibles dans la supervision.')
            ->salutation('L’équipe NÉRÉ Mining');
    }
}
