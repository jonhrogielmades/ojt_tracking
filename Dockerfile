FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache \
        icu-libs \
        libcurl \
        libpq \
        libxml2 \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        icu-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        dom \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        simplexml \
        xml \
        zip \
    && apk del .build-deps \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

COPY app ./app
COPY database ./database
RUN composer dump-autoload \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --classmap-authoritative

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor

RUN npm ci --no-audit --no-fund \
    && npm run build

FROM php-base AS runtime

RUN apk add --no-cache \
        bash \
        nginx \
        supervisor

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY docker/php/render.ini /usr/local/etc/php/conf.d/zz-render.ini
COPY docker/php/www-overrides.conf /usr/local/etc/php-fpm.d/zz-render.conf
COPY docker/nginx/default.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/render-start

RUN chmod +x /usr/local/bin/render-start \
    && mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1 \
    LOG_CHANNEL=stderr \
    PORT=10000

EXPOSE 10000

CMD ["/usr/local/bin/render-start"]
