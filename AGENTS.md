# AGENTS.md — CRM-Utapes

## Project Overview
CRM untuk Utapes — lead management, customer tracking, webhook handling.
Stack: Laravel 11 + Livewire + Alpine.js + Tailwind CSS.

## Architecture
- **Production**: Deployed on Coolify (VPS), database: NeonDB PostgreSQL
- **Local Dev**: SQLite (file: `database/database.sqlite`)
- **Auth**: Laravel Breeze (Livewire)
- **Frontend**: Livewire components + Alpine.js

## Data Paid / Closing — Source of Truth (Scalev)

**Context:** CRM receives order data from Scalev. There are THREE different systems that count "paid/closing", and they disagree:

| System | Basis hitung | Juli 2026 |
|--|--|--|
| **NocoBase manager (lama)** | Rekap manager sendiri | 1.074 paid |
| **Scalev `orders.paid_time`** ← **SOURCE OF TRUTH** | Order dibayar per `paid_time` | **863 paid** |
| **CRM leads (`last_update_at`)** | Lead status paid di-CRM | 811 / 790 (varian) |

**Keputusan:** `orders.paid_time` (Scalev) adalah sumber kebenaran untuk metrik **paid / revenue per CS**. Lead tetap lapisan CRM (follow-up, status), tapi closing & revenue diambil dari tabel `orders`.

### Aturan hitung yang WAJIB diikuti (jangan sampai salah lagi)

1. **Jangan filter `payment_status = 'paid'` untuk metrik closing.** Order yang pernah dibayar lalu statusnya di-revert (unpaid) TETAP dihitung di bulan `paid_time`-nya. Dengan filter itu Juli jadi 764 (salah); tanpa filter = 863 (benar, cocok rekap Python/NocoBase).
2. Basis query: `Order::whereBetween('paid_time', [$start, endOfDay])`.
3. Revenue = `SUM(gross_revenue)` pada baris yang sama (Juli = **Rp 480.475.000**).
4. Per-CS: group by `orders.handler_id`. Order dengan `handler_id NULL` → baris "Tanpa CS (unassigned)".
5. **Offline store (Maya) diabaikan** — CS misterius Babe/Ardha/Hafiz/Yusril dari rekap NocoBase itu = offline store, bukan CS online. Fokus CS online.
6. Ringkasan bulanan by `paid_time`: Feb **282**, Mar **424**, Apr **568**, Mei **502**, Jun **515**, Jul **863**, Agu **24**.

### Implementasi
- Tabel `orders` + `order_items` = sumber kebenaran order (68.635 order historis dari `Webhook_Logs.csv`).
- Backfill: `php artisan scalev:import --path=<csv>` (idempotent, bisa di-ulang).
- Live sync: `POST /api/webhook/scalev` → `ScalevOrderSync::processEvent()` + sync lead. Auth: header `X-Scalev-Secret` (env `SCALEV_WEBHOOK_SECRET`).
- Dashboard (`DashboardController` + `LeadService::getHandlerStats`) memakai `orders.paid_time` untuk `closing`/`total_revenue`, sementara `total`/`followed_up` tetap dari `leads` (intake CRM).


## Local Development

### Database (SQLite)
```bash
# Setup
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### .env Configuration
```env
# LOCAL — SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/Users/rama/crm-utapes/database/database.sqlite

# PRODUCTION — PostgreSQL (NeonDB)
# DB_CONNECTION=pgsql
# DB_HOST=ep-xxx.us-east-2.aws.neon.tech
# DB_PORT=5432
# DB_DATABASE=neondb
```

### Login Default
| Role | Email | Password |
|--|--|--|
| Admin | admin@crm.com | password |
| Manager | manager@crm.com | password |
| CS | siti@crm.com | password |

## Important Rules

### Database Compatibility
- **MUST use Eloquent/Query Builder** — no raw SQL that's PostgreSQL-specific
- **DB::raw() is OK** for simple functions: `date()`, `count()`, `CASE WHEN`
- **AVOID**: `DB::select()`, raw PostgreSQL functions like `interval`, `:: casting`
- All migrations must work on both SQLite AND PostgreSQL

### Code Style
- PHP 8.2+ features (readonly, enums, named arguments)
- Livewire components for interactive UI
- Alpine.js for client-side interactivity
- Tailwind CSS for styling
- No jQuery, no Bootstrap

### File Structure
```
app/
├── Http/Controllers/    ← Web controllers
├── Livewire/           ← Livewire components
├── Models/             ← Eloquent models
├── Services/           ← Business logic
└── View/Components/    ← Blade components
```

## Testing Checklist
- [ ] `php artisan migrate:fresh --seed` passes
- [ ] `php artisan serve` → login works
- [ ] Dashboard loads with charts
- [ ] Lead CRUD works
- [ ] Webhook endpoint responds

## Deployment
- Push to `main` → Coolify auto-deploys
- Never push broken code to `main`
- Test locally first (SQLite), then push

## Investigasi Data (2026-08-02)

### Temuan: NocoBase vs Scalev = SAMA untuk Paid Orders

| Metrik | NocoBase | Scalev | Gap |
|--|--|--|--|
| Total Order (Juli) | 1.074 | - | - |
| Paid Order (Juli) | 863 | 863 | **0** |
| Unpaid | 211 | - | - |

**Kesimpulan:** Manager menghitung TOTAL ORDER (1.074) sebagai "closing", padahal yang benar-bayar cuma 863. NocoBase dan Scalev SEBENARNYA SAMA untuk paid orders.

**Action:** Gunakan kolom "Paid" (bukan "Order") untuk metrik closing/revenue.
