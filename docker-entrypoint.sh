#!/bin/sh
set -eu

cd /var/www/html

rm -f public/hot

export DB_DATABASE="${DB_DATABASE:-/var/www/html/data/database.sqlite}"

mkdir -p \
    data \
    database \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /run/nginx

mkdir -p "$(dirname "$DB_DATABASE")"
touch "$DB_DATABASE"

if [ ! -s data/.app-key ]; then
    php artisan key:generate --show --no-interaction > data/.app-key
fi

export APP_KEY="$(cat data/.app-key)"

write_runtime_env() {
    {
        printf 'APP_NAME=%s\n' "${APP_NAME:-Wallos}"
        printf 'APP_ENV=%s\n' "${APP_ENV:-production}"
        printf 'APP_KEY=%s\n' "$APP_KEY"
        printf 'APP_DEBUG=%s\n' "${APP_DEBUG:-false}"
        printf 'APP_URL=%s\n' "${APP_URL:-http://localhost:8001}"
        printf 'ASSET_URL=%s\n' "${ASSET_URL:-${APP_URL:-http://localhost:8001}}"
        printf 'TRUSTED_PROXIES=%s\n' "${TRUSTED_PROXIES:-*}"
        printf 'APP_LOCALE=%s\n' "${APP_LOCALE:-zh_CN}"
        printf 'APP_FALLBACK_LOCALE=%s\n' "${APP_FALLBACK_LOCALE:-en}"
        printf 'APP_FAKER_LOCALE=%s\n' "${APP_FAKER_LOCALE:-en_US}"
        printf 'LOG_CHANNEL=%s\n' "${LOG_CHANNEL:-stack}"
        printf 'LOG_STACK=%s\n' "${LOG_STACK:-single}"
        printf 'LOG_LEVEL=%s\n' "${LOG_LEVEL:-debug}"
        printf 'DB_CONNECTION=%s\n' "${DB_CONNECTION:-sqlite}"
        printf 'DB_DATABASE=%s\n' "${DB_DATABASE:-/var/www/html/data/database.sqlite}"
        printf 'SESSION_DRIVER=%s\n' "${SESSION_DRIVER:-database}"
        printf 'SESSION_LIFETIME=%s\n' "${SESSION_LIFETIME:-120}"
        printf 'SESSION_ENCRYPT=%s\n' "${SESSION_ENCRYPT:-false}"
        printf 'SESSION_PATH=%s\n' "${SESSION_PATH:-/}"
        printf 'SESSION_DOMAIN=%s\n' "${SESSION_DOMAIN:-null}"
        printf 'CACHE_STORE=%s\n' "${CACHE_STORE:-database}"
        printf 'QUEUE_CONNECTION=%s\n' "${QUEUE_CONNECTION:-database}"
        printf 'BROADCAST_CONNECTION=%s\n' "${BROADCAST_CONNECTION:-log}"
        printf 'FILESYSTEM_DISK=%s\n' "${FILESYSTEM_DISK:-local}"
        printf 'MAIL_MAILER=%s\n' "${MAIL_MAILER:-log}"
        printf 'MAIL_SCHEME=%s\n' "${MAIL_SCHEME:-null}"
        printf 'MAIL_HOST=%s\n' "${MAIL_HOST:-127.0.0.1}"
        printf 'MAIL_PORT=%s\n' "${MAIL_PORT:-2525}"
        printf 'MAIL_USERNAME=%s\n' "${MAIL_USERNAME:-null}"
        printf 'MAIL_PASSWORD=%s\n' "${MAIL_PASSWORD:-null}"
        printf 'MAIL_FROM_ADDRESS=%s\n' "${MAIL_FROM_ADDRESS:-hello@wallos.local}"
        printf 'MAIL_FROM_NAME=%s\n' "${MAIL_FROM_NAME:-Wallos}"
        printf 'VITE_APP_NAME=%s\n' "${VITE_APP_NAME:-Wallos}"
    } > .env
}

write_runtime_env

php artisan storage:link --force
php artisan migrate --force
php artisan db:seed --force

chown -R www-data:www-data data storage bootstrap/cache

if [ "${1:-app}" != "app" ]; then
    exec "$@"
fi

php-fpm -D

exec nginx -g 'daemon off;'
