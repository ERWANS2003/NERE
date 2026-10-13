<?php

namespace App\Console\Commands;

use App\Ldap\LdapUserSynchronizer;
use App\Models\User;
use Illuminate\Console\Command;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

/**
 * Synchronise l'annuaire : importe/met à jour tous les comptes de l'AD dans la
 * table locale `users`. Complément de l'auth temps réel (qui ne synchronise que
 * l'utilisateur qui se connecte) : utile pour peupler l'annuaire d'un coup et
 * refléter les comptes désactivés sans attendre leur prochaine connexion.
 *
 * Exécution : php artisan ldap:sync [--dry-run]
 */
class LdapSyncCommand extends Command
{
    protected $signature = 'ldap:sync {--dry-run : Affiche ce qui serait fait sans écrire en base}';

    protected $description = 'Synchronise les comptes Active Directory vers la base locale';

    public function handle(LdapUserSynchronizer $synchronizer): int
    {
        if (! config('ldap_integration.enabled')) {
            $this->warn('Intégration AD désactivée (LDAP_ENABLED=false). Rien à faire.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Mode simulation (aucune écriture).' : 'Synchronisation de l\'annuaire AD...');

        $domain = config('ldap.default', 'default');
        $imported = 0;
        $updated = 0;
        $disabled = 0;

        try {
            $ldapUsers = LdapUser::query()->paginate(500);
        } catch (\Throwable $e) {
            $this->error('AD injoignable : '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($ldapUsers as $ldap) {
            $guid = $ldap->getConvertedGuid();
            if (blank($guid)) {
                continue;
            }

            $user = User::query()->where('guid', $guid)->first()
                ?? User::query()->where('email', $ldap->getFirstAttribute('mail'))->first()
                ?? new User();

            $existait = $user->exists;

            $user->guid = $guid;
            $user->domain = $domain;
            $attributs = $synchronizer($user, $ldap);
            $user->forceFill($attributs);

            if (! ($attributs['actif'] ?? true)) {
                $disabled++;
            }

            if (! $dryRun) {
                $user->save();
            }

            $existait ? $updated++ : $imported++;
        }

        $this->newLine();
        $this->line("  Importés  : {$imported}");
        $this->line("  Mis à jour: {$updated}");
        $this->line("  Désactivés (AD) : {$disabled}");
        $this->info($dryRun ? 'Simulation terminée.' : 'Synchronisation terminée.');

        return self::SUCCESS;
    }
}
