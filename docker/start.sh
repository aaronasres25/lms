#!/bin/sh
set -e

# Render tells us which port to listen on
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# a mounted persistent disk starts empty - make sure the folders exist
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
         storage/app/private/ebooks storage/app/public/covers bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan package:discover --ansi || true
php artisan storage:link 2>/dev/null || true

# optimise (non-fatal)
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

# database
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi
if [ "${RUN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
