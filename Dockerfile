FROM php:8.2-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    CI_ENVIRONMENT=production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        libxml2-dev \
    && docker-php-ext-install intl mbstring mysqli zip dom \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . .

RUN mkdir -p writable/cache writable/debugbar writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public system/rewrite.php"]