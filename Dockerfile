FROM php:8.4-apache-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates chromium git unzip libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pdo_mysql pdo_pgsql zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS php-build
COPY . .
RUN COMPOSER_ROOT_VERSION=dev-main composer install --no-dev --no-scripts --no-plugins --prefer-dist --optimize-autoloader --no-interaction \
    && mkdir -p bootstrap/cache storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs \
    && php artisan package:discover --ansi

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=php-build /var/www/html/vendor ./vendor
RUN npm run build

FROM php-base AS production
COPY --from=php-build /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/hris.ini
COPY deploy/start.sh /usr/local/bin/start-hris
RUN chmod +x /usr/local/bin/start-hris \
    && mkdir -p storage/app/private/documents storage/app/private/signatures storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PDF_BROWSER_PATH=/usr/bin/chromium
EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/start-hris"]
