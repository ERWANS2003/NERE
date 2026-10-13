<?php

namespace App\Ldap;

use App\Models\User;
use Illuminate\Support\Str;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use LdapRecord\Models\Model as LdapRecordModel;

/**
 * Synchronise un compte Active Directory vers la table locale `users`.
 *
 * LdapRecord appelle cette classe (invokable) à chaque authentification AD et
 * lors de `php artisan ldap:sync`. Elle reçoit le modèle local (déjà rattaché
 * par email) et l'entrée LDAP, et renvoie le tableau des colonnes à écrire.
 *
 * Points clés :
 *  - La colonne d'activation est `actif` (schéma FR), dérivée du bit « compte
 *    désactivé » de userAccountControl côté AD : un compte AD désactivé devient
 *    inactif dans l'intranet, ce qui lui coupe l'accès (règle validée par la DSI).
 *  - On ne touche JAMAIS aux droits applicatifs (is_super_admin, rattachements
 *    départements, matricule déjà présent) : l'AD renseigne l'identité, pas les
 *    autorisations internes.
 *  - Le mot de passe n'est pas synchronisé (sync_passwords=false) : l'AD reste
 *    seul juge du mot de passe, la colonne locale garde une valeur aléatoire.
 */
class LdapUserSynchronizer
{
    /**
     * @param  User  $database  compte local (nouveau ou existant)
     * @param  LdapUser|LdapRecordModel  $ldap  entrée Active Directory
     * @return array<string, mixed>
     */
    public function __invoke(User $database, LdapRecordModel $ldap): array
    {
        $sam = $this->first($ldap, 'samaccountname');

        $attrs = [
            'name' => $this->first($ldap, 'displayname')
                ?? trim($this->first($ldap, 'givenname').' '.$this->first($ldap, 'sn'))
                ?: $this->first($ldap, 'cn'),

            // La colonne email est NOT NULL : beaucoup de comptes AD n'ont pas de
            // « mail » renseigné. On retombe sur l'UPN, puis sur une adresse de
            // substitution déterministe (login@domaine) pour satisfaire la base
            // sans jamais inventer une vraie boîte. Ces adresses « .local » sont
            // reconnaissables et pourront être corrigées quand l'AD sera complété.
            'email' => $this->first($ldap, 'mail')
                ?? $this->first($ldap, 'userprincipalname')
                ?? ($sam ? Str::lower($sam).'@ad.neremining.local' : null),

            'actif' => ! $this->estDesactiveDansAd($ldap),
        ];

        // Attributs optionnels : écrits seulement si l'AD les renseigne, pour ne
        // pas écraser une valeur locale par une chaîne vide.
        $this->copierSiPresent($attrs, $ldap, 'poste', ['title']);
        $this->copierSiPresent($attrs, $ldap, 'telephone', ['telephonenumber', 'mobile']);

        // Matricule : seulement si l'AD le fournit ET que le compte local n'en a
        // pas déjà un (le matricule RH local fait autorité une fois posé).
        if (blank($database->matricule)) {
            $matricule = $this->first($ldap, 'employeeid') ?? $this->first($ldap, 'samaccountname');
            if (filled($matricule)) {
                $attrs['matricule'] = $matricule;
            }
        }

        // À la création d'un compte AD, on pose un mot de passe local aléatoire :
        // il ne sert jamais (auth via AD) mais satisfait la colonne non nulle.
        if (! $database->exists) {
            $attrs['password'] = bcrypt(Str::random(40));
        }

        return array_filter(
            $attrs,
            fn ($v) => $v !== null && $v !== '',
        );
    }

    /**
     * Un compte AD est désactivé si le bit 2 (ACCOUNTDISABLE) de
     * userAccountControl est positionné.
     */
    private function estDesactiveDansAd(LdapRecordModel $ldap): bool
    {
        $uac = (int) $this->first($ldap, 'useraccountcontrol');

        return ($uac & 2) === 2;
    }

    /**
     * Copie la première valeur AD trouvée parmi $sources vers $attrs[$cible].
     *
     * @param  array<string, mixed>  $attrs
     * @param  list<string>  $sources
     */
    private function copierSiPresent(array &$attrs, LdapRecordModel $ldap, string $cible, array $sources): void
    {
        foreach ($sources as $source) {
            $valeur = $this->first($ldap, $source);
            if (filled($valeur)) {
                $attrs[$cible] = $valeur;

                return;
            }
        }
    }

    /** Première valeur d'un attribut LDAP (multi-valué), ou null. */
    private function first(LdapRecordModel $ldap, string $attribut): ?string
    {
        $valeur = $ldap->getFirstAttribute($attribut);

        return is_string($valeur) && $valeur !== '' ? $valeur : null;
    }
}
