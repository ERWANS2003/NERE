# Déployer ITSM Néré Mining sur Windows Server

Guide de déploiement sur un serveur Windows Server local avec IIS, PHP FastCGI, PostgreSQL et Node.js.

## 1. Architecture recommandée

- **IIS** : serveur HTTP et site Windows.
- **PHP 8.3 ou supérieur** : exécution Laravel via FastCGI.
- **Composer** : dépendances PHP.
- **PostgreSQL 15 ou supérieur** : base de données applicative.
- **Node.js 20 LTS ou supérieur** : compilation Vite uniquement.
- **Task Scheduler** : lancement périodique du scheduler Laravel.
- **NSSM** : service Windows recommandé pour le worker de queue.

Le code doit être placé dans un chemin simple, par exemple :

```text
C:\Sites\itsm-nere-mining
```

Ne publiez jamais la racine du projet. Le dossier IIS doit pointer vers :

```text
C:\Sites\itsm-nere-mining\public
```

## 2. Préparer Windows Server

Ouvrir PowerShell en tant qu'administrateur et installer IIS :

```powershell
Install-WindowsFeature Web-Server,Web-WebServer,Web-Common-Http,Web-Default-Doc,Web-Http-Errors,Web-Static-Content,Web-Http-Logging,Web-Request-Monitor,Web-Filtering,Web-Mgmt-Console,Web-CGI
```

Installer ensuite :

- PHP Windows x64 non thread-safe, version 8.3 ou supérieure.
- Composer pour Windows.
- Node.js LTS.
- PostgreSQL avec pgAdmin ou les outils CLI.
- Git pour Windows.
- URL Rewrite pour IIS : https://www.iis.net/downloads/microsoft/url-rewrite
- NSSM pour installer le worker Laravel comme service : https://nssm.cc/download

Ajouter au `PATH` système les dossiers PHP, Composer, Node.js et PostgreSQL `bin`, par exemple :

```text
C:\PHP
C:\ProgramData\ComposerSetup\bin
C:\Program Files\nodejs
C:\Program Files\PostgreSQL\15\bin
```

Vérifier dans un nouveau terminal :

```powershell
php -v
composer --version
node --version
npm --version
psql --version
```

## 3. Configurer PHP

Dans `C:\PHP\php.ini`, activer au minimum les extensions suivantes :

```ini
extension=curl
extension=mbstring
extension=openssl
extension=fileinfo
extension=pdo_pgsql
extension=pgsql
extension=zip
extension=gd
```

Vérifier les limites adaptées aux pièces jointes du projet :

```ini
upload_max_filesize=20M
post_max_size=25M
memory_limit=256M
max_execution_time=120
```

Redémarrer IIS après toute modification de PHP :

```powershell
iisreset
```

## 4. Installer PostgreSQL

Créer la base et un utilisateur dédiés. Depuis PowerShell ou SQL Shell :

```sql
CREATE USER nere_itsm WITH PASSWORD 'REMPLACER_PAR_UN_SECRET_LONG';
CREATE DATABASE nere_mining_itsm OWNER nere_itsm;
```

Tester la connexion :

```powershell
psql -h 127.0.0.1 -p 5432 -U nere_itsm -d nere_mining_itsm
```

Si le serveur PostgreSQL n'est pas sur le port standard, utiliser le port réel dans `.env`.

## 5. Récupérer et installer l'application

Créer le dossier de déploiement puis cloner le dépôt :

```powershell
New-Item -ItemType Directory -Force C:\Sites | Out-Null
Set-Location C:\Sites
git clone https://github.com/ERWANS2003/NERE.git itsm-nere-mining
Set-Location C:\Sites\itsm-nere-mining
```

Installer les dépendances de production :

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Créer le fichier d'environnement :

```powershell
Copy-Item .env.example .env
php artisan key:generate --force
```

Modifier `.env` avec au minimum :

```dotenv
APP_NAME="ITSM Néré Mining"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://itsm-mine.local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nere_mining_itsm
DB_USERNAME=nere_itsm
DB_PASSWORD=REMPLACER_PAR_LE_SECRET_POSTGRESQL

LOG_CHANNEL=stack
LOG_LEVEL=warning

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
MAIL_MAILER=log
```

`APP_KEY` doit être générée une seule fois et conservée lors des mises à jour. Ne commitez jamais `.env`.

## 6. Initialiser la base

Depuis la racine du projet :

