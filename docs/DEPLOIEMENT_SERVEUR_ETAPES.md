# Mise à jour de l'application sur le serveur Windows

À exécuter dans l'ordre, en PowerShell, depuis `C:\inetpub\wwwroot\itsm-nere-mining`.

> Sauvegardez la base PostgreSQL avant toute mise à jour. Ne lancez jamais
> `migrate:fresh`, `migrate:refresh` ni un import de dump pendant un déploiement.

---

## 1. Récupérer le code

```powershell
git pull --ff-only origin main
```

Si Git refuse le fast-forward ou signale des modifications locales, arrêtez-vous
et examinez l'état du dépôt au lieu de forcer la fusion.

## 2. Vérifier `.env` et les prérequis

Conservez le `.env` de production existant. Ne lancez pas `key:generate` après
la première installation : changer `APP_KEY` rendrait illisibles les données
chiffrées et invaliderait les sessions. Vérifiez notamment la base de données,
`APP_DEBUG=false`, les paramètres LDAP et l'extension PHP LDAP :

```powershell
php -v
php -m | Select-String ldap
```

Ce projet requiert PHP 8.3 ou plus récent. Si l'extension LDAP manque, activez-la
dans `C:\PHP\php.ini` puis redémarrez IIS après le déploiement.

## 3. Installer les dépendances PHP et construire les assets

```powershell
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

`npm ci` doit aussi installer les dépendances de développement Vite/Tailwind
nécessaires pour le build.

## 4. Appliquer les migrations sans réinitialiser la base

```powershell
php artisan optimize:clear
php artisan migrate --force
```

Les nouvelles migrations ajoutent les menus, journaux d'audit et notifications,
et alignent le nom de la colonne d'activation des utilisateurs. Elles préservent
les données existantes. Si la migration échoue, arrêtez-vous et consultez le
journal Laravel avant de continuer.

## 5. Vérifier la configuration Active Directory si nécessaire

N'éditez pas les secrets dans le dépôt. Le fichier
`C:\inetpub\wwwroot\itsm-nere-mining\.env` doit contenir les paramètres LDAP
propres à votre environnement, par exemple :

```
# Active Directory
LDAP_ENABLED=true
AUTH_PROVIDER_DRIVER=ldap
LDAP_HOSTS=<IP_DC_1>,<IP_DC_2>
LDAP_BASE_DN="DC=<domaine>,DC=<suffixe>"
LDAP_USERNAME="<DOMAINE>\<COMPTE_DE_SERVICE>"
LDAP_PASSWORD=<MOT_DE_PASSE_DU_COMPTE_DE_SERVICE>
LDAP_LOCAL_FALLBACK=true

# Chiffrement :
#  - si LDAPS est actif sur le DC (certificat posé) :
LDAP_PROTOCOL=ldaps
LDAP_PORT=636
#  - sinon, temporairement en clair (réseau interne de confiance) :
# LDAP_PROTOCOL=ldap
# LDAP_PORT=389
```

> ⚠️ Mettre `LDAP_PASSWORD` entre guillemets (il contient des `@`).

## 6. Reconstruire les caches et redémarrer les workers

```powershell
php artisan optimize
php artisan queue:restart
iisreset
```

`queue:restart` est utile si un worker Laravel tourne en service Windows.

## 7. Vérifier l'application

```powershell
php artisan about
php artisan migrate:status
```

Vérifiez ensuite l'écran de connexion, l'accès à l'intranet et les menus
administratifs. Consultez les erreurs éventuelles avec :

```powershell
Get-Content storage\logs\laravel.log -Tail 60
```

La synchronisation LDAP n'est pas nécessaire à chaque mise à jour. Ne relancez
`ldap:sync` que si vous souhaitez réellement synchroniser les comptes.

---

## Diagnostic LDAP (à utiliser seulement si nécessaire)

### Tester la connexion au contrôleur de domaine

```powershell
php artisan ldap:test
```
- En cas d'échec « Can't contact LDAP server » sur le port 636, vérifiez d'abord
  le certificat LDAPS et la connectivité réseau. N'utilisez le port 389 qu'en
  accord avec votre politique de sécurité.

### Synchroniser l'annuaire uniquement sur demande

```powershell
php artisan ldap:sync --dry-run   # aperçu, n'écrit rien
php artisan ldap:sync             # import réel (~300 comptes)
```

---

## En cas de problème

- **Erreur 500** : `Get-Content storage\logs\laravel.log -Tail 40`
- **Revenir à l'auth locale** (désactiver l'AD sans redéployer) :
  ```
  LDAP_ENABLED=false
  AUTH_PROVIDER_DRIVER=eloquent
  ```
  puis `php artisan config:clear`.
- **Permissions** : `storage/` et `bootstrap/cache/` doivent être inscriptibles
  par le compte du pool IIS.
