FROM php:8.4-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    curl \
    git \
    nodejs \
    npm \
    nginx \
    supervisor \
    zip \
    unzip

# PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring bcmath zip intl opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --no-interaction --optimize-autoloader && \
    npm ci && \
    npm run build && \
    php artisan storage:link && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config
RUN echo 'server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php; \
    charset utf-8; \
    location / { try_files $uri $uri/ /index.php?$query_string; } \
    location ~ \.php$ { fastcgi_pass 127.0.0.1:9000; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params; } \
    location ~ /\.ht { deny all; } \
}' > /etc/nginx/http.d/default.conf

# Supervisor config
RUN echo '[supervisord] \
nodaemon=true \
[program:php] \
command=php-fpm \
[program:nginx] \
command=nginx -g "daemon off;"' > /etc/supervisor.conf

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor.conf"]
