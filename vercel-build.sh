#!/bin/bash

echo "🚀 Starting Vercel build for Laravel..."

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

# Create required directories
echo "📁 Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Build frontend assets if available
if [ -f "package.json" ]; then
    echo "🎨 Building frontend assets..."
    npm ci --production
    npm run build 2>/dev/null || npm run production 2>/dev/null || echo "⚠️ Frontend build skipped"
fi

echo "✅ Vercel build completed!"