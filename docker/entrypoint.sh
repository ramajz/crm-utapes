#!/bin/sh
set -e

echo "[crm] env: APP_ENV=${APP_ENV} DB_HOST=${DB_HOST}"

# /bin/sh (ash) TIDAK mendukung brace expansion {} — buat dir per perintah
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
# php-fpm jalan sebagai www-data — dir runtime harus dimiliki dia
chown -R www-data:www-data storage bootstrap/cache

# PENTING: JANGAN config:cache/route:cache — terbukti bikin
# 'Please provide a valid cache path' (500) di Laravel + Livewire ini.
# Hapus cache lama kalau ada.
rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php

# Migrate saat runtime — env (APP_KEY, DB) sudah lengkap
echo "[crm] migrate..."
php artisan migrate --force

echo "[crm] starting php-fpm..."
exec php-fpm -F
