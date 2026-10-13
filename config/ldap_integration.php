<?php

/*
|--------------------------------------------------------------------------
| Intégration Active Directory — réglages applicatifs
|--------------------------------------------------------------------------
|
| Distinct de config/ldap.php (connexion technique gérée par LdapRecord) :
| ici on règle le COMPORTEMENT de l'intégration côté intranet.
|
*/

return [

    // Interrupteur général. Tant que false, l'intranet fonctionne 100 % en
    // authentification locale (aucune tentative LDAP). À passer à true une fois
    // la connexion au DC validée.
    'enabled' => (bool) env('LDAP_ENABLED', false),

    // Autoriser le repli sur l'authentification locale si l'AD est injoignable
    // ou si l'utilisateur n'existe pas dans l'AD (recommandé : garde l'admin de
    // secours fonctionnel même DC éteint).
    'allow_local_fallback' => (bool) env('LDAP_LOCAL_FALLBACK', true),

];
