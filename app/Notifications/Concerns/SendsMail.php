<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Copie par e-mail d'une notification de l'intranet : même titre, même
 * message, même lien. Activée par MAIL_NOTIFICATIONS=true dans le .env, pour
 * pouvoir couper les e-mails sans déploiement.
 */
trait SendsMail
{
    /**
     * @param  list<string>  $channels
     * @return list<string>
     */
    protected function withMail(array $channels, object $notifiable): array
    {
        if (config('mail.notifications') && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('[Intranet NÉRÉ Mining] '.$data['title'])
            ->greeting('Bonjour '.($notifiable->first_name ?? $notifiable->name ?? '').',')
            ->line($data['message'])
            ->action('Ouvrir dans l’intranet', $data['url'])
            ->line('Vous recevez cet e-mail en complément de la notification affichée dans l’intranet.')
            ->salutation('L’équipe NÉRÉ Mining');
    }
}
