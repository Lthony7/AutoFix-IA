# AUTOFIX IA — build multi-etapa para Render
# 1) Frontend (Node/npm)  2) Runtime (PHP 8.4 + Composer)

# ---------- Frontend ----------
FROM node:22-bookworm AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
# Forzar rebuild de assets cuando cambie el código fuente (evitar cache stale en Render)
ARG FRONTEND_CACHEBUST=1
RUN echo "frontend_cachebust=${FRONTEND_CACHEBUST}" && npm run build

# ---------- PHP runtime ----------
FROM php:8.4-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates \
        libpq-dev libzip-dev libpng-dev libonig-dev libxml2-dev \
        libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql pgsql zip bcmath intl pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && sed -i 's/\r$//' docker/start.sh \
    && chmod +x docker/start.sh

EXPOSE 10000

CMD ["docker/start.sh"]
