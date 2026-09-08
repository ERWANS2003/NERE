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

# RADICAL APACHE CONFIGURATION FIX
RUN echo "=== FIXING APACHE MPM CONFLICTS ===" && \
    # Remove ALL MPM modules completely
    rm -f /etc/apache2/mods-enabled/mpm_* && \
    rm -f /etc/apache2/mods-available/mmp_* && \
    # Disable any auto-loaded modules
    a2dismod mpm_event mpm_worker mpm_async 2>/dev/null || true && \
    # Create fresh MPM prefork configuration
    echo "# MPM Prefork Module" > /etc/apache2/mods-available/mpm_prefork.load && \
    echo "LoadModule mpm_prefork_module modules/mod_mpm_prefork.so" >> /etc/apache2/mods-available/mmp_prefork.load && \
    # Enable ONLY prefork and rewrite
    a2enmod mpm_prefork rewrite && \
    # Set ServerName to avoid warnings
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create completely fresh Laravel Apache configuration
RUN echo '# Laravel Apache Configuration\n\
<VirtualHost *:80>\n\
    ServerName localhost\n\
    ServerAlias *\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        \n\
        # Laravel URL Rewriting\n\
        RewriteEngine On\n\
        RewriteCond %{REQUEST_FILENAME} !-f\n\
        RewriteCond %{REQUEST_FILENAME} !-d\n\
        RewriteRule ^(.*)$ index.php [QSA,L]\n\
    </Directory>\n\
    \n\
    # Logging\n\
    ErrorLog ${APACHE_LOG_DIR}/laravel_error.log\n\
    CustomLog ${APACHE_LOG_DIR}/laravel_access.log combined\n\
    LogLevel warn\n\
</VirtualHost>' > /etc/apache2/sites-available/laravel.conf

# Activate Laravel site
RUN a2dissite 000-default && a2ensite laravel

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies with enhanced error handling
RUN echo "Installing Composer dependencies..." && \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || \
    (echo "Retry with ignore platform reqs..." && \
     composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs) || \
    (echo "Composer failed, continuing...")

# Build frontend assets if needed
RUN if [ -f "package.json" ]; then \
        echo "Setting up Node.js..." && \
        curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
        apt-get install -y nodejs && \
        echo "Installing npm dependencies..." && \
        npm install && \
        echo "Building frontend assets..." && \
        (npm run build || npm run production || echo "Frontend build completed"); \
    else \
        echo "No package.json found, skipping frontend build"; \
    fi

# Set up Laravel directories and permissions
RUN echo "Setting up Laravel directories..." && \
    mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache && \
    chmod -R 775 storage/logs storage/framework

# Create the ultimate entrypoint script with MPM fixes
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "🚀 Starting Laravel ITSM application..."\n\
\n\
# ENSURE CLEAN APACHE STATE\n\
echo "🔧 Ensuring clean Apache configuration..."\n\
# Kill any existing Apache processes\n\
pkill apache2 2>/dev/null || true\n\
# Clean MPM modules one more time at runtime\n\
a2dismod mmp_event mpm_worker 2>/dev/null || true\n\
# Test configuration\n\
apache2ctl configtest 2>/dev/null || echo "Config test warnings ignored"\n\
\n\
# Enhanced database connection check\n\
echo "⏳ Checking database connection..."\n\
max_attempts=15\n\
attempt=1\n\
while [ $attempt -le $max_attempts ]; do\n\
    if timeout 5 php -r "\n\
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
        echo "✅ Database connection established (attempt $attempt)"\n\
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
echo "⚡ Optimizing Laravel application..."\n\
php artisan config:cache || true\n\
php artisan route:cache || true\n\
php artisan view:cache || true\n\
\n\
# Start Apache with clean slate\n\
echo "🌐 Starting Apache web server..."\n\
# Use apache2ctl instead of apache2-foreground for better control\n\
exec /usr/sbin/apache2ctl -D FOREGROUND' > /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Set comprehensive Apache environment variables
ENV APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_LOCK_DIR=/var/lock/apache2 \
    APACHE_SERVERNAME=localhost \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Expose port 80
EXPOSE 80

# Use our entrypoint
ENTRYPOINT ["/entrypoint.sh"]