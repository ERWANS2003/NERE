#!/bin/bash

echo "Starting Vercel build for Laravel ITSM..."

# Install PHP dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate application key if not set
echo "Configuring Laravel..."
php artisan config:clear || true
php artisan cache:clear || true

# Build frontend assets if package.json exists
if [ -f "package.json" ]; then
    echo "Installing Node dependencies..."
    npm ci --only=production
    
    echo "Building frontend assets..."
    npm run build || npm run production || true
fi

# Clear and cache Laravel configurations for production
echo "Optimizing Laravel for production..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create required directories for Vercel
echo "Setting up directories..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache

# Set permissions (not needed in serverless but good practice)
chmod -R 755 storage bootstrap/cache

echo "Vercel build completed!"