```powershell
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
```

Avant la première mise en production, vérifier les migrations :

```powershell
php artisan migrate:status
```

La commande `db:seed --force` réinitialise ou complète les données de référence selon les seeders du projet. Sur une base existante, faire une sauvegarde PostgreSQL avant toute migration ou seed.

## 7. Permissions NTFS

Le compte utilisé par IIS doit pouvoir lire le projet et écrire uniquement dans les dossiers Laravel nécessaires.

Exemple avec le compte IIS standard :

```powershell
$root = 'C:\Sites\itsm-nere-mining'
icacls "$root" /inheritance:r
icacls "$root" /grant:r 'Administrators:(OI)(CI)(F)' 'SYSTEM:(OI)(CI)(F)' 'IIS_IUSRS:(OI)(CI)(RX)'
icacls "$root\storage" /grant 'IIS_IUSRS:(OI)(CI)(M)'
icacls "$root\bootstrap\cache" /grant 'IIS_IUSRS:(OI)(CI)(M)'
icacls "$root\public\storage" /grant 'IIS_IUSRS:(OI)(CI)(M)'
```

Ne donnez pas d'accès en écriture à toute la racine du projet.

## 8. Configurer IIS

### 8.1 Créer le pool d'application

Dans IIS Manager :

1. **Application Pools** > **Add Application Pool**.
2. Nom : `ITSM-Nere-Mining`.
3. `.NET CLR version` : `No Managed Code`.
4. Pipeline : `Integrated`.
5. Identité : `ApplicationPoolIdentity`.

### 8.2 Créer le site

1. **Sites** > **Add Website**.
2. Site name : `ITSM Nere Mining`.
3. Physical path : `C:\Sites\itsm-nere-mining\public`.
4. Application pool : `ITSM-Nere-Mining`.
5. Binding : `http`, port `80`, host name `itsm-mine.local`.

Pour un accès local, ajouter dans `C:\Windows\System32\drivers\etc\hosts` :

```text
127.0.0.1 itsm-mine.local
```

### 8.3 Configurer PHP FastCGI

Dans IIS :

1. Sélectionner le serveur puis **FastCGI Settings**.
2. Ajouter `C:\PHP\php-cgi.exe`.
3. Ajouter une variable d'environnement : `PHP_FCGI_MAX_REQUESTS=10000`.
4. Dans le site, ajouter un **Handler Mapping** :
   - Request path : `*.php`
   - Module : `FastCgiModule`
   - Executable : `C:\PHP\php-cgi.exe`
   - Name : `PHP via FastCGI`

### 8.4 Ajouter le web.config Laravel

Le fichier `public\web.config` doit contenir une réécriture vers `index.php` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <directoryBrowse enabled="false" />
    <rewrite>
      <rules>
        <rule name="Laravel Front Controller" stopProcessing="true">
          <match url=".*" />
          <conditions logicalGrouping="MatchAll">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" />
        </rule>
      </rules>
    </rewrite>
    <security>
      <requestFiltering>
        <requestLimits maxAllowedContentLength="26214400" />
      </requestFiltering>
    </security>
  </system.webServer>
</configuration>
```

Tester ensuite :

```powershell
iisreset
Invoke-WebRequest http://itsm-mine.local/up
```

La réponse `/up` doit retourner un statut HTTP 200.

## 9. Worker de queue Laravel

Le projet utilise `QUEUE_CONNECTION=database`. Créer les tables nécessaires si elles ne sont pas présentes :

```powershell
php artisan queue:table
php artisan migrate --force
```

Installer le worker comme service Windows avec NSSM :

```powershell
nssm install ITSM-Queue C:\PHP\php.exe
nssm set ITSM-Queue AppDirectory C:\Sites\itsm-nere-mining
nssm set ITSM-Queue AppParameters "artisan queue:work database --sleep=3 --tries=3 --timeout=120"
nssm set ITSM-Queue Start SERVICE_AUTO_START
nssm start ITSM-Queue
```

Contrôler le service :

```powershell
Get-Service ITSM-Queue
Get-Content storage\logs\laravel.log -Tail 50
```

Après chaque mise à jour applicative :

```powershell
nssm restart ITSM-Queue
```

## 10. Scheduler Laravel

Créer une tâche planifiée Windows exécutée toutes les minutes :

```powershell
schtasks /Create /TN "ITSM Laravel Scheduler" /SC MINUTE /MO 1 /RU SYSTEM /TR "C:\PHP\php.exe C:\Sites\itsm-nere-mining\artisan schedule:run" /F
```

Le scheduler contrôle notamment les vérifications SLA configurées dans `bootstrap/app.php`.

Tester manuellement :

```powershell
php artisan schedule:list
php artisan schedule:run
php artisan sla:verifier
```

## 11. HTTPS et réseau interne

Pour un usage interne sérieux, utiliser un certificat TLS d'entreprise ou un certificat généré par la PKI interne. Dans IIS :

1. Ajouter un binding `https` sur le port `443`.
2. Sélectionner le certificat serveur.
3. Faire pointer `APP_URL` vers `https://itsm-mine.local`.
4. Ouvrir uniquement les ports nécessaires dans le pare-feu Windows.

