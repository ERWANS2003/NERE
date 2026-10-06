# Script de correction automatique simplifie

Write-Host "=== CORRECTION AUTOMATIQUE ERREUR SERVEUR ===" -ForegroundColor Green

# 1. Creer les repertoires manquants
Write-Host ""
Write-Host "1. Creation des repertoires requis..." -ForegroundColor Cyan

$directories = @(
    "storage\logs",
    "storage\framework\cache",
    "storage\framework\sessions", 
    "storage\framework\views",
    "bootstrap\cache"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "CREE: $dir" -ForegroundColor Green
    } else {
        Write-Host "OK: $dir existe" -ForegroundColor Green
    }
}

# 2. Corriger les permissions
Write-Host ""
Write-Host "2. Correction des permissions..." -ForegroundColor Cyan

$permissionDirs = @("storage", "bootstrap\cache")
foreach ($dir in $permissionDirs) {
    if (Test-Path $dir) {
        Write-Host "Correction permissions: $dir" -ForegroundColor Yellow
        
        icacls $dir /grant "IIS_IUSRS:(OI)(CI)F" /T /Q 2>$null
        icacls $dir /grant "IUSR:(OI)(CI)F" /T /Q 2>$null
        
        Write-Host "OK: Permissions mises a jour pour $dir" -ForegroundColor Green
    }
}

# 3. Nettoyer le cache
Write-Host ""
Write-Host "3. Nettoyage du cache Laravel..." -ForegroundColor Cyan

$commands = @("config:clear", "route:clear", "view:clear", "cache:clear")
foreach ($cmd in $commands) {
    Write-Host "Execution: php artisan $cmd" -ForegroundColor Yellow
    php artisan $cmd 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "OK: $cmd reussi" -ForegroundColor Green
    } else {
        Write-Host "ECHEC: $cmd" -ForegroundColor Red
    }
}

# 4. Verifier/generer la cle APP
Write-Host ""
Write-Host "4. Verification de la cle d'application..." -ForegroundColor Cyan

if (Test-Path ".env") {
    $envContent = Get-Content ".env" -Raw
    if (-not ($envContent -match "APP_KEY=base64:")) {
        Write-Host "Generation de la cle d'application..." -ForegroundColor Yellow
        php artisan key:generate --force
        if ($LASTEXITCODE -eq 0) {
            Write-Host "OK: Cle generee" -ForegroundColor Green
        } else {
            Write-Host "ERREUR: Impossible de generer la cle" -ForegroundColor Red
        }
    } else {
        Write-Host "OK: Cle d'application presente" -ForegroundColor Green
    }
} else {
    Write-Host "ERREUR: Fichier .env manquant" -ForegroundColor Red
    if (Test-Path ".env.windows-server") {
        Write-Host "Copie de .env.windows-server vers .env..." -ForegroundColor Yellow
        Copy-Item ".env.windows-server" ".env"
        Write-Host "OK: Fichier .env cree" -ForegroundColor Green
    }
}

# 5. Optimiser l'autoloader
Write-Host ""
Write-Host "5. Optimisation de l'autoloader..." -ForegroundColor Cyan

composer dump-autoload --optimize 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "OK: Autoloader optimise" -ForegroundColor Green
} else {
    Write-Host "Reinstallation des dependances..." -ForegroundColor Yellow
    composer install --optimize-autoloader --no-dev 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "OK: Dependances reinstallees" -ForegroundColor Green
    } else {
        Write-Host "ERREUR: Probleme avec Composer" -ForegroundColor Red
    }
}

# 6. Test final
Write-Host ""
Write-Host "6. Test final..." -ForegroundColor Cyan

php artisan about 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "OK: Application Laravel fonctionne" -ForegroundColor Green
} else {
    Write-Host "ATTENTION: L'application a encore des problemes" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== CORRECTION TERMINEE ===" -ForegroundColor Green
Write-Host ""
Write-Host "PROCHAINES ETAPES:" -ForegroundColor Yellow
Write-Host "1. Testez votre site web maintenant" -ForegroundColor White
Write-Host "2. Si erreur 500 persiste, activez le debug:" -ForegroundColor White
Write-Host "   - Editez .env: APP_DEBUG=true" -ForegroundColor Gray
Write-Host "   - php artisan config:clear" -ForegroundColor Gray
Write-Host "   - Regardez l'erreur detaillee sur le site" -ForegroundColor Gray
Write-Host "3. N'oubliez pas de desactiver le debug apres: APP_DEBUG=false" -ForegroundColor Red