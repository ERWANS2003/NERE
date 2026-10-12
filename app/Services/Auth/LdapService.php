<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Service d'authentification LDAP/Active Directory
 * 
 * Ce service gère l'intégration avec Active Directory de NERE Mining.
 * En attendant l'activation de l'extension PHP LDAP, il fournit une interface
 * pour une future intégration.
 */
class LdapService
{
    /**
     * Vérifie si LDAP est activé et configuré
     */
    public function isEnabled(): bool
    {
        return config('ldap.enabled', false) && extension_loaded('ldap');
    }

    /**
     * Authentifie un utilisateur via Active Directory
     * 
     * @param string $username Username ou email
     * @param string $password Mot de passe
     * @return array|null Données utilisateur si succès, null sinon
     */
    public function authenticate(string $username, string $password): ?array
    {
        if (!$this->isEnabled()) {
            Log::info('LDAP non activé - authentification locale utilisée');
            return null;
        }

        try {
            // Connexion au serveur LDAP
            $connection = ldap_connect(config('ldap.host'), config('ldap.port', 389));
            
            if (!$connection) {
                Log::error('Impossible de se connecter au serveur LDAP');
                return null;
            }

            // Options de connexion
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

            // Construction du DN utilisateur
            $userDn = $this->buildUserDn($username);

            // Tentative de bind (authentification)
            if (!@ldap_bind($connection, $userDn, $password)) {
                ldap_close($connection);
                return null;
            }

            // Récupération des informations utilisateur
            $userData = $this->getUserData($connection, $username);
            
            ldap_close($connection);
            
            return $userData;

        } catch (\Exception $e) {
            Log::error('Erreur LDAP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronise les utilisateurs depuis Active Directory
     * 
     * @return int Nombre d'utilisateurs synchronisés
     */
    public function syncUsers(): int
    {
        if (!$this->isEnabled()) {
            Log::info('LDAP non activé - synchronisation ignorée');
            return 0;
        }

        $synchronized = 0;

        try {
            $connection = $this->connect();
            if (!$connection) {
                return 0;
            }

            $baseDn = config('ldap.base_dn');
            $filter = config('ldap.user_filter', '(objectClass=user)');

            $search = ldap_search($connection, $baseDn, $filter, [
                'samaccountname',
                'mail',
                'givenname',
                'sn',
                'displayname',
                'telephonenumber',
                'department',
                'title'
            ]);

            if (!$search) {
                ldap_close($connection);
                return 0;
            }

            $entries = ldap_get_entries($connection, $search);

            foreach ($entries as $entry) {
                if (!is_array($entry) || !isset($entry['samaccountname'][0])) {
                    continue;
                }

                $this->syncUser($entry);
                $synchronized++;
            }

            ldap_close($connection);

        } catch (\Exception $e) {
            Log::error('Erreur synchronisation LDAP: ' . $e->getMessage());
        }

        return $synchronized;
    }

    /**
     * Synchronise un utilisateur individuel
     */
    protected function syncUser(array $ldapEntry): void
    {
        $username = $ldapEntry['samaccountname'][0] ?? null;
        $email = $ldapEntry['mail'][0] ?? null;

        if (!$username) {
            return;
        }

        User::updateOrCreate(
            ['matricule' => $username],
            [
                'name' => $ldapEntry['displayname'][0] ?? $username,
                'email' => $email ?: $username . '@nere-mining.com',
                'actif' => true,
                'password' => Hash::make(bin2hex(random_bytes(16))), // Mot de passe aléatoire
                'ldap_synced' => true,
            ]
        );
    }

    /**
     * Connexion au serveur LDAP avec compte de service
     */
    protected function connect()
    {
        $connection = ldap_connect(config('ldap.host'), config('ldap.port', 389));
        
        if (!$connection) {
            return null;
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        $bindDn = config('ldap.bind_dn');
        $bindPassword = config('ldap.bind_password');

        if (!@ldap_bind($connection, $bindDn, $bindPassword)) {
            ldap_close($connection);
            return null;
        }

        return $connection;
    }

    /**
     * Construit le DN utilisateur
     */
    protected function buildUserDn(string $username): string
    {
        $baseDn = config('ldap.base_dn');
        return "CN={$username},{$baseDn}";
    }

    /**
     * Récupère les données utilisateur depuis LDAP
     */
    protected function getUserData($connection, string $username): ?array
    {
        $baseDn = config('ldap.base_dn');
        $filter = "(samaccountname={$username})";

        $search = ldap_search($connection, $baseDn, $filter, [
            'samaccountname',
            'mail',
            'displayname',
            'givenname',
            'sn',
            'department',
            'title'
        ]);

        if (!$search) {
            return null;
        }

        $entries = ldap_get_entries($connection, $search);

        if ($entries['count'] === 0) {
            return null;
        }

        $entry = $entries[0];

        return [
            'username' => $entry['samaccountname'][0] ?? $username,
            'email' => $entry['mail'][0] ?? null,
            'name' => $entry['displayname'][0] ?? $username,
            'first_name' => $entry['givenname'][0] ?? '',
            'last_name' => $entry['sn'][0] ?? '',
            'department' => $entry['department'][0] ?? null,
            'title' => $entry['title'][0] ?? null,
        ];
    }
}
