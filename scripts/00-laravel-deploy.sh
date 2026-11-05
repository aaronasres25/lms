#!/usr/bin/env bash
echo "Building Laravel LMS Application..."
composer install --no-dev --working-dir=/var/www/html
echo "Caching configurations and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "Executing Database Migrations..."
php artisan migrate --force
