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

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
    composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Set permissions
RUN mkdir -p storage/logs storage/framework && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache

# Create entrypoint
RUN echo '#!/bin/bash\n\
set -e\n\
echo "🚀 Starting Laravel..."\n\
for i in {1..30}; do\n\
    if php -r "new PDO(\"pgsql:host={$DB_HOST};dbname={$DB_DATABASE}\", \"$DB_USERNAME\", \"$DB_PASSWORD\");" 2>/dev/null; then\n\
        break\n\
    fi\n\
    sleep 1\n\
done\n\
php artisan migrate --force 2>&1 | head -20 || true\n\
php artisan config:cache 2>/dev/null || true\n\
php artisan route:cache 2>/dev/null || true\n\
echo "🌐 Starting services..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /entrypoint.sh && \
    chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
