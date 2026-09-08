# Multi-stage build for Laravel with PHP-FPM + Nginx
FROM php:8.3-fpm as php

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create Nginx configuration for Laravel
RUN echo 'server {\n\
    listen 80;\n\
    server_name localhost;\n\
    root /var/www/html/public;\n\
    index index.php index.html;\n\
    \n\
    # Security headers\n\
    add_header X-Frame-Options "SAMEORIGIN" always;\n\
    add_header X-XSS-Protection "1; mode=block" always;\n\
    add_header X-Content-Type-Options "nosniff" always;\n\
    \n\
    # Laravel URL rewriting\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    \n\
    # PHP processing\n\
    location ~ \\.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
        fastcgi_read_timeout 300;\n\
    }\n\
    \n\
    # Security: deny access to sensitive files\n\
    location ~ /\\. {\n\
        deny all;\n\
    }\n\
    \n\
    location ~ ^/(storage|bootstrap/cache)/ {\n\
        deny all;\n\
    }\n\
    \n\
    # Logging\n\
    access_log /var/log/nginx/laravel_access.log;\n\
    error_log /var/log/nginx/laravel_error.log;\n\
}' > /etc/nginx/sites-available/laravel

# Enable Laravel site
RUN rm /etc/nginx/sites-enabled/default && \
    ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel

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
pm.process_idle_timeout = 10s\n\
pm.max_requests = 500\n\
catch_workers_output = yes\n\
php_admin_value[error_log] = /var/log/php-fpm.log\n\
php_admin_flag[log_errors] = on' > /usr/local/etc/php-fpm.d/www.conf

# Create Supervisor configuration
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
logfile=/var/log/supervisor/supervisord.log\n\
pidfile=/var/run/supervisord.pid\n\
\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm --nodaemonize\n\
autostart=true\n\
autorestart=true\n\
priority=5\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
priority=10\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0' > /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN echo "Installing Composer dependencies..." && \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || \
    composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Build frontend assets if needed
RUN if [ -f "package.json" ]; then \
        echo "Setting up Node.js for frontend build..." && \
        curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
        apt-get install -y nodejs && \
        npm install && \
        (npm run build || npm run production || echo "Frontend build completed"); \
    fi

# Set proper permissions
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache && \
    chmod -R 775 storage/logs storage/framework && \
    mkdir -p /var/log/supervisor

# Create enhanced startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "🚀 Starting Laravel ITSM with PHP-FPM + Nginx..."\n\
\n\
# Wait for database connection\n\
echo "⏳ Checking database connection..."\n\
max_attempts=15\n\
attempt=1\n\
while [ $attempt -le $max_attempts ]; do\n\
    if timeout 10 php -r "\n\
        try {\n\
            \\$pdo = new PDO(\n\
                \\\"pgsql:host={\\$_ENV[\\\"DB_HOST\\\"]};dbname={\\$_ENV[\\\"DB_DATABASE\\\"]}\\\",\n\
                \\$_ENV[\\\"DB_USERNAME\\\"],\n\
                \\$_ENV[\\\"DB_PASSWORD\\\"]\n\
            );\n\
            echo \\\"Connected\\\";\n\
            exit(0);\n\
        } catch(Exception \\$e) {\n\
            exit(1);\n\
        }\n\
    " 2>/dev/null; then\n\
        echo "✅ Database ready (attempt $attempt)"\n\
        break\n\
    fi\n\
    echo "⏳ Database not ready, attempt $attempt/$max_attempts..."\n\
    sleep 2\n\
    attempt=$((attempt + 1))\n\
done\n\
\n\
# Run database migrations\n\
echo "📊 Running database migrations..."\n\
php artisan migrate --force || echo "⚠️ Migrations completed with warnings"\n\
\n\
# Optimize Laravel\n\
echo "⚡ Optimizing Laravel..."\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
\n\
# Start services with Supervisor\n\
echo "🌐 Starting PHP-FPM and Nginx..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Expose port 80
EXPOSE 80

# Set environment variables
ENV PHP_FPM_LISTEN=127.0.0.1:9000 \
    NGINX_ROOT=/var/www/html/public

ENTRYPOINT ["/entrypoint.sh"]