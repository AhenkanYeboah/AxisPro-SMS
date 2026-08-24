#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
