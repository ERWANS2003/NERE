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

## 5. Configurer Active Directory si l'authentification AD est activée

Les secrets restent uniquement dans le `.env` de production et ne doivent jamais
être copiés dans Git, un ticket ou un journal partagé. Configurez le compte de
service AD en lecture seule (pas le mot de passe de chaque utilisateur) et les
paramètres de connexion correspondant au réseau du serveur :

```
# Active Directory
LDAP_ENABLED=true
LDAP_HOSTS=<HOTE_OU_IP_DC_1>,<HOTE_OU_IP_DC_2>
LDAP_BASE_DN="DC=<domaine>,DC=<suffixe>"
LDAP_USERNAME="<DOMAINE>\<COMPTE_DE_SERVICE_LECTURE>"
LDAP_PASSWORD="<SECRET_DU_COMPTE_DE_SERVICE>"
LDAP_LOCAL_FALLBACK=true

# Transport recommandé en production : LDAPS avec un certificat DC approuvé
LDAP_PROTOCOL=ldaps
LDAP_PORT=636
LDAP_TLS=false
LDAP_STARTTLS=false
```

N'ajoutez pas `AUTH_PROVIDER_DRIVER` : l'application effectue l'authentification
AD dans son propre service puis conserve le provider de session Laravel habituel.
`LDAP_ENABLED=true` active le parcours AD ; `LDAP_LOCAL_FALLBACK=true` conserve
le repli sur les comptes locaux. L'identifiant saisi est le `sAMAccountName`
(login AD) ou l'adresse e-mail/UPN.

LDAP sans chiffrement (port 389, `LDAP_PROTOCOL=ldap`, TLS et StartTLS désactivés)
ne doit servir qu'à un diagnostic temporaire sur un réseau interne maîtrisé :
le bind transmet les identifiants sans chiffrement. Ne l'utilisez pas en
production. Si le serveur ne prend pas encore en charge LDAPS, faites activer
et valider son certificat avant d'activer l'authentification AD.

Après toute modification du `.env`, reconstruisez la configuration puis testez
le bind et la lecture de l'annuaire, avant de demander aux utilisateurs de se
connecter :

```powershell
php artisan config:clear
php artisan ldap:test
php artisan ldap:sync --dry-run
php artisan optimize
```

`ldap:test` vérifie le bind de la connexion configurée ; `ldap:sync --dry-run`
vérifie également que le compte de service peut lire les entrées AD, sans écrire
dans la base. Ne lancez pas `ldap:sync` sans `--dry-run` lors d'une simple mise à
jour. Si l'un des tests échoue, arrêtez-vous et corrigez le compte de service,
la base DN, les permissions de recherche, le réseau ou le certificat. N'envoyez
jamais un mot de passe d'utilisateur ou de service dans les logs ou la sortie
d'un diagnostic.

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

La synchronisation LDAP n'est pas nécessaire à chaque mise à jour. Les
utilisateurs AD sont synchronisés lors de leur première connexion si la recherche
et le bind sont opérationnels ; ne lancez une synchronisation réelle que si vous
souhaitez importer les comptes à l'avance et après validation du mode simulation.

---

## Diagnostic LDAP (à utiliser seulement si nécessaire)

### Tester la connexion au contrôleur de domaine

```powershell
php artisan ldap:test
php artisan ldap:sync --dry-run
```
- « Can't contact LDAP server » : vérifiez les hôtes, le port, le pare-feu et le
  certificat LDAPS.
- « Search: Operations error » : vérifiez le compte de service, son secret, la
  base DN et ses droits de lecture. Un test de bind réussi ne garantit pas que
  la recherche AD soit autorisée.
- Si les deux diagnostics réussissent, vérifiez la connexion avec un compte AD
  autorisé sur la page de connexion, puis confirmez l'accès à l'intranet. Ne
  demandez pas à l'utilisateur de communiquer son mot de passe.

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
  ```
  puis `php artisan config:clear`.
- **Permissions** : `storage/` et `bootstrap/cache/` doivent être inscriptibles
  par le compte du pool IIS.
