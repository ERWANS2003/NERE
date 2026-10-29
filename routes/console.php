<?php

use App\Http\Controllers\Admin\SystemHealthController;
use App\Models\AppSetting;
use App\Services\TicketSlaService;
use App\Services\TimeOffReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\VAPID;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('intranet:check-ticket-slas', function (TicketSlaService $ticketSlaService) {
    $alertes = $ticketSlaService->checkDueAlerts();
    $this->info("{$alertes} alerte(s) SLA générée(s).");
})->purpose('Vérifier les délais SLA des tickets et notifier les équipes IT');

Artisan::command('intranet:remind-pending-time-off', function (TimeOffReminderService $reminderService) {
    $result = $reminderService->sendDueReminders();
    $this->info("{$result['sent']} rappel(s) de congé envoyé(s).");
    if ($result['unroutable'] > 0) {
        $this->warn("{$result['unroutable']} demande(s) n'ont aucun manager ou administrateur actif à notifier; voir les logs.");
    }
})->purpose('Rappeler les demandes de congé en attente depuis 48 heures');

Artisan::command('webpush:generate-vapid-keys', function () {
    $keys = VAPID::createVapidKeys();
    $this->line('WEB_PUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('WEB_PUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);
    $this->warn('Conservez la clé privée dans un gestionnaire de secrets; ne la versionnez jamais.');
})->purpose('Générer une paire de clés VAPID pour les notifications push navigateur');

// Témoin affiché sur la page Santé du serveur de l'admin.
Schedule::call(fn () => Cache::forever(SystemHealthController::SCHEDULER_HEARTBEAT_KEY, now()->toIso8601String()))
    ->everyMinute()
    ->name('sante-scheduler');

// Rappels des événements du calendrier prévus le lendemain, chaque jour à 7h.
Schedule::command('intranet:rappels-evenements')->dailyAt('07:00');
Schedule::command('intranet:check-ticket-slas')->everyMinute()->withoutOverlapping();
Schedule::command('intranet:rappels-menus')->hourly()->withoutOverlapping();
Schedule::command('intranet:remind-pending-time-off')->everyFifteenMinutes()->withoutOverlapping();

Schedule::command('ldap:sync')
    ->everyMinute()
    ->when(static function (): bool {
        if (! config('ldap_integration.enabled') || ! Schema::hasTable('app_settings')) {
            return false;
        }

        $frequency = AppSetting::value('ldap_sync_frequency', 'manual');
        if ($frequency === 'hourly') {
            return now()->minute === 0;
        }

        $time = AppSetting::value('ldap_sync_time', '02:00') ?? '02:00';
        if (now()->format('H:i') !== $time) {
            return false;
        }

        if ($frequency === 'daily') {
            return true;
        }

        return $frequency === 'weekly'
            && now()->dayOfWeekIso === (int) AppSetting::value('ldap_sync_day', '1');
    })
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ldap-sync.log'));
