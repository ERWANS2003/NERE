# Guide de déploiement - Windows Server IIS

## 📋 Prérequis

### Serveur Windows
- Windows Server 2019 ou plus récent
- IIS installé avec les modules :
  - URL Rewrite Module
  - FastCGI Module
  - Application Request Routing (optionnel)

### Logiciels requis
1. **PHP 8.2+** avec extensions :
   - curl, fileinfo, mbstring, openssl, pdo, pdo_pgsql (ou pdo_sqlsrv), tokenizer, xml, zip, gd, intl

2. **Composer** (gestionnaire de dépendances PHP)

3. **Node.js 18+** avec npm

4. **PostgreSQL** ou **SQL Server** (base de données)

5. **Git** (pour les déploiements)

## 🚀 Déploiement rapide

### 1. Cloner le repository
```bash
git clone https://github.com/ERWANS2003/NERE.git C:\inetpub\wwwroot\itsm-nere
cd C:\inetpub\wwwroot\itsm-nere
```

### 2. Exécuter le script de déploiement
```powershell
.\deploy-windows-server.ps1
```

Le script va automatiquement :
- ✅ Vérifier les prérequis
- ✅ Installer les dépendances
- ✅ Configurer IIS
- ✅ Créer l'Application Pool
- ✅ Configurer les permissions
- ✅ Optimiser l'application

### 3. Configuration post-déploiement

#### Configuration de la base de données
1. Modifier le fichier `.env` :
```env
DB_CONNECTION=pgsql
DB_HOST=your-db-server
DB_PORT=5432
DB_DATABASE=nere_intranet_prod
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password
```

2. Exécuter les migrations :
```bash
php artisan migrate --force
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=UserSeeder
```

#### Configuration du domaine
1. Modifier dans `.env` :
```env
APP_URL=https://your-domain.com
FORCE_HTTPS=true
```

2. Configurer le certificat SSL dans IIS

#### Configuration email (optionnel)
```env
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

## 🛠️ Déploiement manuel détaillé

### 1. Installation des dépendances
```bash
# Dépendances PHP
composer install --optimize-autoloader --no-dev

# Dépendances Node.js
npm ci --only=production

# Construction des assets
npm run build
```

### 2. Configuration Laravel
```bash
# Configuration
cp .env.windows-server .env
php artisan key:generate

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Configuration IIS

#### Créer l'Application Pool
```powershell
New-WebAppPool -Name "ITSM-NERE-AppPool"
Set-ItemProperty -Path "IIS:\AppPools\ITSM-NERE-AppPool" -Name processModel.identityType -Value ApplicationPoolIdentity
```

#### Créer le site web
```powershell
New-Website -Name "ITSM-NERE-Mining" -ApplicationPool "ITSM-NERE-AppPool" -PhysicalPath "C:\inetpub\wwwroot\itsm-nere\public" -Port 80
```

#### Configurer les permissions
```powershell
$appPoolIdentity = "IIS AppPool\ITSM-NERE-AppPool"
icacls "C:\inetpub\wwwroot\itsm-nere\storage" /grant "${appPoolIdentity}:(OI)(CI)F" /T
icacls "C:\inetpub\wwwroot\itsm-nere\bootstrap\cache" /grant "${appPoolIdentity}:(OI)(CI)F" /T
```

### 4. Configuration de la base de données

#### PostgreSQL
```sql
CREATE DATABASE nere_intranet_prod;
CREATE USER nere_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE nere_intranet_prod TO nere_user;
```

#### SQL Server
```sql
CREATE DATABASE nere_intranet_prod;
CREATE LOGIN nere_user WITH PASSWORD = 'SecurePassword123!';
USE nere_intranet_prod;
CREATE USER nere_user FOR LOGIN nere_user;
ALTER ROLE db_owner ADD MEMBER nere_user;
```

## 🔧 Dépannage

### Erreurs courantes

#### 1. "Class not found" ou erreurs d'autoload
```bash
composer dump-autoload --optimize
php artisan clear-compiled
```

#### 2. Erreurs de permissions
```powershell
# Réinitialiser les permissions
icacls storage /reset /T
icacls "bootstrap\cache" /reset /T
# Réappliquer les permissions
.\deploy-windows-server.ps1 -Force
```

#### 3. Erreurs 500 Internal Server Error
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier les logs IIS : `C:\inetpub\logs\LogFiles`
3. Activer temporairement le debug :
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

#### 4. Erreurs de base de données
```bash
# Vérifier la connexion
php artisan tinker
>>> DB::connection()->getPdo();

# Recréer les tables
php artisan migrate:fresh --force
php artisan db:seed --force
```

### Commandes utiles

#### Mise à jour de l'application
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci --only=production && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

#### Nettoyage du cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Vérification de l'état
```bash
php artisan about
php artisan route:list
```

## 🔐 Sécurité

### Configuration recommandée

1. **Désactiver l'exposition des informations sensibles**
   ```env
   APP_DEBUG=false
   ```

2. **Configurer HTTPS**
   ```env
   FORCE_HTTPS=true
   SESSION_SECURE_COOKIE=true
   ```

3. **Restrictions d'accès aux fichiers sensibles**
   - Le fichier `web.config` dans `/public` bloque déjà l'accès aux fichiers sensibles
   - Vérifier que `.env` n'est pas accessible via le web

4. **Surveillance des logs**
   - Configurer une rotation des logs Laravel
   - Surveiller les logs IIS pour les tentatives d'intrusion

## 📞 Support

### Comptes de test créés
- **Email** : `admin@nere-mining.com`
- **Mot de passe** : `admin123`
- **Rôle** : Super administrateur

### Logs importants
- Laravel : `storage/logs/laravel.log`
- IIS : `C:\inetpub\logs\LogFiles`
- PHP : Configuré via `php.ini`

### Contacts techniques
Pour tout problème de déploiement, vérifiez d'abord :
1. Les logs d'erreur
2. La configuration de la base de données
3. Les permissions des fichiers
4. La configuration PHP/IIS