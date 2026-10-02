# Déploiement de l’ITSM Néré Mining sur Windows Server avec IIS

Ce guide détaille le déploiement complet de l’application Laravel du projet sur un serveur Windows avec IIS, PHP FastCGI, PostgreSQL, le worker de queue et le scheduler Laravel.

Le projet est un Laravel 13 / PHP 8.3+, avec PostgreSQL et des tâches de fond via `QUEUE_CONNECTION=database` et `CACHE_STORE=database`.

---

## 1) Prérequis serveur

### Logiciels à installer

- Windows Server 2019 / 2022
- IIS (Internet Information Services)
- PHP 8.3 x64 Thread Safe ou non-thread-safe selon votre configuration
- Composer
- PostgreSQL 15+ ou 16+ recommandé
- Node.js 18+ et npm
- URL Rewrite for IIS (obligatoire pour Laravel)
- Optionnel : PHP Manager for IIS (très pratique)
- Optionnel : NSSM (pour exécuter le worker Laravel en service Windows)

### Recommandation d’architecture

- Dossier d’application : `E:\web\itsm-nere-mining`
- Dossier du site IIS : `E:\web\itsm-nere-mining\public`
- Base SQL : `nere_mining_itsm`
- Pool d’application IIS : `itsm-app-pool`
- Domaine ou URL publique : `https://itsm.example.com`

---

## 2) Installer IIS sur Windows Server

### Via le gestionnaire de serveur / PowerShell

Sur un vrai `Windows Server` (2019/2022), la commande correcte est :

```powershell
Install-WindowsFeature -Name Web-Server,Web-WebServer,Web-Common-Http,Web-Default-Doc,Web-Static-Content,Web-Http-Errors,Web-Http-Redirect,Web-Http-Logging,Web-Performance,Web-Stat-Compression,Web-Security,Web-Filtering,Web-Asp-Net45,Web-Net-Ext45,Web-ISAPI-Ext,Web-ISAPI-Filter,Web-Mgmt-Console,Web-Mgmt-Service,Web-CGI -IncludeAllSubFeature
```

> Si la commande échoue avec l’erreur `0x800F081F` ou `The request to add or remove features on the specified server failed`, cela signifie que les fichiers de source IIS ne sont pas disponibles. Il faut soit :
> - utiliser le Gestionnaire de serveur / Ajout de rôles et fonctionnalités,
> - soit monter l’ISO Windows Server et indiquer la source,
> - soit exécuter une commande de type :
>
> ```powershell
> Install-WindowsFeature -Name Web-Server,Web-WebServer -Source D:\sources\sxs
> ```
>
> avec `D:` remplacé par la lettre du lecteur d’installation Windows.

### Vérification

- Ouvrir IIS Manager
- Vérifier que le service IIS est bien actif
- Vérifier le rôle et les modules installés

### Modules IIS importants

Activer au minimum :
- CGI
- URL Rewrite
- Static Content
- HTTP Redirection
- Request Filtering
- IIS Management Console

---

## 3) Installer PHP sur Windows

### 3.1 Télécharger PHP

Télécharger le binaire Windows de PHP 8.3 depuis :

https://windows.php.net/download/

Choisir :
- Thread Safe si vous utilisez PHP via FastCGI dans IIS
- x64
- version compatible avec votre serveur

### 3.2 Configuration recommandée

Créer le dossier :

```powershell
New-Item -ItemType Directory -Path "C:\PHP83" -Force
```

Extraire l’archive PHP dans :

```text
C:\PHP83
```

### 3.3 Extensions PHP nécessaires

Ouvrir `php.ini` et vérifier que ces extensions sont activées :

```ini
extension=curl
extension=gd
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=zip
extension=bcmath
extension=intl
extension=fileinfo
extension=json
extension=ctype
extension=session
extension=tokenizer
```

### 3.4 Ajouter PHP à PATH

Dans les variables d’environnement système :

```powershell
$env:Path += ";C:\PHP83"
```

Ou bien ajouter `C:\PHP83` dans le PATH système.

### 3.5 Tester PHP

```powershell
php -v
```

Si la commande fonctionne, PHP est correctement installé.

---

## 4) Installer Composer

Télécharger Composer depuis :

https://getcomposer.org/download/

Puis installer le binaire et vérifier :

```powershell
composer -V
```

---

## 5) Installer PostgreSQL

### 5.1 Installer PostgreSQL

Télécharger PostgreSQL 15+ ou 16+.

Pendant l’installation :
- choisir un mot de passe fort pour l’utilisateur `postgres`
- noter le port (généralement `5432` ou `5433` si configuré)
- laisser le service PostgreSQL démarré

