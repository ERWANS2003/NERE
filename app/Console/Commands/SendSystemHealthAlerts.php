<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SystemHealthAlert;
use App\Services\SystemHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Notifie les administrateurs système quand une vérification de la page
 * Santé passe au rouge. Seuls les nouveaux problèmes sont signalés ; un
 * problème résolu puis revenu est signalé à nouveau.
 *
 * Lancée par le planificateur : si les tâches planifiées s'arrêtent, cette
 * alerte s'arrête aussi (la page Santé l'affiche).
 */
class SendSystemHealthAlerts extends Command
{
    public const KNOWN_PROBLEMS_KEY = 'sante:problemes-signales';

    protected $signature = 'intranet:alerte-sante';

    protected $description = 'Alerte les administrateurs des nouveaux problèmes techniques du serveur';

    public function handle(SystemHealth $health): int
    {
        $problems = $health->problems();
        $new = array_diff_key($problems, array_flip(Cache::get(self::KNOWN_PROBLEMS_KEY, [])));
        Cache::forever(self::KNOWN_PROBLEMS_KEY, array_keys($problems));

        if ($new === []) {
            $this->info(count($problems).' problème(s) en cours, aucun nouveau.');

            return self::SUCCESS;
        }

        $admins = User::where('is_super_admin', true)->where('actif', true)->get();
        Notification::send($admins, new SystemHealthAlert($new));
        $this->info(count($new).' nouveau(x) problème(s) signalé(s) à '.$admins->count().' administrateur(s).');

        return self::SUCCESS;
    }
}
