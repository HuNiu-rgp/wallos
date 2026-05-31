FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        nginx \
        libsqlite3-dev \
        libzip-dev \
        sqlite3 \
        unzip \
    && docker-php-ext-install pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:20-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:20-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

WORKDIR /var/www/html

COPY . .
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/wallos-entrypoint
COPY docker/nginx.conf /etc/nginx/sites-available/default

RUN mkdir -p data database \
    && touch database/database.sqlite \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci \
    && npm run build \
    && rm -f public/hot \
    && rm -rf node_modules \
    && rm -f database/*.sqlite* database/.app-key data/*.sqlite* data/.app-key \
    && printf '%s\n' 'clear_env = no' > /usr/local/etc/php-fpm.d/zz-wallos.conf \
    && mkdir -p \
        data \
        database \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /run/nginx

EXPOSE 80

ENTRYPOINT ["wallos-entrypoint"]
CMD ["app"]