### 5.2 Créer la base et l’utilisateur

Se connecter à PostgreSQL via `psql` ou pgAdmin :

```sql
CREATE DATABASE nere_mining_itsm;
CREATE USER nere_user WITH PASSWORD 'VotreMotDePasseSecurise';
ALTER ROLE nere_user WITH SUPERUSER;
GRANT ALL PRIVILEGES ON DATABASE nere_mining_itsm TO nere_user;
```

> En production, un accès `SUPERUSER` est parfois utilisé uniquement pour l’installation initiale. Il est préférable de réduire les droits après le premier déploiement si nécessaire.

### 5.3 Vérifier l’accès

```powershell
Test-NetConnection -ComputerName localhost -Port 5432
```

---

## 6) Installer Node.js et compiler les assets du projet

### 6.1 Installer Node.js

Télécharger et installer Node 18+ LTS.

Vérifier :

```powershell
node -v
npm -v
```

### 6.2 Récupérer le projet

```powershell
cd E:\web
git clone https://github.com/ERWANS2003/NERE.git itsm-nere-mining
cd itsm-nere-mining
```

> Le dépôt réel à déployer est celui du projet. À adapter selon votre dépôt local ou Git remote.

---

## 7) Préparer le projet Laravel

### 7.1 Copier le fichier d’environnement

```powershell
Copy-Item .env.example .env
```

### 7.2 Configurer le `.env` de production

Ouvrir le fichier `.env` et configurer :

```env
APP_NAME="ITSM Nere Mining"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx
APP_URL=https://itsm.example.com
FORCE_HTTPS=true
TRUSTED_PROXIES=*

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nere_mining_itsm
DB_USERNAME=nere_user
DB_PASSWORD=VotreMotDePasseSecurise

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=it-support@example.com
MAIL_FROM_NAME="ITSM Nere Mining"
```

### 7.3 Générer la clé Laravel

```powershell
php artisan key:generate
```

### 7.4 Installer les dépendances PHP

```powershell
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```

### 7.5 Installer les dépendances Node

```powershell
npm install
npm run build
```

> En production, le build frontend est obligatoire. Le projet utilise Vite / build assets compilés.

---

## 8) Créer la base de données et exécuter les migrations

### 8.1 Vérifier les migrations

```powershell
php artisan migrate:status
```

### 8.2 Lancer les migrations en production

```powershell
php artisan migrate --force
```

### 8.3 Optionnel : seed initial

Si vous souhaitez installer les données de démarrage et le compte admin initial :

```powershell
php artisan db:seed --force
```

> Le compte admin par défaut indiqué dans le projet est souvent :
>
> - Email : `admin@nere-mining.bf`
> - Mot de passe : `admin123`
>
> Il faut impérativement le modifier après la mise en production.

### 8.4 Vérifier la présence des tables de queue et session

Si vous utilisez le driver `database` pour `QUEUE` et `SESSION`, vérifier la présence des tables Laravel :

```powershell
php artisan queue:table
php artisan session:table
```

Puis migrer à nouveau :

```powershell
php artisan migrate --force
```

---

## 9) Configurer IIS et le site web

### 9.1 Créer le pool d’application

Dans IIS Manager :
- Applications Pools
- Ajouter un pool d’application
- Nom : `itsm-app-pool`
- .NET CLR version : `No Managed Code`
- Gestion pipeline : `Integrated`
- Identité : `ApplicationPoolIdentity` (ou un compte Windows dédié si vous préférez)

### 9.2 Créer le site

- Sites
- Ajouter un site Web
- Nom : `ITSM`
- Physique : `E:\web\itsm-nere-mining\public`
- Port : `80` ou `443` selon votre configuration
- Pool d’application : `itsm-app-pool`

### 9.3 Définir le site sur le dossier public

Le dossier racine du site IIS doit être le dossier `public` du projet Laravel, pas le dossier racine du dépôt.

Cela évite d’exposer les fichiers de configuration, les dépendances et le code interne.

---

## 10) Configurer FastCGI / PHP dans IIS

### Option 1 : via PHP Manager for IIS

Installer PHP Manager pour IIS.

Puis dans IIS Manager :
- `Handler Mappings`
- Ajouter un mapping de gestionnaire
- Configurez le binaire PHP : `C:\PHP83\php-cgi.exe`
- Vérifier que les fichiers `.php` sont traités par PHP

### Option 2 : configuration manuelle

