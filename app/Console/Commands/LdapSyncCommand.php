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
        $ignores = 0;

        try {
            $ldapUsers = LdapUser::query()->paginate(1000);
        } catch (\Throwable $e) {
            $this->error('AD injoignable : '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($ldapUsers as $ldap) {
            $guid = $ldap->getConvertedGuid();
            $sam = $ldap->getFirstAttribute('samaccountname');

            // Filtre : on ignore les comptes système / techniques et les entrées
            // sans identité exploitable (pas de nom affiché ni de login).
            if (blank($guid) || blank($sam) || $this->estComptesSysteme($ldap, $sam)) {
                $ignores++;

                continue;
            }

            $user = User::query()->where('guid', $guid)->first()
                ?? ($this->first($ldap, 'mail')
                    ? User::query()->where('email', $this->first($ldap, 'mail'))->first()
                    : null)
                ?? User::query()->where('matricule', $sam)->first()
                ?? new User();

            $existait = $user->exists;

            $user->guid = $guid;
            $user->domain = $domain;
            $attributs = $synchronizer($user, $ldap);

            // Collision d'email : deux comptes AD distincts peuvent porter la même
            // adresse (alias, comptes partagés). La colonne est unique en base :
            // on rend l'adresse unique en la suffixant avec le login, sans perdre
            // le compte ni écraser l'autre.
            if (! empty($attributs['email'])) {
                $attributs['email'] = $this->emailUnique($attributs['email'], $sam, $user->id);
            }

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
        $this->line("  Importés   : {$imported}");
        $this->line("  Mis à jour : {$updated}");
        $this->line("  Ignorés (système/incomplets) : {$ignores}");
        $this->line("  Dont désactivés dans l'AD : {$disabled}");
        $this->info($dryRun ? 'Simulation terminée.' : 'Synchronisation terminée.');

        return self::SUCCESS;
    }

    /**
     * Comptes à ne jamais importer : comptes intégrés Windows et comptes
     * techniques (service, admin AD) qui ne sont pas des employés.
     */
    private function estComptesSysteme(LdapUser $ldap, string $sam): bool
    {
        $exclus = [
            'guest', 'krbtgt', 'defaultaccount', 'administrator',
            'admt', // compte de service LDAP
        ];

        if (in_array(mb_strtolower($sam), $exclus, true)) {
            return true;
        }

        // Comptes machine / ordinateur : leur sAMAccountName se termine par « $ »
        // (ex. EDV-OPS$). Ce ne sont pas des employés.
        if (str_ends_with($sam, '$')) {
            return true;
        }

        // Sans nom affiché, l'entrée n'a pas d'intérêt dans l'annuaire.
        return blank($this->first($ldap, 'displayname'))
            && blank($this->first($ldap, 'cn'));
    }

    /**
     * Garantit l'unicité de l'email en base. Si l'adresse est déjà prise par un
     * AUTRE utilisateur, on la rend unique en insérant le login :
     * « jdupont@x.com » devient « jdupont+sam@x.com ».
     */
    private function emailUnique(string $email, ?string $sam, ?int $idCourant): string
    {
        $existe = User::query()
            ->where('email', $email)
            ->when($idCourant, fn ($q) => $q->where('id', '!=', $idCourant))
            ->exists();

        if (! $existe) {
            return $email;
        }

        if ($sam && str_contains($email, '@')) {
            [$local, $domaine] = explode('@', $email, 2);

            return $local.'+'.mb_strtolower($sam).'@'.$domaine;
        }

        // Dernier recours : préfixe aléatoire court.
        return \Illuminate\Support\Str::random(6).'.'.$email;
    }

    private function first(LdapUser $ldap, string $attr): ?string
    {
        $v = $ldap->getFirstAttribute($attr);

        return is_string($v) && $v !== '' ? $v : null;
    }
}
