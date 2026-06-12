FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions pdo_mysql gd

WORKDIR /app

COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
