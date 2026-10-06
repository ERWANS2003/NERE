# Script de diagnostic pour erreur serveur 500
# Utilisation: .\diagnose-server-error.ps1

Write-Host "🔍 Diagnostic d'erreur serveur ITSM NERE Mining..." -ForegroundColor Red

# Fonction pour afficher les dernières lignes d'un fichier log
function Show-LogTail {
    param($LogFile, $Lines = 20)
    
    if (Test-Path $LogFile) {
        Write-Host "`n📄 Dernières $Lines lignes de $LogFile :" -ForegroundColor Yellow
        Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Gray
        Get-Content $LogFile -Tail $Lines | ForEach-Object { Write-Host $_ -ForegroundColor White }
        Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Gray
    } else {
        Write-Host "❌ Fichier log non trouvé: $LogFile" -ForegroundColor Red
    }
}

# 1. Vérifier les logs Laravel
Write-Host "`n1️⃣ Vérification des logs Laravel..." -ForegroundColor Cyan

$LaravelLogPath = "storage\logs\laravel.log"
if (Test-Path $LaravelLogPath) {
    Show-LogTail $LaravelLogPath 30
} else {
    Write-Host "❌ Aucun log Laravel trouvé dans storage/logs/" -ForegroundColor Red
    
    # Vérifier si le répertoire existe et est accessible
    if (Test-Path "storage\logs") {
        Write-Host "✅ Répertoire storage/logs existe" -ForegroundColor Green
        $logFiles = Get-ChildItem "storage\logs\*.log" -ErrorAction SilentlyContinue
        if ($logFiles.Count -gt 0) {
            Write-Host "📁 Fichiers logs trouvés:" -ForegroundColor Yellow
            $logFiles | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor White }
        } else {
            Write-Host "⚠️ Aucun fichier log dans storage/logs/" -ForegroundColor Yellow
        }
    } else {
        Write-Host "❌ Répertoire storage/logs n'existe pas ou n'est pas accessible" -ForegroundColor Red
    }
}

# 2. Vérifier les permissions
Write-Host "`n2️⃣ Vérification des permissions..." -ForegroundColor Cyan

$directories = @("storage", "bootstrap\cache", "storage\logs", "storage\framework\cache", "storage\framework\sessions", "storage\framework\views")

foreach ($dir in $directories) {
    if (Test-Path $dir) {
        try {
            # Test d'écriture
            $testFile = Join-Path $dir "test_$(Get-Random).tmp"
            "test" | Out-File -FilePath $testFile -ErrorAction Stop
            Remove-Item $testFile -ErrorAction SilentlyContinue
            Write-Host "✅ $dir - Écriture OK" -ForegroundColor Green
        } catch {
            Write-Host "❌ $dir - Pas d'accès en écriture: $($_.Exception.Message)" -ForegroundColor Red
        }
    } else {
        Write-Host "❌ $dir - Répertoire manquant" -ForegroundColor Red
    }
}

# 3. Vérifier la configuration Laravel
Write-Host "`n3️⃣ Tests de configuration Laravel..." -ForegroundColor Cyan

# Test .env
if (Test-Path ".env") {
    Write-Host "✅ Fichier .env existe" -ForegroundColor Green
    
    # Vérifier la clé APP_KEY
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "APP_KEY=base64:") {
        Write-Host "✅ APP_KEY configurée" -ForegroundColor Green
    } else {
        Write-Host "❌ APP_KEY manquante ou incorrecte" -ForegroundColor Red
        Write-Host "🔧 Correction suggérée: php artisan key:generate" -ForegroundColor Yellow
    }
    
    # Vérifier APP_DEBUG
    if ($envContent -match "APP_DEBUG=true") {
        Write-Host "⚠️ APP_DEBUG=true (activer temporairement pour voir l'erreur détaillée)" -ForegroundColor Yellow
    } else {
        Write-Host "ℹ️ APP_DEBUG=false (production)" -ForegroundColor Cyan
    }
} else {
    Write-Host "❌ Fichier .env manquant" -ForegroundColor Red
    Write-Host "🔧 Correction suggérée: cp .env.example .env" -ForegroundColor Yellow
}

