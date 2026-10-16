# Activation de l'authentification Active Directory

Intégration réalisée avec **LdapRecord** (`directorytree/ldaprecord-laravel`).
L'authentification AD **cohabite** avec l'auth locale : l'admin de secours
(`admin@nere-mining.com`) et les comptes de démo continuent de fonctionner même
si l'AD est injoignable.

## Comment ça marche

1. À la connexion, `LoginRequest` tente d'abord l'AD via `App\Ldap\LdapAuthenticator` :
   - **mot de passe bon** → le compte est créé/mis à jour dans la table locale
     `users` (via `App\Ldap\LdapUserSynchronizer`) et la session s'ouvre ;
   - **compte AD désactivé** → accès refusé (règle DSI) ;
   - **utilisateur hors AD / AD injoignable** → repli sur l'auth locale.
2. L'AD renseigne l'**identité** (nom, email, poste, téléphone, matricule) ; les
   **droits** applicatifs (`is_super_admin`, rattachements départements) restent
   gérés dans l'intranet.
3. Un compte **désactivé dans l'AD** devient `actif = false` localement → accès bloqué.

## Activation sur le serveur

### 1. Extension PHP
Vérifier que `ext-ldap` est actif :
```powershell
php -m | Select-String ldap
```
Si absent : décommenter `extension=ldap` dans `C:\PHP\php.ini`, puis `iisreset`.

### 2. Variables `.env` du serveur
```
LDAP_ENABLED=true
AUTH_PROVIDER_DRIVER=ldap
LDAP_HOSTS=10.226.70.32,10.226.70.31
LDAP_PORT=636
LDAP_PROTOCOL=ldaps
LDAP_BASE_DN="DC=neremining,DC=com"
LDAP_USERNAME="neremining\admt"
LDAP_PASSWORD=<mot de passe du compte de service>
LDAP_LOCAL_FALLBACK=true
```

### 3. Migration (colonnes guid/domain)
```powershell
php artisan migrate --force
php artisan config:clear
```

### 4. Tester la connexion au DC
```powershell
php artisan tinker --execute="LdapRecord\Container::getConnection('default')->connect(); echo 'Connexion LDAP OK';"
```

### 5. Importer l'annuaire (optionnel, en plus de l'auth temps réel)
```powershell
php artisan ldap:sync --dry-run   # simulation
php artisan ldap:sync             # écriture réelle
```

## Points en attente (à confirmer avec la DSI)

- **LDAPS réellement actif sur le DC ?** La fiche indiquait « protocole LDAPS 636 »
  mais « LDAPS activé = Non » ; le test de connexion échoue actuellement. Si LDAPS
  n'est pas disponible, deux options :
  - activer LDAPS côté DC (certificat), ou
  - confirmer StartTLS et son certificat, puis utiliser
    `LDAP_PROTOCOL=ldap`, `LDAP_PORT=389`, `LDAP_STARTTLS=true`.
  > Ne jamais transmettre de mots de passe sur du LDAP 389 non chiffré.
- **Mot de passe du compte de service** : il était visible sur la photo transmise.
  Le faire renouveler avant toute utilisation, puis stocker le nouveau secret uniquement
  dans le `.env` du serveur, jamais dans Git ni dans cette documentation.
- **Attributs** : `sAMAccountName`, `mail`, `givenName`, `sn` et `displayName` sont
  cochés sur la fiche. `department`, `title`, téléphone, matricule et manager ne le
  sont pas ; ne pas attendre ces valeurs depuis l'AD sans confirmation de la DSI.
- **Unités d'organisation à exclure** : la fiche indique qu'il faut en exclure, mais
  ne donne pas leurs DN. Obtenir les DN exacts avant de configurer un filtre.

## Revenir à l'auth 100 % locale

En cas de souci, désactiver l'AD sans redéploiement :
```
LDAP_ENABLED=false
AUTH_PROVIDER_DRIVER=eloquent
```
puis `php artisan config:clear`.
