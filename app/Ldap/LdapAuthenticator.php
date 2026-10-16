<?php

namespace App\Ldap;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LdapRecord\Container;

/**
 * Tente d'authentifier un utilisateur contre l'Active Directory.
 *
 * Pensé pour cohabiter avec l'authentification locale existante : si l'AD est
 * désactivé, injoignable, ou si l'utilisateur n'y figure pas, on renvoie un
 * résultat « non concluant » et l'appelant (LoginRequest) retombe sur la
 * vérification du mot de passe local. L'admin de secours
 * (admin@nere-mining.com) et les comptes de démo continuent donc de fonctionner.
 */
class LdapAuthenticator
{
    public function __construct(
        private readonly LdapUserSynchronizer $synchronizer,
    ) {
    }

    /** L'intégration AD est-elle activée ? */
    public function enabled(): bool
    {
        return (bool) config('ldap_integration.enabled', env('LDAP_ENABLED', false));
    }

    /**
     * Essaie de connecter l'utilisateur via l'AD.
     *
     * @return bool|null  true  = authentifié et connecté (session ouverte)
     *                    false = trouvé dans l'AD mais mot de passe refusé / compte désactivé
     *                    null  = AD indisponible ou utilisateur absent → tenter le local
     */
    public function attempt(string $identifiant, string $password, bool $remember = false): ?bool
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $ldap = $this->findLdapUser($identifiant);
        } catch (\Throwable $e) {
            // AD injoignable (réseau, DC down, cert LDAPS...) : on log et on
            // laisse l'auth locale prendre le relais plutôt que de bloquer tout
            // le monde si l'annuaire tombe.
            Log::warning('LDAP indisponible, repli sur auth locale', ['erreur' => $e->getMessage()]);

            return null;
        }

        // Inconnu de l'AD : ce n'est pas un compte AD → tenter le local.
        if ($ldap === null) {
            return null;
        }

        // Compte désactivé dans l'AD : accès refusé (règle DSI), sans repli local.
        $uac = (int) $ldap->getFirstAttribute('useraccountcontrol');
        if (($uac & 2) === 2) {
            Log::notice('Authentification LDAP refusée : compte AD désactivé');

            return false;
        }

        // Vérifie le mot de passe contre le DN de l'utilisateur AD.
        if (! $this->checkPassword($ldap->getDn(), $password)) {
            Log::warning('Authentification LDAP refusée : bind utilisateur rejeté');

            return false;
        }

        // Mot de passe bon : synchronise le compte local et ouvre la session.
        $user = $this->syncToDatabase($ldap);

        if (! $user->actif) {
            Log::warning('Authentification LDAP refusée : compte local inactif après synchronisation');

            return false;
        }

        Auth::login($user, $remember);

        return true;
    }

    /**
     * Recherche l'entrée AD par sAMAccountName (login) ou e-mail.
     */
    private function findLdapUser(string $identifiant): ?object
    {
        $query = \LdapRecord\Models\ActiveDirectory\User::query();

        if (str_contains($identifiant, '@')) {
            return $query->where('mail', '=', $identifiant)->first()
                ?? \LdapRecord\Models\ActiveDirectory\User::query()
                    ->where('userprincipalname', '=', $identifiant)->first();
        }

        return $query->where('samaccountname', '=', $identifiant)->first();
    }

    /**
     * Tente un bind LDAP avec le DN et le mot de passe fournis, puis restaure
     * la connexion du compte de service.
     */
    private function checkPassword(string $dn, string $password): bool
    {
        if ($password === '') {
            return false;
        }

        $connection = Container::getConnection(config('ldap.default', 'default'));

        try {
            return $connection->auth()->attempt($dn, $password);
        } catch (\Throwable $e) {
            Log::warning('Echec du bind LDAP', ['dn' => $dn, 'erreur' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Crée ou met à jour le compte local à partir de l'entrée AD, via le même
     * synchroniseur que le provider d'authentification.
     */
    private function syncToDatabase(object $ldap): User
    {
        $guid = $ldap->getConvertedGuid();

        $user = User::query()->where('guid', $guid)->first()
            ?? User::query()->where('email', $ldap->getFirstAttribute('mail'))->first()
            ?? new User();

        $user->guid = $guid;
        $user->domain = config('ldap.default', 'default');

        $attributs = ($this->synchronizer)($user, $ldap);
        $user->forceFill($attributs);
        $user->save();

        return $user;
    }
}
