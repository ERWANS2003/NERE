<?php

use App\Models\User;
use App\Notifications\SystemHealthAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

it('adds the e-mail channel only when mail notifications are enabled and the user has an address', function () {
    $user = User::factory()->create(['email' => 'agent@example.test']);
    $withoutEmail = User::factory()->make(['email' => null]);
    $alert = new SystemHealthAlert(['queue' => 'Des envois sont en échec.']);

    config(['mail.notifications' => false]);
    expect($alert->via($user))->toBe(['database']);

    config(['mail.notifications' => true]);
    expect($alert->via($user))->toBe(['database', 'mail'])
        ->and($alert->via($withoutEmail))->toBe(['database']);
});

it('builds the e-mail from the intranet notification content', function () {
    $user = User::factory()->create(['name' => 'Awa Traoré']);
    $notification = new SystemHealthAlert(['backup' => 'Aucune sauvegarde réussie depuis plus de 36 h.']);

    $mail = $notification->toMail($user);
    $data = $notification->toArray($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('[Intranet NÉRÉ Mining] '.$data['title'])
        ->and($mail->introLines)->toContain($data['message'])
        ->and($mail->actionUrl)->toBe($data['url'])
        ->and((string) $mail->render())->toContain('Ouvrir dans l’intranet');
});

it('queues the e-mail but keeps the bell notification immediate', function () {
    config(['mail.notifications' => true]);
    Notification::fake();
    $user = User::factory()->create(['email' => 'agent@example.test']);

    $user->notify(new SystemHealthAlert(['disk' => 'Moins de 10 % d’espace disque libre.']));

    Notification::assertSentTo($user, SystemHealthAlert::class, fn ($notification, array $channels) => $channels === ['database', 'mail']
        && $notification instanceof ShouldQueue
        && $notification->viaConnections() === ['database' => 'sync']);
});