Dans le site IIS :
- `Handler Mappings`
- `Add Module Mapping`
- Request path : `*.php`
- Module : `FastCgiModule`
- Executable : `C:\PHP83\php-cgi.exe`
- Name : `PHP_via_FastCGI`

---

## 11) Configurer la réécriture d’URL avec URL Rewrite

Créez un fichier `web.config` dans le dossier `public` :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="LaravelRewrite" stopProcessing="true">
          <match url=".*" ignoreCase="false" />
          <conditions logicalGrouping="MatchAll">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
            <add input="{REQUEST_URI}" pattern="^/(favicon.ico|robots.txt|.*\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot|map))$" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php/{R:0}" appendQueryString="true" />
        </rule>
      </rules>
    </rewrite>

    <handlers>
      <add name="PHPFastCGI" path="*.php" verb="*" modules="FastCgiModule" scriptProcessor="C:\PHP83\php-cgi.exe" resourceType="Unspecified" requireAccess="Script" />
    </handlers>

    <staticContent>
      <mimeMap fileExtension=".js" mimeType="application/javascript" />
      <mimeMap fileExtension=".mjs" mimeType="application/javascript" />
      <mimeMap fileExtension=".json" mimeType="application/json" />
    </staticContent>
  </system.webServer>
</configuration>
```

### Vérification

Tester une URL comme :

```text
http://localhost/
```

et un routeur Laravel comme :

```text
http://localhost/login
```

Cela doit rediriger correctement vers `index.php` et charger l’application.

---

## 12) Configurer le certificat HTTPS / SSL

### 12.1 Installer un certificat

Vous pouvez utiliser :
- un certificat de votre AD / PKI interne
- un certificat Let's Encrypt si votre serveur est exposé
- un certificat commercial pour le domaine interne ou public

### 12.2 Lier le certificat au site IIS

Dans IIS Manager :
- Sélectionner le site
- `Bindings`
- Ajouter un binding HTTPS sur port 443
- Choisir votre certificat

### 12.3 Configurer Laravel pour HTTPS

Dans le `.env` :

```env
APP_URL=https://itsm.example.com
FORCE_HTTPS=true
TRUSTED_PROXIES=*
```

### 12.4 En production derrière IIS

Quand IIS termine le TLS, Laravel doit savoir que la requête initiale est en HTTPS. C’est le rôle de :
- `FORCE_HTTPS=true`
- `TRUSTED_PROXIES=*`

Cela permet d’éviter les redirections HTTP/HTTPS incorrectes et les problèmes de cookies sécurisés.

---

## 13) Définir les permissions de fichiers

### 13.1 Donner les droits d’écriture à l’application

Exécuter une commande PowerShell en admin :

```powershell
icacls "E:\web\itsm-nere-mining\storage" /grant "IIS AppPool\itsm-app-pool:(OI)(CI)(M)" /T
icacls "E:\web\itsm-nere-mining\bootstrap\cache" /grant "IIS AppPool\itsm-app-pool:(OI)(CI)(M)" /T
icacls "E:\web\itsm-nere-mining\public\storage" /grant "IIS AppPool\itsm-app-pool:(OI)(CI)(M)" /T
```

### 13.2 Vérifier les droits

Le pool d’application doit pouvoir :
- lire les fichiers du projet
- écrire dans `storage/`
- écrire dans `bootstrap/cache/`
- écrire dans les dossiers de cache / session / logs

---

## 14) Activer le cache Laravel en production

Dans le dossier du projet, lancer :

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

Ou en une seule commande :

```powershell
php artisan optimize
```

---

## 15) Démarrer le worker de queue Laravel

Le projet utilise `QUEUE_CONNECTION=database`. Donc il faut un worker en arrière-plan.

### Méthode simple : via PowerShell

```powershell
cd E:\web\itsm-nere-mining
php artisan queue:work --sleep=3 --tries=3 --timeout=3600 --daemon
```

### Méthode robuste : service Windows (recommandé)

Installer `NSSM` puis créer un service :

```powershell
nssm install itsm-queue "C:\PHP83\php.exe"
```

Puis configurer l’argument :

```powershell
nssm set itsm-queue AppParameters "E:\web\itsm-nere-mining\artisan queue:work --sleep=3 --tries=3 --timeout=3600 --daemon"
```

Et démarrer :

```powershell
nssm start itsm-queue
```

---

## 16) Planifier le scheduler Laravel (cron sur Windows)

Laravel utilise le scheduler via `schedule:run`.

### Option 1 : Task Scheduler de Windows

Créer une tâche planifiée :
- Nom : `ITSM Laravel Scheduler`
- Déclencher : tous les minutes
- Programme :

```powershell
C:\PHP83\php.exe E:\web\itsm-nere-mining\artisan schedule:run >> "E:\web\itsm-nere-mining\storage\logs\scheduler.log" 2>&1
```

### Vérification

Le scheduler doit exécuter les tâches planifiées comme :
- nettoyage des jobs
- rappel de notifications
- maintenance ou contrôles automatiques

---

## 17) Vérifications post-déploiement

### 17.1 Vérifier les logs

```powershell
Get-ChildItem "E:\web\itsm-nere-mining\storage\logs"
```

### 17.2 Tester l’application

Ouvrir dans le navigateur :

```text
https://itsm.example.com
```

Vérifier :
- page d’accueil ou login s’affiche
- CSS/JS chargés
- connexion admin possible
- DB accessible
- fichiers en écriture dans `storage`

### 17.3 Vérifier la queue

```powershell
php artisan queue:work --once
```

### 17.4 Vérifier les commandes Laravel

```powershell
php artisan about
php artisan route:list
```

---

## 18) Checklist de production

- [ ] IIS installé et fonctionnel
- [ ] PHP 8.3 installé
- [ ] Composer installé
- [ ] PostgreSQL installé et base créée
- [ ] Node.js + npm installés
- [ ] `.env` configuré pour la production
- [ ] `APP_KEY` généré
- [ ] `composer install --no-dev` OK
- [ ] `npm install` et `npm run build` OK
- [ ] `php artisan migrate --force` OK
- [ ] `php artisan config:cache` OK
- [ ] `php artisan route:cache` OK
- [ ] `php artisan view:cache` OK
- [ ] pool IIS créé
- [ ] site pointé sur `public/`
- [ ] `web.config` ajouté
- [ ] URL Rewrite installé
- [ ] certificat HTTPS configuré
- [ ] permissions de `storage` et `bootstrap/cache` OK
- [ ] worker queue exécuté
- [ ] scheduler Windows en place
- [ ] compte admin modifié en production

---

## 19) Dépannage courant

### Erreur 500

Vérifier :
- permissions sur `storage` et `bootstrap/cache`
- `APP_DEBUG=false` en production
- logs dans `storage/logs`
- fichier `.env` valide
- `php artisan optimize` à relancer

### URL bloquées / pages non trouvées

Vérifier :
- `web.config` dans le dossier `public`
- module URL Rewrite installé
- site IIS pointé sur `public`
- le handler PHP est bien configuré

### Problème de session / cookies

Vérifier :
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true` avec HTTPS
- `APP_URL` conforme au vrai domaine

