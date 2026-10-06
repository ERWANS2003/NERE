# Script de déploiement pour Windows Server IIS
# Utilisation: .\deploy-windows-server.ps1

param(
    [string]$Environment = "production",
    [switch]$Force = $false
)

Write-Host "🚀 Déploiement ITSM NERE Mining sur Windows Server..." -ForegroundColor Green

# Configuration
$SiteName = "ITSM-NERE-Mining"
$AppPoolName = "ITSM-NERE-AppPool"
$SitePath = "C:\inetpub\wwwroot\itsm-nere"
$LogPath = "C:\logs\itsm-nere"

# Vérification des prérequis
Write-Host "📋 Vérification des prérequis..." -ForegroundColor Yellow

# Vérification de PHP
try {
    $phpVersion = php -v
    Write-Host "✅ PHP installé: $($phpVersion[0])" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP non trouvé. Veuillez installer PHP 8.2+ avec les extensions requises." -ForegroundColor Red
    exit 1
}

# Vérification de Composer
try {
    $composerVersion = composer --version
    Write-Host "✅ Composer installé: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Composer non trouvé. Veuillez installer Composer." -ForegroundColor Red
    exit 1
}

# Vérification de Node.js
try {
    $nodeVersion = node --version
    Write-Host "✅ Node.js installé: $nodeVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Node.js non trouvé. Veuillez installer Node.js." -ForegroundColor Red
    exit 1
}

# Vérification d'IIS
try {
    Import-Module WebAdministration
    Write-Host "✅ IIS disponible" -ForegroundColor Green
} catch {
    Write-Host "❌ IIS non disponible. Veuillez installer IIS avec le module WebAdministration." -ForegroundColor Red
    exit 1
}

# Mise à jour du code depuis Git
Write-Host "📥 Mise à jour du code depuis Git..." -ForegroundColor Yellow
git pull origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors du pull Git" -ForegroundColor Red
    if (!$Force) {
        exit 1
    }
}

# Installation des dépendances PHP
Write-Host "📦 Installation des dépendances PHP..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev --no-interaction

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de l'installation des dépendances PHP" -ForegroundColor Red
    exit 1
}

# Installation des dépendances Node.js
Write-Host "📦 Installation des dépendances Node.js..." -ForegroundColor Yellow
npm ci --only=production

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de l'installation des dépendances Node.js" -ForegroundColor Red
    exit 1
}

# Construction des assets
Write-Host "🏗️ Construction des assets..." -ForegroundColor Yellow
npm run build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de la construction des assets" -ForegroundColor Red
    exit 1
}

# Configuration de l'environnement
Write-Host "⚙️ Configuration de l'environnement..." -ForegroundColor Yellow

# Copie du fichier de configuration
if (-not (Test-Path .env)) {
    if (Test-Path .env.production) {
        Write-Host "📝 Copie du fichier de configuration de production..." -ForegroundColor Yellow
        Copy-Item .env.production .env
        Write-Host "⚠️  IMPORTANT: Vérifiez et modifiez le fichier .env avec vos paramètres" -ForegroundColor Magenta
    } else {
        Write-Host "❌ Aucun fichier de configuration trouvé (.env ou .env.production)" -ForegroundColor Red
        exit 1
    }
}

# Génération de la clé d'application si nécessaire
$envContent = Get-Content .env -Raw
if (-not ($envContent -match "APP_KEY=base64:")) {
    Write-Host "🔑 Génération de la clé d'application..." -ForegroundColor Yellow
    php artisan key:generate --force
}

# Configuration d'IIS
Write-Host "🌐 Configuration d'IIS..." -ForegroundColor Yellow

# Création du répertoire de logs
if (-not (Test-Path $LogPath)) {
    New-Item -ItemType Directory -Path $LogPath -Force
    Write-Host "✅ Répertoire de logs créé: $LogPath" -ForegroundColor Green
}

