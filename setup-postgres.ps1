# Configuration PostgreSQL pour ITSM NERE Mining
Write-Host "=== Configuration PostgreSQL ===" -ForegroundColor Green

# 1. Vérifier si PostgreSQL est installé
Write-Host "`n1. Vérification de PostgreSQL:" -ForegroundColor Cyan
try {
    $psqlVersion = psql --version 2>$null
    if ($psqlVersion) {
        Write-Host "[OK] PostgreSQL installé: $psqlVersion" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] PostgreSQL non installé!" -ForegroundColor Red
        Write-Host "Téléchargez PostgreSQL sur: https://www.postgresql.org/download/windows/" -ForegroundColor Yellow
        exit 1
    }
} catch {
    Write-Host "[ERROR] PostgreSQL non trouvé dans le PATH" -ForegroundColor Red
    Write-Host "Ajoutez PostgreSQL au PATH ou installez-le" -ForegroundColor Yellow
    exit 1
}

# 2. Vérifier si le service PostgreSQL fonctionne
Write-Host "`n2. Vérification du service PostgreSQL:" -ForegroundColor Cyan
$postgresService = Get-Service -Name "*postgresql*" -ErrorAction SilentlyContinue
if ($postgresService) {
    foreach ($service in $postgresService) {
        if ($service.Status -eq "Running") {
            Write-Host "[OK] Service PostgreSQL en cours d'exécution: $($service.Name)" -ForegroundColor Green
        } else {
            Write-Host "[WARNING] Service PostgreSQL arrêté: $($service.Name)" -ForegroundColor Yellow
            Write-Host "Tentative de démarrage..." -ForegroundColor Cyan
            try {
                Start-Service $service.Name
                Write-Host "[OK] Service démarré" -ForegroundColor Green
            } catch {
                Write-Host "[ERROR] Impossible de démarrer le service" -ForegroundColor Red
            }
        }
    }
} else {
    Write-Host "[WARNING] Service PostgreSQL non trouvé" -ForegroundColor Yellow
}

# 3. Tester la connexion
Write-Host "`n3. Test de connexion PostgreSQL:" -ForegroundColor Cyan
try {
    $connectionTest = psql -U postgres -c "\l" 2>&1
    if ($connectionTest -match "List of databases") {
        Write-Host "[OK] Connexion PostgreSQL réussie" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Problème de connexion: $connectionTest" -ForegroundColor Yellow
        Write-Host "Vérifiez le mot de passe dans le fichier .env" -ForegroundColor Cyan
    }
} catch {
    Write-Host "[ERROR] Impossible de se connecter à PostgreSQL" -ForegroundColor Red
    Write-Host "Vérifiez que le service est démarré et les identifiants sont corrects" -ForegroundColor Yellow
}

# 4. Créer la base de données
Write-Host "`n4. Création de la base de données:" -ForegroundColor Cyan
try {
    $createDbCmd = 'psql -U postgres -c "CREATE DATABASE nere_mining_itsm;"'
    Write-Host "Création de la base de données 'nere_mining_itsm'..."
    $result = Invoke-Expression $createDbCmd 2>&1
    
    if ($result -match "CREATE DATABASE" -or $result -match "already exists") {
        Write-Host "[OK] Base de données créée ou existe déjà" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Résultat: $result" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Erreur lors de la création: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Configurer Laravel
Write-Host "`n5. Configuration Laravel:" -ForegroundColor Cyan

# Nettoyer les caches
try {
    php artisan config:clear
    php artisan cache:clear
    Write-Host "[OK] Caches nettoyés" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Erreur lors du nettoyage des caches" -ForegroundColor Yellow
}

# 6. Tester la connexion Laravel
Write-Host "`n6. Test connexion Laravel PostgreSQL:" -ForegroundColor Cyan
try {
    $laravelTest = php artisan migrate:status 2>&1
    if ($laravelTest -match "Migration name" -or $laravelTest -match "No migrations found") {
        Write-Host "[OK] Laravel connecté à PostgreSQL!" -ForegroundColor Green
    } else {
        Write-Host "[WARNING] Problème Laravel: $laravelTest" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ERROR] Test Laravel échoué" -ForegroundColor Red
}

# 7. Exécuter les migrations
Write-Host "`n7. Migrations de la base de données:" -ForegroundColor Cyan
try {
    php artisan migrate --force
    Write-Host "[OK] Migrations exécutées" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec des migrations" -ForegroundColor Red
}

# 8. Peupler la base de données
Write-Host "`n8. Peuplement initial:" -ForegroundColor Cyan
try {
    php artisan db:seed --force
    Write-Host "[OK] Données initiales ajoutées" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Échec du peuplement" -ForegroundColor Red
}

Write-Host "`n=== Configuration PostgreSQL Terminée ===" -ForegroundColor Green
Write-Host "Vous pouvez maintenant démarrer le serveur:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host "Et accéder au site sur: http://localhost:8000" -ForegroundColor White
Write-Host "`nIdentifiants de test:" -ForegroundColor Cyan
Write-Host "  Email: admin@nere-mining.com" -ForegroundColor White
Write-Host "  Mot de passe: admin123" -ForegroundColor White