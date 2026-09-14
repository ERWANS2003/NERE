#!/bin/bash

# Build script for Railway deployment
echo "=== Building application ==="

# Install composer dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install npm dependencies and build assets
echo "Installing and building frontend assets..."
npm install
npm run build

# Fix permissions
echo "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Clear caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo "=== Build complete ==="
