web: vendor/bin/heroku-php-apache2 public/
release: chmod -R 775 storage bootstrap/cache && php artisan migrate --force --quiet && php artisan config:cache && php artisan view:cache && php artisan event:cache && php artisan storage:link && php artisan cache:clear
