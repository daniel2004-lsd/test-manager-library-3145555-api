FROM php:8.3-cli-alpine

RUN apk add --no-cache $PHPIZE_DEPS git unzip libzip-dev oniguruma-dev postgresql-dev \
    && docker-php-ext-install pdo_pgsql zip pcntl bcmath opcache \
    && pecl install redis >/dev/null 2>&1 || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
