# Script de déploiement PowerShell pour ITSM NERE Mining
# Utilisation: .\deploy.ps1

Write-Host "🚀 Début du déploiement ITSM NERE Mining..." -ForegroundColor Green

# Vérification des prérequis
Write-Host "📋 Vérification des prérequis..." -ForegroundColor Yellow

# Vérification de PHP
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "❌ PHP n'est pas installé ou non trouvé dans PATH" -ForegroundColor Red
    exit 1
}

# Vérification de Composer
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Composer n'est pas installé ou non trouvé dans PATH" -ForegroundColor Red
    exit 1
}

# Vérification de Node.js
if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Node.js n'est pas installé ou non trouvé dans PATH" -ForegroundColor Red
    exit 1
}

# Vérification de npm
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Host "❌ NPM n'est pas installé ou non trouvé dans PATH" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Tous les prérequis sont satisfaits" -ForegroundColor Green

# Mise à jour du code depuis Git
Write-Host "📥 Récupération du code depuis Git..." -ForegroundColor Yellow
git pull origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erreur lors de la récupération du code Git" -ForegroundColor Red
    exit 1
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

# Copie du fichier de configuration de production
if (-not (Test-Path .env)) {
    Write-Host "📝 Copie du fichier de configuration de production..." -ForegroundColor Yellow
    Copy-Item .env.production .env
    Write-Host "⚠️  IMPORTANT: Modifiez le fichier .env avec vos paramètres de production" -ForegroundColor Magenta
} else {
    Write-Host "ℹ️  Fichier .env existant détecté, conservation des paramètres actuels" -ForegroundColor Cyan
}

# Génération de la clé d'application si nécessaire
$envContent = Get-Content .env -Raw
if (-not ($envContent -match "APP_KEY=base64:")) {
    Write-Host "🔑 Génération de la clé d'application..." -ForegroundColor Yellow
    php artisan key:generate --force
}

# Cache des configurations
Write-Host "🗂️ Mise en cache des configurations..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations de base de données
Write-Host "🗄️ Mise à jour de la base de données..." -ForegroundColor Yellow
php artisan migrate --force

# Nettoyage du cache
Write-Host "🧹 Nettoyage du cache..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Recache après nettoyage
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimisation Composer
Write-Host "⚡ Optimisation des autoloaders..." -ForegroundColor Yellow
composer dump-autoload --optimize

# Configuration des permissions (Windows)
Write-Host "🔐 Configuration des permissions..." -ForegroundColor Yellow
if (Test-Path storage) {
    icacls storage /grant Everyone:F /T /Q
}
if (Test-Path bootstrap/cache) {
    icacls "bootstrap/cache" /grant Everyone:F /T /Q
}

# Vérification de l'état de l'application
Write-Host "🔍 Vérification de l'état de l'application..." -ForegroundColor Yellow
php artisan about

Write-Host "✅ Déploiement terminé avec succès!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Actions post-déploiement à effectuer:" -ForegroundColor Cyan
Write-Host "1. Vérifiez le fichier .env avec vos paramètres de production" -ForegroundColor White
Write-Host "2. Configurez votre serveur web (IIS/Apache)" -ForegroundColor White
Write-Host "3. Configurez la base de données de production" -ForegroundColor White
Write-Host "4. Testez l'application" -ForegroundColor White
Write-Host ""
Write-Host "🌐 L'application est prête pour la production!" -ForegroundColor Green