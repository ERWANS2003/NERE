#!/bin/bash

# Script de déploiement pour ITSM NERE Mining
# Utilisation: ./deploy.sh

echo "🚀 Début du déploiement ITSM NERE Mining..."

# Vérification des prérequis
echo "📋 Vérification des prérequis..."

# Vérification de PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi

# Vérification de Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé"
    exit 1
fi

# Vérification de Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Node.js n'est pas installé"
    exit 1
fi

# Vérification de npm
if ! command -v npm &> /dev/null; then
    echo "❌ NPM n'est pas installé"
    exit 1
fi

echo "✅ Tous les prérequis sont satisfaits"

# Mise à jour du code depuis Git
echo "📥 Récupération du code depuis Git..."
git pull origin main

# Installation des dépendances PHP
echo "📦 Installation des dépendances PHP..."
composer install --optimize-autoloader --no-dev --no-interaction

# Installation des dépendances Node.js
echo "📦 Installation des dépendances Node.js..."
npm ci --only=production

# Construction des assets
echo "🏗️ Construction des assets..."
npm run build

# Configuration de l'environnement
echo "⚙️ Configuration de l'environnement..."

# Copie du fichier de configuration de production
if [ ! -f .env ]; then
    echo "📝 Copie du fichier de configuration de production..."
    cp .env.production .env
    echo "⚠️  IMPORTANT: Modifiez le fichier .env avec vos paramètres de production"
else
    echo "ℹ️  Fichier .env existant détecté, conservation des paramètres actuels"
fi

# Génération de la clé d'application si nécessaire
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Cache des configurations
echo "🗂️ Mise en cache des configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations de base de données
echo "🗄️ Mise à jour de la base de données..."
php artisan migrate --force

# Nettoyage du cache
echo "🧹 Nettoyage du cache..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Recache après nettoyage
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimisation Composer
echo "⚡ Optimisation des autoloaders..."
composer dump-autoload --optimize

# Configuration des permissions
echo "🔐 Configuration des permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 644 storage/logs/*

# Vérification de l'état de l'application
echo "🔍 Vérification de l'état de l'application..."
php artisan about

echo "✅ Déploiement terminé avec succès!"
echo ""
echo "📋 Actions post-déploiement à effectuer:"
echo "1. Vérifiez le fichier .env avec vos paramètres de production"
echo "2. Configurez votre serveur web (Apache/Nginx)"
echo "3. Configurez la base de données de production"
echo "4. Testez l'application"
echo ""
echo "🌐 L'application est prête pour la production!"