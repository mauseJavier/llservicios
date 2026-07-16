#!/bin/sh
set -e

mkdir -p \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/storage/app/public \
    /var/www/html/bootstrap/cache

if [ "${MIGRATE}" = "true" ]; then
    php /var/www/html/artisan migrate --force
fi

if [ "$1" = "apache2-foreground" ]; then
    if [ "${APP_ENV}" = "production" ]; then
        php /var/www/html/artisan config:cache
        php /var/www/html/artisan view:clear
    else
        php /var/www/html/artisan config:clear
        php /var/www/html/artisan view:clear
    fi
fi

if [ ! -L /var/www/html/public/storage ]; then
    php /var/www/html/artisan storage:link --force
fi

chgrp -R www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod -R g+rwxs \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache
chmod +t /var/www/html/storage /var/www/html/storage/app /var/www/html/storage/app/public 2>/dev/null || true

exec "$@"
