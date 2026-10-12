<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\UpcomingEventReminder;
use Illuminate\Console\Command;

/**
 * Envoie un rappel pour chaque événement du calendrier prévu le lendemain.
 *
 * À planifier une fois par jour (voir routes/console.php). Idempotence
 * assurée côté planification : lancée une seule fois par jour, elle ne
 * produit qu'un rappel par événement.
 */
class SendEventReminders extends Command
{
    protected $signature = 'intranet:rappels-evenements';

    protected $description = 'Notifie les employés des événements du calendrier prévus demain';

    public function handle(): int
    {
        $demain = now()->addDay()->toDateString();

        $evenements = CalendarEvent::with('department')
            ->whereDate('event_date', $demain)
            ->get();

        if ($evenements->isEmpty()) {
            $this->info('Aucun événement demain, aucun rappel envoyé.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($evenements as $evenement) {
            // Un événement de département ne concerne que ses membres ;
            // un événement sans département concerne toute l'entreprise.
            $destinataires = $evenement->department_id === null
                ? User::where('actif', true)->get()
                : User::where('actif', true)
                    ->whereHas('departments', fn ($q) => $q->where('departments.id', $evenement->department_id))
                    ->get();

            foreach ($destinataires as $destinataire) {
                $destinataire->notify(new UpcomingEventReminder($evenement));
                $total++;
            }
        }

        $this->info("{$evenements->count()} événement(s), {$total} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
