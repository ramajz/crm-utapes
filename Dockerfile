FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    curl git nginx supervisor nodejs npm zip unzip postgresql-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) pdo_pgsql mbstring bcmath zip intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --no-interaction --optimize-autoloader && \
    npm ci && npm run build -- --no-interaction && \
    php artisan storage:link && \
    chmod -R 777 storage bootstrap/cache

RUN echo 'server { listen 80; root /var/www/html/public; index index.php; charset utf-8; location / { try_files $uri $uri/ /index.php?$query_string; } location ~ \.php$ { fastcgi_pass 127.0.0.1:9000; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params; } }' > /etc/nginx/http.d/default.conf

RUN echo '[supervisord] \nnodaemon=true \n[program:php-fpm] \ncommand=php-fpm \n[program:nginx] \ncommand=nginx -g "daemon off;"' > /etc/supervisord.conf

EXPOSE 80
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
