# Script de diagnostic simplifie pour erreur serveur 500

Write-Host "=== DIAGNOSTIC ERREUR SERVEUR ITSM NERE MINING ===" -ForegroundColor Red

# 1. Verifier les logs Laravel
Write-Host ""
Write-Host "1. Verification des logs Laravel..." -ForegroundColor Cyan

$LaravelLogPath = "storage\logs\laravel.log"
if (Test-Path $LaravelLogPath) {
    Write-Host "Log Laravel trouve. Dernieres lignes:" -ForegroundColor Green
    Get-Content $LaravelLogPath -Tail 20 | Write-Host -ForegroundColor White
} else {
    Write-Host "ERREUR: Aucun log Laravel trouve" -ForegroundColor Red
    if (Test-Path "storage\logs") {
        $logFiles = Get-ChildItem "storage\logs\*.log" -ErrorAction SilentlyContinue
        if ($logFiles) {
            Write-Host "Fichiers logs trouves:" -ForegroundColor Yellow
            $logFiles | ForEach-Object { Write-Host "- $($_.Name)" }
        }
    } else {
        Write-Host "ERREUR: Repertoire storage\logs manquant" -ForegroundColor Red
    }
}

# 2. Verifier les permissions
Write-Host ""
Write-Host "2. Verification des permissions..." -ForegroundColor Cyan

$directories = @("storage", "bootstrap\cache")
foreach ($dir in $directories) {
    if (Test-Path $dir) {
        try {
            $testFile = "$dir\test_$(Get-Random).tmp"
            "test" | Out-File -FilePath $testFile
            Remove-Item $testFile -ErrorAction SilentlyContinue
            Write-Host "OK: $dir - Ecriture autorisee" -ForegroundColor Green
        } catch {
            Write-Host "ERREUR: $dir - Pas d'acces en ecriture" -ForegroundColor Red
        }
    } else {
        Write-Host "ERREUR: $dir - Repertoire manquant" -ForegroundColor Red
    }
}

# 3. Verifier .env
Write-Host ""
Write-Host "3. Verification du fichier .env..." -ForegroundColor Cyan

if (Test-Path ".env") {
    Write-Host "OK: Fichier .env existe" -ForegroundColor Green
    
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "APP_KEY=base64:") {
        Write-Host "OK: APP_KEY configuree" -ForegroundColor Green
    } else {
        Write-Host "ERREUR: APP_KEY manquante" -ForegroundColor Red
        Write-Host "SOLUTION: Executez 'php artisan key:generate'" -ForegroundColor Yellow
    }
    
    if ($envContent -match "APP_DEBUG=true") {
        Write-Host "INFO: Mode debug active" -ForegroundColor Yellow
    } else {
        Write-Host "INFO: Mode production (debug desactive)" -ForegroundColor Cyan
    }
} else {
    Write-Host "ERREUR: Fichier .env manquant" -ForegroundColor Red
    Write-Host "SOLUTION: Copiez .env.windows-server vers .env" -ForegroundColor Yellow
}

# 4. Test base de donnees
Write-Host ""
Write-Host "4. Test connexion base de donnees..." -ForegroundColor Cyan

try {
    $dbResult = php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>&1
    if ($dbResult -like "*DB_OK*") {
        Write-Host "OK: Connexion base de donnees reussie" -ForegroundColor Green
    } else {
        Write-Host "ERREUR: Probleme de connexion base de donnees" -ForegroundColor Red
        Write-Host $dbResult -ForegroundColor White
    }
} catch {
    Write-Host "ERREUR: Impossible de tester la base de donnees" -ForegroundColor Red
}

# 5. Test routes
Write-Host ""
Write-Host "5. Test des routes Laravel..." -ForegroundColor Cyan

try {
    php artisan route:list --compact | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "OK: Routes chargees correctement" -ForegroundColor Green
    } else {
        Write-Host "ERREUR: Probleme lors du chargement des routes" -ForegroundColor Red
    }
} catch {
    Write-Host "ERREUR: Impossible de tester les routes" -ForegroundColor Red
}

# 6. Verifier vendor
Write-Host ""
Write-Host "6. Verification de l'autoloader..." -ForegroundColor Cyan

if (Test-Path "vendor\autoload.php") {
    Write-Host "OK: Autoloader Composer present" -ForegroundColor Green
} else {
    Write-Host "ERREUR: Autoloader manquant" -ForegroundColor Red
    Write-Host "SOLUTION: Executez 'composer install'" -ForegroundColor Yellow
}

# Solutions recommandees
Write-Host ""
Write-Host "=== SOLUTIONS RAPIDES ===" -ForegroundColor Green
Write-Host ""
Write-Host "1. Activer le debug temporairement:" -ForegroundColor White
Write-Host "   - Editez le fichier .env" -ForegroundColor Gray
Write-Host "   - Changez APP_DEBUG=false en APP_DEBUG=true" -ForegroundColor Gray
Write-Host "   - Executez: php artisan config:clear" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Nettoyer les caches:" -ForegroundColor White
Write-Host "   php artisan config:clear" -ForegroundColor Gray
Write-Host "   php artisan route:clear" -ForegroundColor Gray
Write-Host "   php artisan view:clear" -ForegroundColor Gray
Write-Host "   php artisan cache:clear" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Corriger les permissions:" -ForegroundColor White
Write-Host "   icacls storage /grant ""IIS_IUSRS:(OI)(CI)F"" /T" -ForegroundColor Gray
Write-Host "   icacls bootstrap\cache /grant ""IIS_IUSRS:(OI)(CI)F"" /T" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Si probleme de base de donnees:" -ForegroundColor White
Write-Host "   php artisan migrate --force" -ForegroundColor Gray
Write-Host ""
Write-Host "IMPORTANT: Desactivez APP_DEBUG=false apres resolution!" -ForegroundColor Red