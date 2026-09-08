# Use official PHP image with Apache
FROM php:8.3-apache

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
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Completely fix Apache MPM configuration
RUN echo "Configuring Apache MPM..." && \
    a2dismod mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork rewrite ssl headers && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create custom Apache configuration
RUN echo 'LoadModule rewrite_module modules/mod_rewrite.so\n\
<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        \n\
        <IfModule mod_rewrite.c>\n\
            RewriteEngine On\n\
            RewriteCond %{REQUEST_FILENAME} !-f\n\
            RewriteCond %{REQUEST_FILENAME} !-d\n\
            RewriteRule ^(.*)$ index.php [QSA,L]\n\
        </IfModule>\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/laravel.conf

# Enable our Laravel site and disable default
RUN a2dissite 000-default && \
    a2ensite laravel

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies with error handling
RUN composer install --no-dev --optimize-autoloader --no-interaction || \
    (echo "Composer install failed, trying without platform requirements..." && \
     composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs)

# Build frontend assets if package.json exists
RUN if [ -f "package.json" ]; then \
        curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
        apt-get install -y nodejs && \
        npm install && \
        (npm run build || npm run production || echo "Frontend build skipped"); \
    fi

# Create storage directories and set permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Create optimized entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "🚀 Starting Laravel ITSM application..."\n\
\n\
# Wait for database to be ready\n\
echo "⏳ Checking database connection..."\n\
timeout=30\n\
while [ $timeout -gt 0 ]; do\n\
    if php artisan migrate:status >/dev/null 2>&1; then\n\
        echo "✅ Database is ready"\n\
        break\n\
    fi\n\
    echo "⏳ Waiting for database... ($timeout seconds left)"\n\
    sleep 2\n\
    timeout=$((timeout-2))\n\
done\n\
\n\
# Run migrations\n\
echo "📊 Running database migrations..."\n\
php artisan migrate --force || echo "⚠️ Migrations failed, continuing..."\n\
\n\
# Cache configurations\n\
echo "⚡ Optimizing Laravel..."\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
\n\
# Start Apache with proper signal handling\n\
echo "🌐 Starting Apache web server..."\n\
exec apache2-foreground' > /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Expose port 80
EXPOSE 80

# Environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_PID_FILE=/var/run/apache2.pid \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_LOCK_DIR=/var/lock/apache2

ENTRYPOINT ["/entrypoint.sh"]

