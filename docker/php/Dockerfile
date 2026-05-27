# --- Сборка SPA-бандла Owner-панели ---
# OwnerSpaController отдаёт base_path('../dist/owner/index.html'), поэтому
# бандл должен лежать рядом с backend внутри php-образа.
FROM node:22-alpine AS owner-frontend

WORKDIR /build

COPY frontend/package.json frontend/package-lock.json ./
COPY frontend/scan/package.json ./scan/
COPY frontend/owner/package.json ./owner/
COPY frontend/shared/package.json ./shared/

RUN npm ci --ignore-scripts

COPY frontend/ ./

RUN npm run build:owner

FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    linux-headers \
    postgresql-client \
    postgresql-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl opcache pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-guardreviews.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM php-base AS vendor

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --no-autoloader

COPY backend/ ./

RUN composer dump-autoload --optimize --classmap-authoritative

FROM php-base AS production

COPY docker/php/php-production.ini /usr/local/etc/php/conf.d/98-guardreviews-production.ini

COPY --from=vendor /var/www/html /var/www/html

# Owner SPA bundle рядом с backend (см. OwnerSpaController::DIST_PATH).
COPY --from=owner-frontend /dist/owner /var/www/dist/owner

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html /var/www/dist

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM php-base AS development

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/98-guardreviews-dev.ini

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER root

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
