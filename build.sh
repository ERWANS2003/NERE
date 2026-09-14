#!/bin/bash
set -e

echo "🚀 Starting ITSM deployment build..."

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Install Node dependencies
echo "📦 Installing Node dependencies..."
npm install --omit=dev

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Cache config and views
echo "💾 Caching configuration..."
php artisan config:cache
php artisan view:cache
php artisan event:cache

echo "✅ Build completed successfully!"
