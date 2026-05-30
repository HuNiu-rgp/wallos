#!/bin/sh
set -eu

cd /var/www/html

rm -f public/hot

mkdir -p \
    database \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ ! -s database/.app-key ]; then
    php artisan key:generate --show --no-interaction > database/.app-key
fi

export APP_KEY="$(cat database/.app-key)"

first_run=0

if [ ! -s database/database.sqlite ]; then
    first_run=1
fi

touch database/database.sqlite
php artisan storage:link --force
php artisan migrate --force

if [ "$first_run" = "1" ]; then
    php artisan db:seed --force
fi

if [ "${1:-app}" != "app" ]; then
    exec "$@"
fi

exec php artisan serve --host=0.0.0.0 --port=8000
