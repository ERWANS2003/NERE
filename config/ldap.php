<?php

/*
|--------------------------------------------------------------------------
| Configuration LDAP / Active Directory (LdapRecord)
|--------------------------------------------------------------------------
|
| Intégration avec l'Active Directory de NÉRÉ Mining.
|
| Infos fournies par la DSI :
|   - Domaine AD        : neremining.com
|   - DC principal      : KAR-NM-DC02.neremining.com (10.226.70.32)
|   - DC secondaire     : 10.226.70.31
|   - Base DN           : DC=neremining,DC=com
|   - Compte de service : neremining\admt  (lecture seule)
|   - Protocole         : LDAPS, port 636 (chiffré)
|
| Les valeurs sensibles (hôtes, compte de service, mot de passe) vivent dans
| le fichier .env du serveur, jamais dans le dépôt. Les valeurs par défaut
| ci-dessous reprennent les infos DSI pour fonctionner même sans .env complet,
| sauf le mot de passe qui reste vide par sécurité.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Connexion LDAP par défaut
    |--------------------------------------------------------------------------
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Connexions LDAP
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'default' => [
            // Les deux contrôleurs de domaine : LdapRecord bascule sur le
            // secondaire si le principal ne répond pas.
            'hosts' => array_filter(explode(',', (string) env(
                'LDAP_HOSTS',
                '10.226.70.32,10.226.70.31'
            ))),

            'username' => env('LDAP_USERNAME', 'neremining\\admt'),
            'password' => env('LDAP_PASSWORD', ''),
            'port' => (int) env('LDAP_PORT', 636),
            'base_dn' => env('LDAP_BASE_DN', 'DC=neremining,DC=com'),

            // Chiffrement (LdapRecord v4) :
            //  - LDAPS   : LDAP_PROTOCOL=ldaps + port 636 (chiffré d'emblée) ;
            //  - StartTLS: LDAP_PROTOCOL=ldap + LDAP_STARTTLS=true + port 389 ;
            //  - clair   : LDAP_PROTOCOL=ldap (ou vide) + port 389 — à n'utiliser
            //              que pour un diagnostic sur réseau interne de confiance.
            //
            // LdapRecord attend le schéma URI complet (ldaps://) pour protocol.
            // La clé n'est transmise que pour LDAPS : l'ext-ldap Windows rejette
            // protocol=ldap dans ldap_connect (« Bad parameter »).
            ...(env('LDAP_PROTOCOL', 'ldaps') === 'ldaps' ? ['protocol' => 'ldaps://'] : []),

            'use_tls' => (bool) env('LDAP_TLS', false),
            'use_starttls' => (bool) env('LDAP_STARTTLS', false),

            'timeout' => (int) env('LDAP_TIMEOUT', 5),

            // Ne pas suivre les référrals : évite les redirections AD qui font
            // échouer les recherches sur un domaine à plusieurs contrôleurs.
            'follow_referrals' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Journalisation LDAP
    |--------------------------------------------------------------------------
    |
    | Trace les opérations LDAP dans les logs Laravel. Utile pour diagnostiquer
    | la connexion au DC ; peut être désactivé en production une fois stable.
    |
    */

    'logging' => env('LDAP_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Cache LDAP
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => env('LDAP_CACHE', false),
        'driver' => env('CACHE_STORE', 'file'),
    ],

];
