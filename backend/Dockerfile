FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
    && docker-php-ext-install pdo_pgsql pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && printf "APP_KEY=base64:i/nYjegOeMVp0SofWutgluJGwQlEIpaGMeh3oGfPSTM=\n" > .env \
    && composer dump-autoload --optimize \
    && chmod +x docker-entrypoint.sh \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000 8080

ENTRYPOINT ["./docker-entrypoint.sh"]
