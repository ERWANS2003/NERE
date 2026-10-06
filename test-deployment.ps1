# Script de vérification du déploiement
# Utilisation: .\test-deployment.ps1

param(
    [string]$Url = "http://localhost",
    [switch]$Verbose = $false
)

Write-Host "🧪 Tests de vérification du déploiement ITSM NERE Mining..." -ForegroundColor Green

$TestResults = @()

function Test-Requirement {
    param($Name, $Test, $ErrorMessage = "")
    
    Write-Host "  Testing $Name..." -NoNewline
    
    try {
        $result = Invoke-Expression $Test
        if ($result) {
            Write-Host " ✅" -ForegroundColor Green
            $TestResults += @{ Name = $Name; Status = "PASS"; Message = "" }
            return $true
        } else {
            Write-Host " ❌" -ForegroundColor Red
            $TestResults += @{ Name = $Name; Status = "FAIL"; Message = $ErrorMessage }
            return $false
        }
    } catch {
        Write-Host " ❌" -ForegroundColor Red
        $TestResults += @{ Name = $Name; Status = "ERROR"; Message = $_.Exception.Message }
        return $false
    }
}

function Test-FileExists {
    param($Name, $Path)
    Test-Requirement $Name "Test-Path '$Path'" "Fichier manquant: $Path"
}

function Test-DirectoryWritable {
    param($Name, $Path)
    $testFile = Join-Path $Path "test_write_$(Get-Random).tmp"
    $test = "try { 'test' | Out-File -FilePath '$testFile' -ErrorAction Stop; Remove-Item '$testFile' -ErrorAction SilentlyContinue; `$true } catch { `$false }"
    Test-Requirement $Name $test "Répertoire non accessible en écriture: $Path"
}

Write-Host "`n📋 Vérification des prérequis système..." -ForegroundColor Yellow

# Tests des prérequis
Test-Requirement "PHP installé" "Get-Command php -ErrorAction SilentlyContinue" "PHP non trouvé dans PATH"
Test-Requirement "Composer installé" "Get-Command composer -ErrorAction SilentlyContinue" "Composer non trouvé dans PATH"
Test-Requirement "Node.js installé" "Get-Command node -ErrorAction SilentlyContinue" "Node.js non trouvé dans PATH"

# Tests des modules IIS
Write-Host "`n🌐 Vérification d'IIS..." -ForegroundColor Yellow
Test-Requirement "Module WebAdministration" "Get-Module WebAdministration -ListAvailable" "Module WebAdministration non disponible"

# Tests des fichiers Laravel
Write-Host "`n📁 Vérification des fichiers Laravel..." -ForegroundColor Yellow
Test-FileExists "Fichier artisan" "artisan"
Test-FileExists "Composer.json" "composer.json"
Test-FileExists "Package.json" "package.json"
Test-FileExists "Configuration .env" ".env"
Test-FileExists "Web.config public" "public/web.config"

# Tests des répertoires critiques
Write-Host "`n📂 Vérification des répertoires..." -ForegroundColor Yellow
Test-FileExists "Répertoire storage" "storage"
Test-FileExists "Répertoire bootstrap/cache" "bootstrap/cache"
Test-FileExists "Répertoire public" "public"
Test-FileExists "Répertoire vendor" "vendor"

# Tests des permissions
Write-Host "`n🔐 Vérification des permissions..." -ForegroundColor Yellow
Test-DirectoryWritable "Écriture storage" "storage"
Test-DirectoryWritable "Écriture bootstrap/cache" "bootstrap/cache"

# Tests Laravel
Write-Host "`n⚡ Tests Laravel..." -ForegroundColor Yellow
Test-Requirement "Configuration Laravel" "php artisan about 2>$null; `$LASTEXITCODE -eq 0" "Erreur de configuration Laravel"
Test-Requirement "Clé d'application" "(Get-Content .env) -match 'APP_KEY=base64:'" "Clé d'application non générée"
Test-Requirement "Connexion base de données" "php artisan tinker --execute='DB::connection()->getPdo()' 2>$null; `$LASTEXITCODE -eq 0" "Impossible de se connecter à la base de données"

# Tests des routes
Write-Host "`n🛣️ Tests des routes..." -ForegroundColor Yellow
Test-Requirement "Routes chargées" "php artisan route:list 2>$null; `$LASTEXITCODE -eq 0" "Erreur lors du chargement des routes"

# Tests web (si serveur accessible)
Write-Host "`n🌐 Tests web..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
    Test-Requirement "Page d'accueil accessible" "`$true" ""
    
    if ($response.StatusCode -eq 200) {
        Test-Requirement "Status HTTP 200" "`$true" ""
        
        # Test de la page de connexion
        try {
            $loginResponse = Invoke-WebRequest -Uri "$Url/connexion" -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
            Test-Requirement "Page de connexion accessible" "`$true" ""
        } catch {
            Test-Requirement "Page de connexion accessible" "`$false" "Page de connexion inaccessible"
        }
    } else {
        Test-Requirement "Status HTTP 200" "`$false" "Status reçu: $($response.StatusCode)"
    }
} catch {
    Test-Requirement "Page d'accueil accessible" "`$false" "Serveur web inaccessible: $($_.Exception.Message)"
}

# Tests des assets
Write-Host "`n🎨 Tests des assets..." -ForegroundColor Yellow
Test-FileExists "Assets CSS buildés" "public/build"
Test-Requirement "Vite manifest" "Test-Path 'public/build/manifest.json'" "Assets non buildés"

# Résumé des tests
Write-Host "`n📊 Résumé des tests:" -ForegroundColor Cyan
$PassedTests = ($TestResults | Where-Object { $_.Status -eq "PASS" }).Count
$FailedTests = ($TestResults | Where-Object { $_.Status -eq "FAIL" }).Count
$ErrorTests = ($TestResults | Where-Object { $_.Status -eq "ERROR" }).Count
$TotalTests = $TestResults.Count

Write-Host "  ✅ Réussis: $PassedTests" -ForegroundColor Green
Write-Host "  ❌ Échoués: $FailedTests" -ForegroundColor Red
Write-Host "  ⚠️  Erreurs: $ErrorTests" -ForegroundColor Yellow
Write-Host "  📊 Total: $TotalTests" -ForegroundColor White

if ($FailedTests -gt 0 -or $ErrorTests -gt 0) {
    Write-Host "`n⚠️ Problèmes détectés:" -ForegroundColor Red
    $TestResults | Where-Object { $_.Status -ne "PASS" } | ForEach-Object {
        Write-Host "  • $($_.Name): $($_.Message)" -ForegroundColor Yellow
    }
    
    Write-Host "`n🔧 Actions recommandées:" -ForegroundColor Yellow
    Write-Host "  1. Vérifiez le README-DEPLOYMENT.md pour les prérequis" -ForegroundColor White
    Write-Host "  2. Exécutez deploy-windows-server.ps1 -Force" -ForegroundColor White
    Write-Host "  3. Vérifiez la configuration de la base de données" -ForegroundColor White
    Write-Host "  4. Consultez les logs dans storage/logs/" -ForegroundColor White
    
    exit 1
} else {
    Write-Host "`n🎉 Tous les tests sont passés! L'application est prête." -ForegroundColor Green
    Write-Host "`n🔗 Accès à l'application:" -ForegroundColor Cyan
    Write-Host "  URL: $Url" -ForegroundColor White
    Write-Host "  Login: admin@nere-mining.com" -ForegroundColor White
    Write-Host "  Password: admin123" -ForegroundColor White
    
    exit 0
}