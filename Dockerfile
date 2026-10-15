FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    curl git zip unzip nodejs npm postgresql-dev oniguruma-dev libzip-dev linux-headers \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring bcmath zip intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm install --ignore-scripts

COPY . .
# Fix permission apapun hasil COPY (tar lokal bisa membawa mode 600 → www-data
# tidak bisa baca → "Permission denied" saat render view). Lesson #233.
RUN find app resources routes config database -type f -name "*.php" -exec chmod 644 {} + && \
    chmod 644 artisan composer.json 2>/dev/null || true

# Build assets TANPA hooks npm (lesson: --ignore-scripts)
RUN npm run build \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/{app/public,framework/{cache/data,sessions,testing,views},logs} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan storage:link 2>/dev/null || true

# Migrate + cache dijalankan saat STARTUP (env baru sudah terbaca), bukan saat build
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
