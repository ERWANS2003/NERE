<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Ticket;
use App\Notifications\SlaAlertNotification;
use App\Services\SlaService;

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
                ->each(fn($user) => $user->notify(new SlaAlertNotification($ticket, $seuil)));
            $alertes++;
        });

    $this->info("{$alertes} alerte(s) SLA générée(s).");
})->purpose('Déclencher les alertes SLA à 75 % et 100 %');
