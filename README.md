# CRM-Utapes

CRM untuk Utapes — lead management, customer tracking, webhook handling.
**Stack:** Laravel 11 + Livewire + Alpine.js + Tailwind CSS. Production: Coolify (VPS) + NeonDB PostgreSQL. Lokal: SQLite.

---

## ⚡ Source of Truth — Data Paid/Closing (2026-08-07)

> **Baca AGENTS.md + DATA_ACUAN.md dulu sebelum sentuh data.** Ringkasan:

| Metrik | Sumber | Juli 2026 |
|--------|--------|-----------|
| **Closing / Paid** | `orders.paid_time` (Scalev) | **863** |
| **Revenue** | `SUM(orders.gross_revenue)` | **Rp 480.475.000** |
| **Lead** (order masuk) | NocoBase (excl offline_store) | 17.538 |

Aturan kunci: **jangan filter `payment_status='paid'`** (order yang di-revert tetap dihitung di bulan paid_time-nya); per-CS group by `orders.handler_id`; offline store diabaikan.

---

## 🧩 Fitur & Modul

### 1. Ingest Order Scalev (tabel `orders` + `order_items`)
Sumber kebenaran order (68.635 order historis dari `Webhook_Logs.csv`).

```bash
# Backfill historis (idempotent, bisa diulang):
php artisan scalev:import --path=database/imports/Webhook_Logs.csv
php artisan scalev:import --dry-run   # preview tanpa tulis DB
```

**Live sync:** `POST /api/webhook/scalev` → `ScalevOrderSync::processEvent()` (upsert order + items + customer + handler). Auth: header `X-Scalev-Secret` (env `SCALEV_WEBHOOK_SECRET`).

### 2. Migrasi dari Google Sheets (legacy → DB)
```bash
php artisan migrate:sheets --path=database/imports   # Looker_Master.csv + Customer_Master.csv
php artisan migrate:sheets --dry-run
```
Import lead (Looker_Master) + customer (Customer_Master) dari export Google Sheets. Idempotent (upsert by key), handler/customer auto-create.

### 3. Dashboard (`/dashboard`)
- **Closing & revenue** = `orders.paid_time` (Scalev) — chart harian + per handler
- **Total & followed-up lead** = `leads` (intake CRM)
- Per-CS: closing/revenue dari `orders.handler_id`, lead dari `leads.handler_id`

### 4. Lead Management
- CRUD lead + detail (`/leads`, `/leads/{id}`)
- Update status lead + history (`LeadHistory`), toast + auto-reload
- `last_update_at` di leads (tracking follow-up terakhir)

### 5. Follow-up CS via WhatsApp — Pesan Prefilled (bukan WABA)
Fitur bantu CS follow-up: klik **Chat WhatsApp** → WA terbuka dengan **pesan follow-up sudah terisi otomatis** (biar CS gak ngetik dari nol). Ini murni fitur internal CRM, **tidak terkait chatbot WABA**.

Alur:
1. Halaman detail lead → klik **Chat WhatsApp** (`wa.me/<no_customer>?text=<template>`) — tab WA terbuka, pesan sudah terisi
2. CS tinggal review/kirim, lalu balik ke CRM
3. Modal **"Kembali dari WhatsApp!"** muncul otomatis (`showWaNotesModal` event)
4. Form notes aktif — CS isi **Status FU**, **Notes**, **Ukuran (size)**
5. Simpan → tercatat di `lead_histories`

> Pengganti form follow-up AppScript (PRD v2 M3). Komponen: `app/Livewire/UpdateLeadStatus.php` + `resources/views/livewire/update-lead-status.blade.php`. Event: `openUpdateModal`, `showWaNotesModal`.
> ⚠️ **Beda dengan WABA**: template WABA (9 template chatbot) itu project terpisah di `/home/rmjz/utapes-waba-templates.md` (masih draft) — jangan dicampur.

### 6. Services
| Service | Fungsi |
|---------|--------|
| `ScalevOrderSync` | Upsert order/items/customer/handler dari webhook |
| `UtmParserService` | Klasifikasi traffic: `cpc/ppc/paid/ads` atau `fbads/googleads` → **Ads**; ada UTM → **Organik**; tanpa UTM → **Direct** |
| `LoyaltyService` | Deteksi customer **new/repeat** by phone + `findOrCreate` (lock anti race condition, increment total_orders/total_spend) |
| `LeadService` | Statistik handler (closing/revenue/lead) untuk dashboard |

### 7. Laporan Bulanan (tools)
```bash
python3 scripts/join_laporan.py --bulan 2026-07 --csv "Leads_Jul_2026.csv"
```
Output: `laporan-YYYY-MM.md` (draft lengkap) + `master-YYYY-MM.csv` (tabel join per order). Formulasi lengkap: **DATA_ACUAN.md**.

---

## 🔒 Webhook Auth
- Header: `X-Scalev-Secret: <SCALEV_WEBHOOK_SECRET>`
- Tanpa secret valid → 401. Test: `tests/Feature/ScalevWebhookTest.php`

---

## 🗄️ Database (migration utama)
| Tabel | Isi |
|-------|-----|
| `orders` | Sumber kebenaran order Scalev (paid_time, gross_revenue, handler_id) |
| `order_items` | Item per order |
| `leads` | Lead CRM (funnel, status FU, UTM, handler) |
| `lead_histories` | Riwayat perubahan status lead |
| `customers` | Customer (total_orders, total_spend) |
| `handlers` | CS / handler |
| `webhook_logs` | Log mentah webhook masuk |

---

## 🧪 Testing
```bash
php artisan migrate:fresh --seed
php artisan test    # termasuk ScalevWebhookTest
```

## 🚀 Deployment
- Push `main` → Coolify auto-deploy. Jangan pernah push kode rusak ke `main`.
- Detail: `PHASE1_DEPLOY.md`, lokal: `LOCAL_DEV.md`
