<?php

namespace App\Console\Commands;

use App\Models\MenuCampaign;
use App\Models\User;
use App\Notifications\MenuCampaignNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Dans les 24 h qui précèdent la clôture des inscriptions, rappelle aux
 * employés désignés qui n'ont pas encore choisi leurs plats. Un seul rappel
 * par semaine de menus (reminder_sent_at).
 */
class SendMenuReminders extends Command
{
    protected $signature = 'intranet:rappels-menus';

    protected $description = 'Rappelle aux employés désignés de choisir leurs plats avant la clôture';

    public function handle(): int
    {
        $campaigns = MenuCampaign::query()
            ->openForRegistration()
            ->whereNull('reminder_sent_at')
            ->where('registration_closes_at', '<=', now()->addDay())
            ->get();

        $total = 0;
        foreach ($campaigns as $campaign) {
            $recipients = User::query()
                ->menuChoosers()
                ->whereDoesntHave('menuRegistrations', fn ($query) => $query->where('campaign_id', $campaign->id))
                ->get();

            Notification::send($recipients, new MenuCampaignNotification($campaign, reminder: true));
            $campaign->update(['reminder_sent_at' => now()]);
            $total += $recipients->count();
        }

        $this->info("{$campaigns->count()} semaine(s), {$total} rappel(s) de menu envoyé(s).");

        return self::SUCCESS;
    }
}
