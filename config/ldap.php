<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP / Active Directory Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec Active Directory de NERE Mining
    |
    */

    'enabled' => env('LDAP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Serveur LDAP
    |--------------------------------------------------------------------------
    */

    'host' => env('LDAP_HOST', 'ldap://ad.nere-mining.com'),
    'port' => env('LDAP_PORT', 389),

    /*
    |--------------------------------------------------------------------------
    | Base DN (Distinguished Name)
    |--------------------------------------------------------------------------
    |
    | Exemple: DC=nere-mining,DC=com
    |
    */

    'base_dn' => env('LDAP_BASE_DN', 'DC=nere-mining,DC=com'),

    /*
    |--------------------------------------------------------------------------
    | Compte de service
    |--------------------------------------------------------------------------
    |
    | Compte utilisé pour lire l'annuaire AD
    |
    */

    'bind_dn' => env('LDAP_BIND_DN', 'CN=Service Account,CN=Users,DC=nere-mining,DC=com'),
    'bind_password' => env('LDAP_BIND_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Filtres de recherche
    |--------------------------------------------------------------------------
    */

    'user_filter' => env('LDAP_USER_FILTER', '(&(objectClass=user)(objectCategory=person))'),
    
    /*
    |--------------------------------------------------------------------------
    | OU des utilisateurs
    |--------------------------------------------------------------------------
    |
    | Unité d'organisation où se trouvent les utilisateurs
    | Exemple: OU=Employees,DC=nere-mining,DC=com
    |
    */

    'users_ou' => env('LDAP_USERS_OU', 'OU=Users,DC=nere-mining,DC=com'),

    /*
    |--------------------------------------------------------------------------
    | Synchronisation automatique
    |--------------------------------------------------------------------------
    */

    'auto_sync' => env('LDAP_AUTO_SYNC', false),
    'sync_schedule' => env('LDAP_SYNC_SCHEDULE', 'daily'), // daily, hourly, etc.

    /*
    |--------------------------------------------------------------------------
    | Mapping des attributs
    |--------------------------------------------------------------------------
    |
    | Correspondance entre attributs LDAP et colonnes de la base
    |
    */

    'attribute_map' => [
        'username' => 'samaccountname',
        'email' => 'mail',
        'first_name' => 'givenname',
        'last_name' => 'sn',
        'display_name' => 'displayname',
        'department' => 'department',
        'title' => 'title',
        'phone' => 'telephonenumber',
        'mobile' => 'mobile',
    ],

];