### Problème de base de données

Vérifier :
- port PostgreSQL actif
- `DB_USERNAME` / `DB_PASSWORD` corrects
- l’utilisateur PostgreSQL a les droits sur la base
- `php artisan migrate --force` exécuté

### Problème de queue

Vérifier :
- `QUEUE_CONNECTION=database`
- tables de queue créées
- service Windows ou `php artisan queue:work` tourne bien

---

## 20) Exemple de configuration finale recommandée

### Fichier `.env` final

```env
APP_NAME="ITSM Nere Mining"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_URL=https://itsm.example.com
FORCE_HTTPS=true
TRUSTED_PROXIES=*

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nere_mining_itsm
DB_USERNAME=nere_user
DB_PASSWORD=VotreMotDePasseSecurise

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

### Commandes de mise en production

```powershell
cd E:\web\itsm-nere-mining
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

---

## 21) Conclusion

Le déploiement sur Windows Server + IIS est parfaitement possible pour ce projet Laravel. La clé est de respecter trois éléments indispensables :

1. installer PHP + FastCGI correctement,
2. pointer le site IIS vers le dossier `public/`,
3. configurer Laravel en production avec PostgreSQL, cache, queue, HTTPS et permissions Windows correctes.

Une fois ces points en place, l’application est prête à être utilisée en production avec un accès sécurisé via IIS et HTTPS.

---

## 22) Recommandation finale

Pour un environnement de production sérieux, je recommande :
- un site IIS avec certificat HTTPS valide,
- un pool d’application dédié,
- un service Windows pour la queue Laravel,
- un scheduler Task Scheduler pour le cron Laravel,
- un accès database dédié sécurisé,
- un compte admin modifié dès la mise en production.

Si tu veux, je peux aussi te préparer la version adaptée à ton vrai serveur :
- nom de domaine exact,
- port PostgreSQL,
- chemin d’installation Windows,
- certificat SSL,
- puis te donner les commandes prêtes à copier-coller pour ton serveur.
