# Script de correction automatique des erreurs courantes
# Utilisation: .\fix-server-error.ps1

param(
    [switch]$Force = $false,
    [switch]$DebugMode = $false
)

Write-Host "🔧 Correction automatique des erreurs serveur..." -ForegroundColor Green

# 1. Activer le debug temporairement si demandé
if ($DebugMode) {
    Write-Host "`n🐛 Activation du mode debug..." -ForegroundColor Yellow
    if (Test-Path ".env") {
        $envContent = Get-Content ".env" -Raw
        $envContent = $envContent -replace "APP_DEBUG=false", "APP_DEBUG=true"
        $envContent = $envContent -replace "LOG_LEVEL=error", "LOG_LEVEL=debug"
        $envContent | Out-File ".env" -Encoding UTF8
        Write-Host "✅ Mode debug activé (PENSEZ À LE DÉSACTIVER APRÈS)" -ForegroundColor Red
    }
}

# 2. Créer les répertoires manquants
Write-Host "`n📁 Création des répertoires requis..." -ForegroundColor Cyan
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
        Write-Host "✅ Créé: $dir" -ForegroundColor Green
    } else {
        Write-Host "✅ Existe: $dir" -ForegroundColor Green
    }
}

# 3. Corriger les permissions
Write-Host "`n🔐 Correction des permissions..." -ForegroundColor Cyan
$permissionDirs = @("storage", "bootstrap\cache")

foreach ($dir in $permissionDirs) {
    if (Test-Path $dir) {
        Write-Host "🔒 Correction permissions: $dir" -ForegroundColor Yellow
        
        # Permissions pour IIS
        icacls $dir /grant "IIS_IUSRS:(OI)(CI)F" /T /Q 2>$null
        icacls $dir /grant "IUSR:(OI)(CI)F" /T /Q 2>$null
        
        # Permissions pour l'Application Pool (si spécifié)
        $appPoolIdentity = "IIS AppPool\ITSM-NERE-AppPool"
        icacls $dir /grant "${appPoolIdentity}:(OI)(CI)F" /T /Q 2>$null
        
        Write-Host "✅ Permissions mises à jour: $dir" -ForegroundColor Green
    }
}

# 4. Nettoyage complet du cache
Write-Host "`n🧹 Nettoyage complet du cache..." -ForegroundColor Cyan
$cacheCommands = @(
    "config:clear",
    "route:clear", 
    "view:clear",
    "cache:clear"
)

foreach ($cmd in $cacheCommands) {
    Write-Host "🗑️ php artisan $cmd" -ForegroundColor Yellow
    php artisan $cmd 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ $cmd réussi" -ForegroundColor Green
    } else {
        Write-Host "⚠️ $cmd échoué" -ForegroundColor Red
    }
}

# 5. Régénération de la clé si nécessaire
Write-Host "`n🔑 Vérification de la clé d'application..." -ForegroundColor Cyan
if (Test-Path ".env") {
    $envContent = Get-Content ".env" -Raw
    if (-not ($envContent -match "APP_KEY=base64:")) {
        Write-Host "🔑 Génération de la clé d'application..." -ForegroundColor Yellow
        php artisan key:generate --force
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Clé générée avec succès" -ForegroundColor Green
        } else {
            Write-Host "❌ Erreur lors de la génération de la clé" -ForegroundColor Red
        }
    } else {
        Write-Host "✅ Clé d'application présente" -ForegroundColor Green
    }
}

# 6. Optimisation de l'autoloader
Write-Host "`n📦 Optimisation de l'autoloader..." -ForegroundColor Cyan
composer dump-autoload --optimize 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Autoloader optimisé" -ForegroundColor Green
} else {
    Write-Host "⚠️ Problème avec l'optimisation de l'autoloader" -ForegroundColor Yellow
    Write-Host "🔄 Tentative de réinstallation..." -ForegroundColor Cyan
    composer install --optimize-autoloader --no-dev 2>$null
}

# 7. Test de la base de données et migrations
Write-Host "`n🗄️ Vérification de la base de données..." -ForegroundColor Cyan
try {
    $dbTest = php artisan migrate:status 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Base de données accessible" -ForegroundColor Green
        
        # Vérifier s'il y a des migrations en attente
        if ($dbTest -like "*Pending*") {
            Write-Host "⚠️ Migrations en attente détectées" -ForegroundColor Yellow
            if ($Force) {
                Write-Host "🔄 Exécution des migrations..." -ForegroundColor Cyan
                php artisan migrate --force
            } else {
                Write-Host "💡 Exécutez: php artisan migrate --force" -ForegroundColor Yellow
            }
        }
    } else {
        Write-Host "❌ Problème de base de données détecté" -ForegroundColor Red
        Write-Host $dbTest -ForegroundColor White
    }
} catch {
    Write-Host "❌ Impossible de vérifier la base de données" -ForegroundColor Red
}

# 8. Regeneration du cache de production
Write-Host "`n⚡ Régénération du cache de production..." -ForegroundColor Cyan
if (-not $DebugMode) {
    php artisan config:cache 2>$null
    php artisan route:cache 2>$null
    php artisan view:cache 2>$null
    Write-Host "✅ Cache de production régénéré" -ForegroundColor Green
}

# 9. Test final
Write-Host "`n🧪 Test final de l'application..." -ForegroundColor Cyan
try {
    $aboutTest = php artisan about 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Application Laravel fonctionne correctement" -ForegroundColor Green
    } else {
        Write-Host "❌ L'application Laravel a encore des problèmes:" -ForegroundColor Red
        Write-Host $aboutTest -ForegroundColor White
    }
} catch {
    Write-Host "❌ Impossible de tester l'application" -ForegroundColor Red
}

# 10. Instructions finales
Write-Host "`n📋 Étapes de vérification:" -ForegroundColor Cyan
Write-Host "1. Testez votre site web maintenant" -ForegroundColor White
Write-Host "2. Si l'erreur persiste, exécutez: .\diagnose-server-error.ps1" -ForegroundColor White
Write-Host "3. Vérifiez les logs dans storage\logs\ pour plus de détails" -ForegroundColor White

if ($DebugMode) {
    Write-Host "`n⚠️ IMPORTANT: Mode debug activé!" -ForegroundColor Red
    Write-Host "Après avoir identifié le problème, désactivez le debug:" -ForegroundColor Yellow
    Write-Host "1. Modifier .env: APP_DEBUG=false" -ForegroundColor Gray
    Write-Host "2. Modifier .env: LOG_LEVEL=error" -ForegroundColor Gray
    Write-Host "3. php artisan config:cache" -ForegroundColor Gray
}

Write-Host "`n🎉 Correction terminée!" -ForegroundColor Green