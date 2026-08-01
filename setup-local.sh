#!/bin/bash
# CRM-Utapes Local Setup Script
# Jalankan dari folder project: bash setup-local.sh

set -e

echo "=== CRM-Utapes Local Setup ==="
echo ""

# 1. Cek PHP
echo "[1/7] Cek PHP..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP tidak ditemukan. Install: brew install php"
    exit 1
fi
PHP_VERSION=$(php -v | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "✅ PHP $PHP_VERSION"

# 2. Cek SQLite extension
echo "[2/7] Cek SQLite extension..."
if php -m | grep -q sqlite; then
    echo "✅ SQLite extension ada"
else
    echo "⚠️  SQLite extension tidak ada. Install: brew install php-sqlite"
    echo "   Atau: pecl install sqlite3"
    echo "   Setelah install, jalankan ulang script ini."
    exit 1
fi

# 3. Install dependencies
echo "[3/7] Install Composer dependencies..."
composer install --no-interaction --prefer-dist

# 4. Setup .env
echo "[4/7] Setup .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ .env dibuat dari .env.example"
else
    echo "✅ .env sudah ada"
fi

# 5. Generate app key
echo "[5/7] Generate app key..."
php artisan key:generate --force

# 6. Buat SQLite database
echo "[6/7] Setup SQLite database..."
DB_PATH=$(pwd)/database/database.sqlite
if [ ! -f "$DB_PATH" ]; then
    touch "$DB_PATH"
    echo "✅ database.sqlite dibuat"
else
    echo "✅ database.sqlite sudah ada"
fi

# Update .env dengan koneksi SQLite yang benar
# (ganti kalau ada, tambah kalau belum ada — mis. dari config pgsql)
ensure_env() {
    local key="$1" value="$2"
    if grep -q "^${key}=" .env; then
        sed -i '' "s|^${key}=.*|${key}=${value}|" .env 2>/dev/null || \
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

ensure_env "DB_CONNECTION" "sqlite"
ensure_env "DB_DATABASE" "$DB_PATH"

# 7. Jalankan migration + seed
echo "[7/7] Jalankan migration & seed..."
php artisan migrate:fresh --seed --force

echo ""
echo "=== Setup Selesai! ==="
echo ""
echo "Jalankan server:"
echo "  php artisan serve"
echo ""
echo "Buka: http://localhost:8000"
echo ""
echo "Login:"
echo "  Email: admin@crm.com"
echo "  Password: password"
echo ""
