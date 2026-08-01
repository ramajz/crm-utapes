# CRM-Utapes: Local Development Guide (SQLite)

> Panduan lengkap untuk develop CRM-Utapes di MacBook pakai SQLite.
> Production tetap pakai NeonDB PostgreSQL.

## Kenapa SQLite?

| | NeonDB (Production) | SQLite (Lokal) |
|--|--|--|
| Speed | Tergantung internet (~50-200ms/query) | Langsung (~1-5ms/query) |
| Setup | Perlu koneksi internet | File .sqlite aja |
| Data | Data production nyata | Data dummy (seeder) |
| Cost | Free tier (terbatas) | Gratis total |

## Prasyarat

- PHP 8.2+ (cek: `php -v`)
- Composer (cek: `composer -V`)
- SQLite3 extension (cek: `php -m | grep sqlite`)

### Cek SQLite di PHP:
```bash
php -m | grep sqlite
```
Harus muncul: `sqlite3` dan/atau `pdo_sqlite`

Kalau gak ada (macOS dengan Homebrew):
```bash
brew install php-sqlite
```

## Step-by-Step Setup

### 1. Clone Repository
```bash
git clone https://github.com/ramajz/crm-utapes.git
cd crm-utapes
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
```bash
cp .env.example .env
```

Edit `.env` — ganti bagian database:
```env
# ===== GANTI INI =====
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
# =====================

# SQLite file path (relative dari root project)
DB_DATABASE=/Users/YOUR_USERNAME/crm-utapes/database/database.sqlite
```

**Ganti `YOUR_USERNAME` dengan username macOS kamu.**

Atau pakai path relative:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/Users/rama/crm-utapes/database/database.sqlite
```

### 4. Bikin File SQLite
```bash
touch database/database.sqlite
```

### 5. Generate App Key
```bash
php artisan key:generate
```

### 6. Jalankan Migration
```bash
php artisan migrate
```

### 7. Isi Data Dummy
```bash
php artisan db:seed
```

### 8. Cache Config (Optional, untuk speed)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Jalankan Server
```bash
php artisan serve
```

Buka: `http://localhost:8000`

## Login Default

| Role | Email | Password |
|--|--|--|
| Admin | admin@crm.com | password |
| Manager | manager@crm.com | password |
| CS | siti@crm.com | password |
| CS | rina@crm.com | password |
| CS | budi@crm.com | password |
| CS | dewi@crm.com | password |
| CS | ahmad@crm.com | password |

## Troubleshooting

### "Could not find driver" (SQLite)
```bash
# macOS
brew install php-sqlite
# atau
pecl install sqlite3

# Restart terminal, cek lagi
php -m | grep sqlite
```

### "Database file not found"
Pastikan path di `.env` benar dan file `.sqlite` ada:
```bash
ls -la database/database.sqlite
```

### "Permission denied"
```bash
chmod 644 database/database.sqlite
```

### Migration error "no such table"
Database kosong, jalankan ulang:
```bash
php artisan migrate:fresh --seed
```

## Perbedaan SQLite vs PostgreSQL

| Feature | SQLite | PostgreSQL | Impact |
|--|--|--|--|
| Boolean | 0/1 | TRUE/FALSE | Laravel handle otomatis |
| Auto-increment | ROWID | SERIAL | Laravel `$table->id()` handle |
| JSON | JSON1 ext | Native JSONB | Gak dipake di CRM ini |
| Timestamps | String | Native | Laravel handle otomatis |

**CRM-Utapes aman** — semua query pakai Eloquent/Query Builder, gak ada raw SQL yang incompatible.

## File Structure

```
crm-utapes/
├── database/
│   ├── database.sqlite     ← FILE INI (SQLite database)
│   ├── migrations/         ← Migration files (sama untuk SQLite & PostgreSQL)
│   └── seeders/
│       └── DatabaseSeeder.php  ← Data dummy
├── .env                    ← DB_CONNECTION=sqlite
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Services/
└── ...
```

## Switching Between Local & Production

### Lokal (SQLite):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/Users/rama/crm-utapes/database/database.sqlite
```

### Production (NeonDB PostgreSQL):
```env
DB_CONNECTION=pgsql
DB_HOST=ep-xxx.us-east-2.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=...
DB_PASSWORD=...
DB_SSLMODE=require
```

**Gak perlu ubah code.** Cuma ganti `.env`.

## Development Workflow

```
1. Edit code di MacBook
2. Test di localhost:8000 (SQLite)
3. Push ke git
4. Coolify auto-deploy ke VPS (NeonDB)
```

## Notes

- Data SQLite **gak akan sync** ke production (sengaja — dev data ≠ prod data)
- Kalau butuh data production di lokal, export dari NeonDB → import ke SQLite
- SQLite file cuma 1 file, bisa di-backup dengan copy aja