# Test de la base de données
Write-Host "`n🗄️ Test de connexion base de données..." -ForegroundColor Cyan
try {
    $dbTest = php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_OK'; } catch(Exception `$e) { echo 'DB_ERROR: ' . `$e->getMessage(); }" 2>&1
    if ($dbTest -like "*DB_OK*") {
        Write-Host "✅ Connexion base de données OK" -ForegroundColor Green
    } else {
        Write-Host "❌ Erreur de connexion base de données:" -ForegroundColor Red
        Write-Host $dbTest -ForegroundColor White
    }
} catch {
    Write-Host "❌ Impossible de tester la base de données: $($_.Exception.Message)" -ForegroundColor Red
}

# 4. Test des routes
Write-Host "`n🛣️ Test des routes..." -ForegroundColor Cyan
try {
    $routeTest = php artisan route:list --compact 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Routes chargées correctement" -ForegroundColor Green
    } else {
        Write-Host "❌ Erreur lors du chargement des routes:" -ForegroundColor Red
        Write-Host $routeTest -ForegroundColor White
    }
} catch {
    Write-Host "❌ Impossible de tester les routes: $($_.Exception.Message)" -ForegroundColor Red
}

# 5. Test de l'autoloader
Write-Host "`n📦 Test de l'autoloader..." -ForegroundColor Cyan
if (Test-Path "vendor\autoload.php") {
    Write-Host "✅ Autoloader Composer existe" -ForegroundColor Green
} else {
    Write-Host "❌ Autoloader Composer manquant" -ForegroundColor Red
    Write-Host "🔧 Correction suggérée: composer install" -ForegroundColor Yellow
}

# 6. Vérifier les logs IIS/PHP
Write-Host "`n6️⃣ Vérification des logs système..." -ForegroundColor Cyan

# Logs PHP
$phpLogPaths = @(
    "C:\Program Files\PHP\v8.2\logs\php_errors.log",
    "C:\php\logs\php_errors.log",
    "C:\Windows\Temp\php-errors.log"
)

foreach ($phpLog in $phpLogPaths) {
    if (Test-Path $phpLog) {
        Write-Host "📄 Log PHP trouvé: $phpLog" -ForegroundColor Yellow
        Show-LogTail $phpLog 15
        break
    }
}

# Logs IIS
$iisLogPath = "C:\inetpub\logs\LogFiles"
if (Test-Path $iisLogPath) {
    Write-Host "📄 Logs IIS disponibles dans: $iisLogPath" -ForegroundColor Yellow
    $latestIISLog = Get-ChildItem "$iisLogPath\*\*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latestIISLog) {
        Write-Host "📄 Log IIS le plus récent: $($latestIISLog.FullName)" -ForegroundColor Yellow
        Show-LogTail $latestIISLog.FullName 10
    }
}

# 7. Solutions rapides
Write-Host "`n🔧 Solutions rapides à essayer..." -ForegroundColor Green
Write-Host "1. Activer le debug temporairement:" -ForegroundColor White
Write-Host "   Modifier .env: APP_DEBUG=true" -ForegroundColor Gray
Write-Host "   php artisan config:clear" -ForegroundColor Gray

Write-Host "`n2. Régénérer les caches:" -ForegroundColor White
Write-Host "   php artisan config:clear" -ForegroundColor Gray
Write-Host "   php artisan route:clear" -ForegroundColor Gray
Write-Host "   php artisan view:clear" -ForegroundColor Gray
Write-Host "   php artisan cache:clear" -ForegroundColor Gray

Write-Host "`n3. Réinstaller les dépendances:" -ForegroundColor White
Write-Host "   composer install --optimize-autoloader" -ForegroundColor Gray

Write-Host "`n4. Corriger les permissions:" -ForegroundColor White
Write-Host "   icacls storage /grant ""IIS_IUSRS:(OI)(CI)F"" /T" -ForegroundColor Gray
Write-Host "   icacls bootstrap\cache /grant ""IIS_IUSRS:(OI)(CI)F"" /T" -ForegroundColor Gray

Write-Host "`n5. Vérifier la base de données:" -ForegroundColor White
Write-Host "   php artisan migrate:status" -ForegroundColor Gray
Write-Host "   php artisan migrate --force (si nécessaire)" -ForegroundColor Gray

Write-Host "`n📞 Une fois le problème identifié, désactivez APP_DEBUG=false en production!" -ForegroundColor Red