# syntax=docker/dockerfile:1

# ---- Frontend assets ----
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY public public
COPY vite.config.js ./
RUN npm run build

# ---- PHP dependencies ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# ---- Runtime image ----
FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring bcmath opcache

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
