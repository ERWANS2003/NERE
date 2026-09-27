web: PHP_CLI_SERVER_WORKERS=8 php -S 0.0.0.0:$PORT -t public server.php
release: chmod -R 775 storage bootstrap/cache && php artisan migrate --force --quiet && php artisan config:cache && php artisan view:cache && php artisan event:cache && php artisan storage:link && php artisan cache:clear
