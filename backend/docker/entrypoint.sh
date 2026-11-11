#!/bin/sh
set -e

PORT_TO_USE=${PORT:-80}
if [ -f /etc/nginx/http.d/default.conf ]; then
    sed -i "s/listen 80;/listen ${PORT_TO_USE};/g" /etc/nginx/http.d/default.conf
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force || true
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm -D

exec nginx -g "daemon off;"

