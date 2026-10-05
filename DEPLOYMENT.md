# 🚀 Guide de Déploiement — Nere Mining ITSM

## Déploiement initial (première fois)

```powershell
cd C:\inetpub\wwwroot\itsm-nere-mining

# 1. Cloner le repo
git clone https://github.com/ERWANS2003/NERE.git .

# 2. Exécuter le script de déploiement
.\deploy.ps1
```

## Mise à jour (après un git pull)

```powershell
cd C:\inetpub\wwwroot\itsm-nere-mining

git pull origin main

# Exécuter le script de déploiement
.\deploy.ps1
```

## Dépannage

### ❌ Erreur: "Target class [Database\Seeders\IntranetSeeder] does not exist"

**Cause:** Autoload Composer obsolète

**Solution:**
```powershell
composer dump-autoload -o
php artisan db:seed --class=IntranetSeeder
```

### ❌ Erreur: "rolldown failed to resolve import 'alpinejs'"

**Cause:** `node_modules` n'existe pas ou est incomplet

**Solution:**
```powershell
npm install
npm run build
```

### ❌ Erreur: "Command 'test' is not defined"

**Cause:** Artisan cache obsolète

**Solution:**
```powershell
php artisan cache:clear
php artisan config:clear
php artisan route:cache
```

### ❌ Erreur: "Your application doesn't have any routes matching the given criteria"

**Cause:** Routes non cachées après git pull

**Solution:**
```powershell
php artisan route:cache
php artisan route:list --path=intranet
```

## Vérification post-déploiement

```powershell
# Vérifier les routes
php artisan route:list --path=intranet

# Vérifier les tests
php artisan test tests/Feature/Intranet/ --no-coverage

# Vérifier les assets
ls public/build/manifest.json

# Vérifier les migrations
php artisan migrate:status
```

## Services et tâches planifiées

### Commande SLA Checker (toutes les heures)

```powershell
# Tester manuellement
php artisan intranet:check-sla

# Cron sur serveur Windows
# Ajouter dans Task Scheduler :
# Command: php.exe
# Arguments: C:\inetpub\wwwroot\itsm-nere-mining\artisan intranet:check-sla
# Schedule: Toutes les heures
```

### Queue des notifications

```powershell
# Sur serveur de production, configurer le driver dans .env
QUEUE_CONNECTION=database

# Faire tourner le worker en arrière-plan
php artisan queue:work --timeout=60 &
```

## Variables d'environnement requises (.env)

```env
# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=itsm_nere
DB_USERNAME=root
DB_PASSWORD=***

# Mail (pour notifications)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=***
MAIL_PASSWORD=***
MAIL_FROM_ADDRESS=noreply@nere.local

# Queue
QUEUE_CONNECTION=database
```

## Structure des fichiers principaux

```
app/
  ├── Http/Controllers/Intranet/
  │   ├── HomeController.php
  │   ├── FormController.php
  │   ├── SubmissionController.php
  │   └── Admin/
  │       ├── DashboardAdminController.php
  │       ├── DepartmentAdminController.php
  │       └── FormBuilderController.php
  ├── Models/Intranet/
  │   ├── Department.php
  │   ├── Form.php
  │   ├── FormField.php
  │   ├── Submission.php
  │   ├── WorkflowStep.php
  │   └── ...
  ├── Policies/Intranet/
  │   ├── DepartmentPolicy.php
  │   ├── FormPolicy.php
  │   └── SubmissionPolicy.php
  ├── Services/Intranet/
  │   └── WorkflowService.php
  └── Notifications/Intranet/
      ├── SubmissionStatusChanged.php
      └── SubmissionSlaBreach.php

routes/
  └── intranet.php (25 routes)

database/
  ├── migrations/
  │   └── 2026_0X_XX_*_create_intranet_*.php (11 migrations)
  └── seeders/
      └── IntranetSeeder.php

resources/
  └── views/intranet/
      ├── home.blade.php
      ├── forms/
      ├── submissions/
      └── admin/

tests/
  └── Feature/Intranet/
      └── AccessControlTest.php (13 tests)
```

## Logs et monitoring

```powershell
# Afficher les logs en temps réel
php artisan tail

# Logs spécifiques Intranet
Get-Content storage/logs/laravel.log | Select-String "Intranet"

# Vérifier les jobs en queue
php artisan queue:failed
```

---

**Version:** 1.0  
**Date:** 2026-09-07  
**Auteur:** Kiro Agent
