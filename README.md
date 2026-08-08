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

### 0. Fitur Utama

- Lead and customer management with follow-up status, funnel stage, notes, and audit history.
- Scalev webhook sync for orders, payments, customers, and leads.
- **Automatic lead assignment** to active CS handlers — strategi `least_loaded` (default) dan `round_robin`, bisa diatur via `.env` (`LEAD_AUTO_ASSIGN`, `LEAD_ASSIGN_STRATEGY`).
- **Wajib Follow-Up**: manager/admin menandai lead prioritas; CS melihatnya di menu khusus dan menandai selesai.
- **Bulk Reassign CS**: manager memindahkan banyak lead ke CS lain sekaligus.
- **Branches**: Lumajang & Kediri dengan NocoBase mapping (`branch_id` pada leads, orders, handlers).
- **WhatsApp Message Templates**: template pesan dengan variabel dinamis (`{nama}`, `{order_id}`, `{size}`, `{total}`, `{handler}`) yang terbuka di `wa.me` dengan pesan terisi — CS bisa pilih template & edit sebelum kirim.
- Local development with SQLite; production uses PostgreSQL.

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

### 3. Import Data Real (AppScript)
```bash
php artisan migrate:sheets --looker="App-Utapes - Leads_Jul_2026.csv" --flush
```

- Supports AppScript headers (`Phone (WA)`, `Handler (CS)`, `Financial Status`, etc.)
- Maps CS aliases to canonical names (Lana → Hafiz, Kiki ternyata → Kiki, etc.)
- `--flush` clears old data first; `--dry-run` previews without writing.

### 4. Dashboard (`/dashboard`)
- **Closing & revenue** = `orders.paid_time` (Scalev) — chart harian + per handler
- **Total & followed-up lead** = `leads` (intake CRM)
- Per-CS: closing/revenue dari `orders.handler_id`, lead dari `leads.handler_id`

### 5. Lead Management
- CRUD lead + detail (`/leads`, `/leads/{id}`)
- Update status lead + history (`LeadHistory`), toast + auto-reload
- `last_update_at` di leads (tracking follow-up terakhir)
- Auto-assign: lead baru tanpa handler di-assign otomatis ke CS aktif (lock anti race condition)

### 6. Follow-up CS via WhatsApp — Template Pesan (bukan WABA)
Fitur bantu CS follow-up: halaman detail lead menampilkan **pilihan template pesan** (kategori cold/warm/hot). Klik template → textarea pesan terisi (variabel `{nama}`, `{order_id}`, dll. sudah dirender), CS bisa edit, lalu **Kirim WA** → tab WA terbuka dengan pesan terisi. Ini murni fitur internal CRM, **tidak terkait chatbot WABA**.

Alur:
1. Halaman detail lead → pilih **template** di kartu "Template Pesan WhatsApp" — textarea muncul dengan pesan terisi
2. CS edit pesan sesuai kebutuhan, klik **Kirim WA** (`wa.me/<no_customer>?text=<pesan>`) — tab WA terbuka
3. Balik ke CRM → modal **"Kembali dari WhatsApp!"** muncul otomatis (`showWaNotesModal` event)
4. Form notes aktif — CS isi **Status FU**, **Notes**, **Ukuran (size)**
5. Simpan → tercatat di `lead_histories`

> **Kelola template**: manager/admin CRUD template di `/templates` (`WhatsAppTemplateController`). Komponen lain: `app/Livewire/UpdateLeadStatus.php` + `resources/views/livewire/update-lead-status.blade.php`. Event: `openUpdateModal`, `showWaNotesModal`.
> ⚠️ **Beda dengan WABA**: template WABA (9 template chatbot) itu project terpisah di `/home/rmjz/utapes-waba-templates.md` (masih draft) — jangan dicampur.

### 7. Wajib Follow-Up & Reassign
- **Tandai Wajib FU**: manager/admin menandai lead dari daftar lead (tombol di `leads/index`) — `follow_up_required=true`, status `pending`
- **Menu Follow-Up** (`/leads/follow-up`): daftar lead wajib FU — CS hanya lihat miliknya, manager/admin bisa filter handler/cabang/status/rentang tanggal; pending di atas
- **Complete**: CS menandai selesai → `follow_up_status=done` + `follow_up_completed_at`
- **Bulk Reassign**: pilih banyak lead → pindah ke satu CS (`/leads/bulk-reassign`)

### 8. Lead Assignment (auto-assign)
```bash
# Preview / assign lead yang belum punya handler:
php artisan leads:assign-unassigned --dry-run
php artisan leads:assign-unassigned
```

Config di `.env`:
```env
LEAD_AUTO_ASSIGN=true
LEAD_ASSIGN_STRATEGY=least_loaded
```

### 9. Services
| Service | Fungsi |
|---------|--------|
| `ScalevOrderSync` | Upsert order/items/customer/handler dari webhook |
| `UtmParserService` | Klasifikasi traffic: `cpc/ppc/paid/ads` atau `fbads/googleads` → **Ads**; ada UTM → **Organik**; tanpa UTM → **Direct** |
| `LoyaltyService` | Deteksi customer **new/repeat** by phone + `findOrCreate` (lock anti race condition, increment total_orders/total_spend) |
| `LeadAssignmentService` | Auto-assign lead ke CS aktif (`least_loaded` / `round_robin`) dengan assignment lock |
| `LeadService` | Statistik handler (closing/revenue/lead) untuk dashboard + markFollowUp/completeFollowUp/reassignHandler/bulkReassign |

### 10. Laporan Bulanan (tools)
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
| `orders` | Sumber kebenaran order Scalev (paid_time, gross_revenue, handler_id, branch_id) |
| `order_items` | Item per order |
| `leads` | Lead CRM (funnel, status FU, UTM, handler, branch_id, follow_up_required/status/completed_at) |
| `lead_histories` | Riwayat perubahan status lead |
| `customers` | Customer (total_orders, total_spend) |
| `handlers` | CS / handler (branch_id) |
| `branches` | Cabang (Lumajang, Kediri) + mapping NocoBase |
| `whatsapp_templates` | Template pesan WA (category cold/warm/hot, is_active) |
| `webhook_logs` | Log mentah webhook masuk |

---

## 🧪 Testing
```bash
php artisan migrate:fresh --seed
php artisan test    # ScalevWebhookTest, LeadAutoAssignTest, FollowUpReassignTest, TemplateWaTest
```

## 🚀 Deployment
- Push `main` → Coolify auto-deploy. Jangan pernah push kode rusak ke `main`.
- Detail: `PHASE1_DEPLOY.md`, lokal: `LOCAL_DEV.md`
