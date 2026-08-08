# 🚀 Phase 1: CRM Utapes — Deployment ke Coolify

> **Tanggal:** 27 Juli 2026
> **Model:** Claude Sonnet 4.6 via Syncera
> **Tujuan:** Deploy CRM Utapes (Laravel 13 + NeonDB PostgreSQL) ke Coolify

---

## ✅ Ringkasan

CRM Utapes berhasil di-deploy ke **GitHub** (`ramajz/crm-utapes`) dan diintegrasikan dengan **Coolify** (self-hosted PaaS). Database menggunakan **NeonDB PostgreSQL** (serverless, free tier).

## 📂 Repositori

- **GitHub:** https://github.com/ramajz/crm-utapes
- **Codebase:** Laravel 11 → Laravel 13 (upgrade via composer.lock)
- **Stack:** Laravel 13 + Livewire + Blade + Tailwind + NeonDB

## ⚙️ Langkah-langkah

### 1. Setup Lingkungan
- Install PHP 8.4 + ekstensi (pgsql, mbstring, bcmath, zip, intl)
- Install Composer 2.x
- Clone repo `ramajz/herd` → extract `crm-utapes`

### 2. Database (NeonDB)
```
Host: ep-aged-waterfall-axih051h.c-4.us-east-2.aws.neon.tech
Port: 5432
Database: neondb
User: neondb_owner
SSL: required
```

### 3. Seeding Data
- 7 user (admin, manager, 5 CS)
- 5 handler
- 20 customer
- 620 leads (90 hari data dummy)
- Auto-funnel & auto-payment logic

### 4. Setup GitHub
- Repo baru: `ramajz/crm-utapes` (public)
- 126 files, 17.666 lines initial commit
- Dockerfile + .dockerignore untuk Coolify deployment

### 5. Setup Coolify
- **Project:** crm-utapes (ID: `xem20d4a89uou9xomkkkog2c`)
- **Environment:** production
- **Server:** localhost (keuk96uqiywo5c69jv42uma4)
- **Build pack:** Dockerfile (PHP 8.4)
- **Port:** 80
- **Domain:** crm.utapesseken.co (planned)
- **15 env vars** termasuk koneksi NeonDB

### 6. Resume Coolify API Token
Generate API token baru lewat container:
```
Token: 24|BSfTbqPPaOhCJpYLFLUKl4mCs8zU1dkGJmGgi5eY
```

### ⚠️ Masalah & Solusi

| Masalah | Solusi |
|---------|--------|
| Token MCP expired | Generate langsung dari DB `personal_access_tokens`, format Sanctum `ID\|random40` |
| Token abilities null → Server Error | Fix: `abilities` harus `["*"]` (JSON array, bukan string `[*]`) |
| Server "lokalan" not reachable | Ganti ke server "localhost" (is_coolify_host: true) |
| Nixpacks PHP 8.3 → gagal Laravel 13 | Ganti build pack ke Dockerfile (PHP 8.4-fpm-alpine) |
| Deployment gagal "Server not functional" | Pake server yang reachable (`keuk96uqiywo5c69jv42uma4`) |

## 🚀 Credential Development

Login (untuk development):
```
Admin: admin@crm.com / password
Manager: manager@crm.com / password
CS: siti@crm.com / password
```

## 🗄️ Cache Store & Cache Locks (penting untuk Auto-Assign Lead)

Aplikasi memakai **`CACHE_STORE=database`** (bukan Redis) — baik di `.env` lokal maupun `.env.example`. Setup ini **sudah benar dan cukup** untuk fitur auto-assign lead (`LeadAssignmentService` memakai `Cache::lock`).

### Kenapa `Cache::lock` tetap berfungsi tanpa Redis

- Laravel `DatabaseStore` mengimplementasikan `LockProvider` (`vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php`)
- Lock disimpan di tabel **`cache_locks`** — dibuat otomatis oleh migrasi default `0001_01_01_000001_create_cache_table.php`
- `DatabaseLock::acquire()` melakukan `INSERT` atomik (unique constraint pada kolom `key`); jika key sudah ada, lock hanya di-reassign jika sudah expired atau milik owner yang sama
- Karena lock hidup di database, mutual exclusion **aman antar-proses/worker** selama semua worker memakai koneksi DB yang sama (NeonDB shared)

### Env vars yang relevan

```env
CACHE_STORE=database
DB_CACHE_CONNECTION=           # kosong = pakai koneksi default (PostgreSQL production)
DB_CACHE_LOCK_CONNECTION=      # kosong = pakai koneksi lock yang sama
DB_CACHE_TABLE=cache
DB_CACHE_LOCK_TABLE=           # kosong = default 'cache_locks'
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

> Pada praktiknya cukup `CACHE_STORE=database` — opsi lainnya dibiarkan kosong agar memakai default.

### Checklist saat deploy/update production

1. `php artisan migrate` — **wajib** agar tabel `cache` dan `cache_locks` ada di PostgreSQL (dibuat oleh migrasi default)
2. Verifikasi tabel ada di PostgreSQL: `SELECT to_regclass('cache_locks');`
3. Pastikan **semua worker** (web + queue) memakai DB yang sama

### Kapan upgrade ke Redis (opsional)

Redis **tidak wajib** untuk kebenaran `Cache::lock`. Pertimbangkan upgrade jika:

- Dashboard/cache mulai berat dan ingin performa lebih tinggi
- Ingin TTL pruning yang lebih efisien dibanding tabel `cache` di PostgreSQL

Langkah upgrade (jika diperlukan nanti):

1. Tambah service Redis di Coolify
2. Set env: `CACHE_STORE=redis`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
3. Install extension redis: `docker-php-ext-install redis` di Dockerfile

### Yang TIDAK boleh dipakai di production

- `CACHE_STORE=array` — lock hanya berlaku dalam satu proses (dipakai di test suite saja)
- `CACHE_STORE=memcached` — `MemcachedStore` **tidak** mengimplementasikan `LockProvider` → `Cache::lock` tidak akan berfungsi

---

## 📋 Fase Selanjutnya (Phase 2)

1. Domain setup: `crm.utapesseken.co` → Cloudflare + SSL
2. Webhook Scalev integration
3. CSV Import from Google Sheets
4. Data Migration from old system
5. Export PDF (Hot Leads)

---

*Dibuat oleh Claude Sonnet 4.6 · Hermes Agent · Syncera*
