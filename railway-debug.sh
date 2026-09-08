#!/bin/bash
# Railway Deployment Debug Script
# Run this to diagnose Railway deployment issues

echo "🔍 RAILWAY DEPLOYMENT DIAGNOSTICS"
echo "=================================="

echo "📊 Environment Variables:"
echo "APP_ENV: $APP_ENV"
echo "DB_HOST: $DB_HOST"
echo "DB_DATABASE: $DB_DATABASE"
echo "APP_KEY set: $(if [ -n "$APP_KEY" ]; then echo "✅ Yes"; else echo "❌ No"; fi)"

echo ""
echo "🔧 Apache Configuration:"
apache2ctl -t 2>&1 || echo "❌ Apache config test failed"

echo ""
echo "🗄️ Database Connection:"
timeout 10 php -r "
try {
    \$pdo = new PDO(
        \"pgsql:host={\$_ENV['DB_HOST']};dbname={\$_ENV['DB_DATABASE']}\",
        \$_ENV['DB_USERNAME'],
        \$_ENV['DB_PASSWORD']
    );
    echo '✅ Database connection successful';
} catch(Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage();
}" 2>&1

echo ""
echo "📁 File Permissions:"
ls -la storage/ | head -5
ls -la bootstrap/cache/ | head -3

echo ""
echo "🚀 Laravel Status:"
php artisan --version 2>&1
php artisan migrate:status 2>&1 | head -10

echo ""
echo "🌐 Apache Modules:"
apache2ctl -M | grep -E "(mpm_|rewrite)" 2>&1

echo ""
echo "💡 Railway Tips:"
echo "1. Ensure PostgreSQL service is added to your Railway project"
echo "2. Set environment variables in Railway dashboard"
echo "3. Check Railway build and deployment logs"
echo "4. Verify APP_KEY is generated: php artisan key:generate --show"