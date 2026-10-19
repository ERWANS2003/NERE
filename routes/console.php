<?php

use App\Models\AppSetting;
use App\Models\Ticket;
use App\Notifications\SlaAlertNotification;
use App\Services\SlaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sla:verifier', function (SlaService $slaService) {
    $alertes = 0;
    Ticket::with(['sla', 'statut', 'technicien', 'team.techniciens'])
        ->whereNotNull('date_echeance_resolution')
        ->whereNull('date_cloture')
        ->whereNull('deleted_at')
        ->each(function (Ticket $ticket) use ($slaService, &$alertes) {
            $seuil = $slaService->verifierAlerte($ticket);
            if (! $seuil) {
                return;
            }

            collect([$ticket->technicien])->merge($ticket->team?->techniciens ?? collect())
                ->filter()->unique('id')
                ->each(fn ($user) => $user->notify(new SlaAlertNotification($ticket, $seuil)));
            $alertes++;
        });

    $this->info("{$alertes} alerte(s) SLA générée(s).");
})->purpose('Déclencher les alertes SLA à 75 % et 100 %');

// Rappels des événements du calendrier prévus le lendemain, chaque jour à 7h.
Schedule::command('intranet:rappels-evenements')->dailyAt('07:00');

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
