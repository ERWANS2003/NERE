# Multi-stage build for Laravel with PHP-FPM + Nginx
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip postgresql-client libpq-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev \
    nginx supervisor \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create minimal Nginx configuration
RUN rm -rf /etc/nginx/sites-enabled/* && \
    echo 'server {\n\
    listen 80 default_server;\n\
    server_name _;\n\
    root /var/www/html/public;\n\
    index index.php;\n\
    \n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    \n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
    }\n\
    \n\
    location ~ /\. { deny all; }\n\
}' > /etc/nginx/sites-available/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configure PHP-FPM
RUN echo '[www]\n\
user = www-data\n\
group = www-data\n\
listen = 127.0.0.1:9000\n\
listen.owner = www-data\n\
listen.group = www-data\n\
pm = dynamic\n\
pm.max_children = 10\n\
pm.start_servers = 2\n\
pm.min_spare_servers = 1\n\
pm.max_spare_servers = 3\n\
catch_workers_output = yes' > /usr/local/etc/php-fpm.d/www.conf

# Configure Supervisor
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm --nodaemonize\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Create default .env if not exists
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
    composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Set permissions
RUN mkdir -p storage/logs storage/framework && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache

# Create entrypoint script
RUN cat > /entrypoint.sh << 'EOF'
#!/bin/bash
set -e

echo "🚀 Starting Laravel ITSM on Railway..."

# Railway provides DATABASE_URL in format: postgresql://user:pass@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
    echo "📦 Parsing Railway DATABASE_URL..."
    # Parse PostgreSQL connection string
    # Format: postgresql://user:password@host:port/database?sslmode=require
    
    DB_CONNECTION=pgsql
    DB_HOST=$(echo $DATABASE_URL | sed -E 's|.*@([^:]+).*|\1|')
    DB_PORT=$(echo $DATABASE_URL | sed -E 's|.*:([0-9]+)/.*|\1|')
    DB_DATABASE=$(echo $DATABASE_URL | sed -E 's|.*/([^?]+).*|\1|')
    DB_USERNAME=$(echo $DATABASE_URL | sed -E 's|.*://([^:]+).*|\1|')
    DB_PASSWORD=$(echo $DATABASE_URL | sed -E 's|.*://[^:]+:([^@]+)@.*|\1|')
    
    echo "  ✅ DB_HOST=$DB_HOST"
    echo "  ✅ DB_PORT=$DB_PORT"
    echo "  ✅ DB_DATABASE=$DB_DATABASE"
    echo "  ✅ DB_USERNAME=$DB_USERNAME"
    
    # Update .env with Railway config
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=$DB_CONNECTION|" /var/www/html/.env
    sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" /var/www/html/.env
    sed -i "s|^DB_PORT=.*|DB_PORT=$DB_PORT|" /var/www/html/.env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" /var/www/html/.env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" /var/www/html/.env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" /var/www/html/.env
fi

echo ""
echo "🔧 Current Database Config:"
echo "  DB_CONNECTION: $(grep '^DB_CONNECTION' /var/www/html/.env || echo 'NOT SET')"
echo "  DB_HOST: $(grep '^DB_HOST' /var/www/html/.env || echo 'NOT SET')"
echo "  DB_DATABASE: $(grep '^DB_DATABASE' /var/www/html/.env || echo 'NOT SET')"

# Clear Laravel cache (old config might be cached)
rm -f /var/www/html/bootstrap/cache/config.php

# Wait for database
echo ""
echo "⏳ Waiting for database connection..."
for i in {1..30}; do
    if timeout 5 php artisan tinker --execute="DB::connection()->getPDO(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
        echo "✅ Database connected!"
        break
    fi
    echo "  Attempt $i/30..."
    sleep 2
done

# Run migrations
echo ""
echo "📊 Running migrations..."
php artisan migrate --force 2>&1 | grep -E "DONE|ERROR|FAILED" | head -30 || true

# Cache configuration
echo ""
echo "⚡ Optimizing Laravel..."
php artisan config:cache 2>/dev/null || echo "⚠️ Config cache failed"
php artisan route:cache 2>/dev/null || echo "⚠️ Route cache failed"

echo ""
echo "🌐 Starting PHP-FPM and Nginx..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