Exemple pour autoriser HTTP et HTTPS :

```powershell
New-NetFirewallRule -DisplayName 'ITSM HTTP' -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
New-NetFirewallRule -DisplayName 'ITSM HTTPS' -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow
```

Ne pas exposer PostgreSQL sur Internet. Autoriser le port 5432 uniquement depuis le serveur applicatif ou le réseau d'administration.

## 12. Procédure de mise à jour

Depuis PowerShell administrateur :

```powershell
Set-Location C:\Sites\itsm-nere-mining
git pull origin main
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan down --render="errors::503" --retry=60
php artisan migrate --force
php artisan optimize:clear
php artisan storage:link
php artisan optimize
nssm restart ITSM-Queue
php artisan up
```

Si la compilation échoue, ne basculez pas la version en production. Restaurer le commit précédent, vérifier `npm ci` et relancer le build avant de remettre le site en ligne.

## 13. Sauvegardes

Créer un dossier de sauvegarde hors du répertoire web :

```powershell
New-Item -ItemType Directory -Force C:\Backups\ITSM | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd-HHmm'
pg_dump -h 127.0.0.1 -U nere_itsm -d nere_mining_itsm -F c -f "C:\Backups\ITSM\nere-$stamp.dump"
```

Planifier cette commande quotidiennement et tester régulièrement une restauration sur une base distincte.

Sauvegarder également :

- `C:\Sites\itsm-nere-mining\.env` dans un coffre sécurisé.
- `storage\app\public` si les pièces jointes doivent être conservées.
- Les certificats TLS et la configuration IIS.

## 14. Checklist de validation

- [ ] `php artisan about` affiche `APP_ENV=production` et `APP_DEBUG=false`.
- [ ] `php artisan migrate:status` ne signale aucune migration en attente.
- [ ] `/up` répond en HTTP 200.
- [ ] La page de connexion s'affiche en HTTP et HTTPS.
- [ ] La connexion admin fonctionne avec un mot de passe changé.
- [ ] Création et affichage d'un ticket fonctionnent.
- [ ] Ajout d'une pièce jointe fonctionne.
- [ ] Les permissions empêchent l'accès aux modules non autorisés.
- [ ] Un incident HSE peut être consulté et traité par un rôle habilité.
- [ ] Le worker `ITSM-Queue` est `Running`.
- [ ] `schedule:run` s'exécute sans erreur.
- [ ] Les logs Laravel et IIS sont surveillés.
- [ ] Une sauvegarde PostgreSQL récente est disponible.

## 15. Dépannage rapide

### Erreur 500 ou page blanche

```powershell
php artisan optimize:clear
Get-Content storage\logs\laravel.log -Tail 100
```

Vérifier `APP_KEY`, les permissions NTFS, `APP_DEBUG=false` et la connexion PostgreSQL.

### Erreur 404 sur les routes Laravel

- Vérifier l'installation IIS URL Rewrite.
- Vérifier que le site pointe vers `public`.
- Vérifier la présence de `public\web.config`.
- Redémarrer IIS avec `iisreset`.

### Erreur `could not find driver`

Vérifier que `pdo_pgsql` et `pgsql` sont activés dans le `php.ini` utilisé par IIS et par la CLI :

```powershell
php --ini
php -m | Select-String 'pgsql|pdo_pgsql'
```

### Les jobs restent en attente

```powershell
Get-Service ITSM-Queue
php artisan queue:failed
php artisan queue:retry all
```

### Les fichiers uploadés ne s'affichent pas

```powershell
php artisan storage:link
Test-Path public\storage
```

Vérifier également les permissions d'écriture sur `storage\app\public`.
