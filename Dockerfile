# syntax=docker/dockerfile:1.7

FROM php:8.4-cli-alpine AS base

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-libs \
    libpq \
    libzip \
    oniguruma \
    postgresql-client \
    sqlite-libs \
    unzip \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install -j"$(nproc)" \
    bcmath \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo_pgsql \
    pdo_sqlite \
    zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS vendor

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

FROM base AS frontend

COPY --from=node:22-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY package.json package-lock.json ./

RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor

RUN npm run build

FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=pgsql \
    DB_PORT=5432

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