# Création de l'Application Pool
if (Get-IISAppPool -Name $AppPoolName -ErrorAction SilentlyContinue) {
    Write-Host "⚠️ Application Pool '$AppPoolName' existe déjà" -ForegroundColor Yellow
    if ($Force) {
        Remove-WebAppPool -Name $AppPoolName
        Write-Host "🗑️ Application Pool supprimé" -ForegroundColor Yellow
    }
}

if (-not (Get-IISAppPool -Name $AppPoolName -ErrorAction SilentlyContinue)) {
    New-WebAppPool -Name $AppPoolName
    Set-ItemProperty -Path "IIS:\AppPools\$AppPoolName" -Name processModel.identityType -Value ApplicationPoolIdentity
    Set-ItemProperty -Path "IIS:\AppPools\$AppPoolName" -Name recycling.periodicRestart.time -Value "00:00:00"
    Set-ItemProperty -Path "IIS:\AppPools\$AppPoolName" -Name processModel.idleTimeout -Value "00:00:00"
    Write-Host "✅ Application Pool '$AppPoolName' créé" -ForegroundColor Green
}

# Création du site web
if (Get-Website -Name $SiteName -ErrorAction SilentlyContinue) {
    Write-Host "⚠️ Site web '$SiteName' existe déjà" -ForegroundColor Yellow
    if ($Force) {
        Remove-Website -Name $SiteName
        Write-Host "🗑️ Site web supprimé" -ForegroundColor Yellow
    }
}

if (-not (Get-Website -Name $SiteName -ErrorAction SilentlyContinue)) {
    New-Website -Name $SiteName -ApplicationPool $AppPoolName -PhysicalPath (Get-Location).Path -Port 80
    Write-Host "✅ Site web '$SiteName' créé sur le port 80" -ForegroundColor Green
}

# Configuration des permissions
Write-Host "🔐 Configuration des permissions..." -ForegroundColor Yellow

# Permissions pour l'Application Pool Identity
$appPoolIdentity = "IIS AppPool\$AppPoolName"

# Permissions sur les dossiers Laravel
$foldersToPermission = @("storage", "bootstrap\cache", "public")

foreach ($folder in $foldersToPermission) {
    if (Test-Path $folder) {
        Write-Host "🔒 Configuration des permissions pour $folder..." -ForegroundColor Cyan
        icacls $folder /grant "${appPoolIdentity}:(OI)(CI)F" /T /Q
        icacls $folder /grant "IIS_IUSRS:(OI)(CI)RX" /T /Q
    }
}

# Permissions sur le fichier .env
if (Test-Path .env) {
    icacls .env /grant "${appPoolIdentity}:R" /Q
}

# Cache des configurations Laravel
Write-Host "🗂️ Mise en cache des configurations..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache  
php artisan view:cache

# Migrations de base de données
Write-Host "🗄️ Mise à jour de la base de données..." -ForegroundColor Yellow
php artisan migrate --force

if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️ Erreur lors des migrations (vérifiez la configuration de la base de données)" -ForegroundColor Yellow
}

# Optimisation finale
Write-Host "⚡ Optimisation finale..." -ForegroundColor Yellow
composer dump-autoload --optimize

# Test de l'application
Write-Host "🔍 Test de l'application..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Application accessible sur http://localhost" -ForegroundColor Green
    } else {
        Write-Host "⚠️ Application répond avec le code: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️ Impossible de tester l'application: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host "✅ Déploiement terminé avec succès!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Actions post-déploiement:" -ForegroundColor Cyan
Write-Host "1. Vérifiez le fichier .env avec vos paramètres de production" -ForegroundColor White
Write-Host "2. Testez l'application sur http://localhost" -ForegroundColor White
Write-Host "3. Vérifiez les logs dans: $LogPath" -ForegroundColor White
Write-Host "4. Configurez HTTPS si nécessaire" -ForegroundColor White
Write-Host ""
Write-Host "🌐 L'application est maintenant déployée sur IIS!" -ForegroundColor Green

# Affichage des informations de connexion
Write-Host ""
Write-Host "🔐 Informations de connexion de test:" -ForegroundColor Magenta
Write-Host "Email: admin@nere-mining.com" -ForegroundColor White
Write-Host "Mot de passe: admin123" -ForegroundColor White