# Configuration locale avec PostgreSQL
Write-Host "=== Configuration Locale PostgreSQL ===" -ForegroundColor Green

# 1. Créer la base de données PostgreSQL
Write-Host "`n1. Création de la base de données PostgreSQL:" -ForegroundColor Cyan
try {
    # Essayer de créer la base de données
    $createDbCmd = 'psql -U postgres -c "CREATE DATABASE itsm_nere_mining;"'
    Write-Host "Création de la base de données itsm_nere_mining..."
    Invoke-Expression $createDbCmd 2>$null
    Write-Host "[OK] Base de données créée" -ForegroundColor Green
} catch {
    Write-Host "[INFO] Base de données existe peut-être déjà" -ForegroundColor Yellow
}

# 2. Installer les dépendances Composer
Write-Host "`n2. Installation des dépendances:" -ForegroundColor Cyan
try {
    composer install
    Write-Host "[OK] Dépendances installées" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec installation dépendances" -ForegroundColor Red
}

# 3. Générer la clé d'application
Write-Host "`n3. Génération de la clé d'application:" -ForegroundColor Cyan
try {
    php artisan key:generate
    Write-Host "[OK] Clé d'application générée" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec génération clé" -ForegroundColor Red
}

# 4. Nettoyer les caches
Write-Host "`n4. Nettoyage des caches:" -ForegroundColor Cyan
$clearCommands = @("config:clear", "cache:clear", "route:clear", "view:clear")
foreach ($cmd in $clearCommands) {
    try {
        php artisan $cmd
        Write-Host "[OK] $cmd" -ForegroundColor Green
    } catch {
        Write-Host "[WARNING] Échec $cmd" -ForegroundColor Yellow
    }
}

# 5. Tester la connexion à la base de données
Write-Host "`n5. Test de connexion à la base de données:" -ForegroundColor Cyan
try {
    $dbTest = php artisan migrate:status 2>&1
    if ($dbTest -match "Migration name" -or $dbTest -match "No migrations found") {
        Write-Host "[OK] Connexion PostgreSQL réussie" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Problème de connexion: $dbTest" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Test de connexion échoué" -ForegroundColor Red
}

# 6. Exécuter les migrations
Write-Host "`n6. Exécution des migrations:" -ForegroundColor Cyan
try {
    php artisan migrate --force
    Write-Host "[OK] Migrations exécutées" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec des migrations" -ForegroundColor Red
}

# 7. Peupler la base de données
Write-Host "`n7. Peuplement de la base de données:" -ForegroundColor Cyan
try {
    php artisan db:seed --force
    Write-Host "[OK] Base de données peuplée" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec du peuplement" -ForegroundColor Red
}

# 8. Créer le lien de stockage
Write-Host "`n8. Création du lien de stockage:" -ForegroundColor Cyan
try {
    php artisan storage:link
    Write-Host "[OK] Lien de stockage créé" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Lien de stockage déjà existant" -ForegroundColor Yellow
}

Write-Host "`n=== Configuration Locale Terminée ===" -ForegroundColor Green
Write-Host "Vous pouvez maintenant démarrer le serveur de développement:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host "Et accéder au site sur: http://localhost:8000" -ForegroundColor White