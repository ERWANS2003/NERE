web: vendor/bin/heroku-php-apache2 public/
release: php artisan migrate --force --quiet && php artisan config:cache && php artisan view:cache && php artisan event:cache
