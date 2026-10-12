# Database Plan — CRM-Utapes

> **Source of truth:** migrasi di `database/migrations/`.
> **Dokumen ini dibuat otomatis dari migrasi (12 Okt 2026).**
> Jika migrasi berubah, update dokumen ini.

> **Database:** PostgreSQL (NeonDB) di produksi, SQLite di lokal dev.
> Aturan: semua query harus kompatibel dua-duanya.

---

## 1. Database Topology

| Connection | DB Engine | Database | Purpose |
|-----------|----------|---------|---------|
| `pgsql` (prod) | PostgreSQL | `neondb` (NeonDB) | Data CRM Utapes |
| `sqlite` (dev) | SQLite | `database/database.sqlite` | Local development |

---

## 2. Entity Relationship Diagram

```text
                        users
                          │
            ┌─────────────┼─────────────┐
            │ user_id     │ user_id     │
            ▼             ▼             │
       handlers          │             │
            │             │             │
            │ branch_id   │             │
            │   ┌─────────┘             │
            │   │                       │
   branches ┼───┼──── orders ───────────┘
            │   │       │
            │   │       └──< order_items
            │   │
            │   └──── leads
            │            │
            │            └──< lead_histories
            │
            └── (semua tabel core punya branch_id nullable)

customers (standalone, unique key: phone)
webhook_logs (log event masuk)
whatsapp_templates (template pesan broadcast)
```

---

## 3. Table Schemas

### `users` — Akun sistem

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `name` | string | | |
| `email` | string | UNIQUE | |
| `email_verified_at` | timestamp | nullable | |
| `password` | string | | bcrypt |
| `role` | string | default `cs` | `admin` / `manager` / `cs` |
| `phone` | string(20) | nullable | |
| `is_active` | boolean | default `true` | |
| `remember_token` | string | | |
| `created_at`, `updated_at` | timestamp | | |

---

### `handlers` — CS / petugas penanganan lead

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `user_id` | bigint | FK → `users`, nullable, `nullOnDelete` | |
| `branch_id` | bigint | FK → `branches`, nullable | cabang tempat CS |
| `name` | string(100) | | nama CS |
| `phone` | string(20) | nullable | |
| `is_active` | boolean | default `true` | |
| `created_at`, `updated_at` | timestamp | | |

---

### `customers` — Data pelanggan

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `phone` | string(20) | UNIQUE, INDEX | nomor WhatsApp |
| `name` | string(100) | INDEX | |
| `total_orders` | integer | default 0 | dihitung dari orders |
| `total_spend` | decimal(15,0) | default 0 | akumulasi pengeluaran |
| `first_purchase_at` | timestamp | nullable | |
| `last_purchase_at` | timestamp | nullable | |
| `notes` | text | nullable | |
| `created_at`, `updated_at` | timestamp | | |

---

### `leads` — Pipeline penjualan (dari Scalev LP)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `order_id` | string(50) | UNIQUE | ID asli dari Scalev (`ORD-xxxx`) |
| `customer_id` | bigint | FK → `customers`, nullable | |
| `handler_id` | bigint | FK → `handlers`, nullable | CS yang menangani |
| `branch_id` | bigint | FK → `branches`, nullable | |
| `financial_status` | string(20) | default `unpaid`, INDEX | `unpaid`/`paid`/`lunas` |
| `total_value` | decimal(15,0) | default 0 | nilai order |
| `funnel_stage` | string(10) | default `cold`, INDEX | `hot`/`warm`/`cold` |
| `status_fu` | string(30) | default `new`, INDEX | **indikator follow-up**: `new` = belum disentuh |
| `notes` | text | nullable | |
| `size` | string(5) | nullable | |
| `utm_source` | string(100) | nullable | |
| `utm_medium` | string(100) | nullable | |
| `utm_campaign` | string(100) | nullable | |
| `utm_content` | string(100) | nullable | |
| `traffic_type` | string(20) | nullable, INDEX | `ads`/`organik`/`direct` |
| `lead_type` | string(10) | default `new`, INDEX | `new`/`repeat` |
| `first_replied_at` | timestamp | nullable, INDEX | waktu balasan pertama |
| `last_update_at` | datetime | nullable | proxy waktu update terakhir |
| `timestamp` | datetime | INDEX | **lead masuk** (asli dari Scalev) |
| `follow_up_required` | boolean | default `false` | perlu follow-up? |
| `follow_up_status` | string(10) | nullable | status follow-up |
| `follow_up_completed_at` | timestamp | nullable | waktu follow-up selesai |
| `created_at`, `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | soft delete |

**Metrik follow-up** dihitung: `status_fu != 'new'` = sudah di-follow-up.
Ini indikator status di spreadsheet/CRM, **bukan bukti percakapan nyata**.

---

### `lead_histories` — Riwayat perubahan lead

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `lead_id` | bigint | FK → `leads`, CASCADE | |
| `user_id` | bigint | FK → `users`, nullable | siapa yang mengubah |
| `field_changed` | string(50) | | kolom yang berubah |
| `old_value` | text | nullable | |
| `new_value` | text | nullable | |
| `created_at`, `updated_at` | timestamp | | |
| | | INDEX | `(lead_id, created_at)` |

---

### `orders` — Transaksi final (dari Scalev)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `order_id` | string(50) | UNIQUE | ID Scalev |
| `customer_id` | bigint | FK → `customers`, nullable | |
| `handler_id` | bigint | FK → `handlers`, nullable | CS closing |
| `branch_id` | bigint | FK → `branches`, nullable | |
| `status` | string(30) | default `draft`, INDEX | `draft`/`pending`/`confirmed`/`shipped`/`completed`/`canceled` |
| `payment_status` | string(20) | default `unpaid`, INDEX | |
| `is_probably_spam` | boolean | default `false` | deteksi spam |
| `source` | string(20) | default `scalev` | |
| `*_time` (16 kolom) | datetime | nullable | timeline status: `created`, `draft`, `pending`, `confirmed`, `in_process`, `ready`, `shipped`, `completed`, `rts`, `canceled`, `closed`, `unpaid`, `paid`, `conflict`, `settled`, `transfer` |
| `payment_method` | string(100) | nullable | |
| `epayment_provider` | string(100) | nullable | |
| `financial_entity` | string(100) | nullable | |
| `payment_account_holder` | string(100) | nullable | |
| `payment_account_number` | string(100) | nullable | |
| `transferproof_url` | string | nullable | |
| `pg_reference_id` | string | nullable | |
| `pg_payment_info` | json | nullable | |
| `gross_revenue` | decimal(15,0) | default 0 | **revenue kotor** |
| `scalev_fee` | decimal(15,0) | default 0 | fee platform |
| `payment_fee` | decimal(15,0) | default 0 | fee payment gateway |
| `net_payment_revenue` | decimal(15,0) | default 0 | |
| `unique_code_discount` | decimal(15,0) | default 0 | |
| `discount_code_discount` | decimal(15,0) | default 0 | |
| `net_revenue` | decimal(15,0) | default 0 | **revenue bersih** |
| `product_price` | decimal(15,0) | default 0 | |
| `product_discount` | decimal(15,0) | default 0 | |
| `other_income` | decimal(15,0) | default 0 | |
| `cogs` | decimal(15,0) | default 0 | harga pokok |
| `shipping_cost` | decimal(15,0) | default 0 | |
| `shipping_discount` | decimal(15,0) | default 0 | |
| `discount_rate` | decimal(8,2) | default 0 | |
| `total_quantity` | integer | default 0 | jumlah item |
| `total_weight` | integer | default 0 | |
| `advertiser` | string(100) | nullable | |
| `utm_source` … `utm_id` | string(100) | nullable | atribusi iklan |
| `traffic_type` | string(20) | nullable | |
| `store` | string(100) | nullable | |
| `destination` | json | nullable | alamat pengiriman |
| `notes` | text | nullable | |
| `raw_payload` | json | nullable | payload asli dari Scalev |
| `created_at`, `updated_at` | timestamp | | |

**Aturan hitung closing & revenue (AGENTS.md):**
- Basis: `paid_time` (bukan `payment_status`)
- Jangan filter `payment_status='paid'` — order yang pernah dibayar tetap dihitung
- Per CS: group by `handler_id`; NULL → "Tanpa CS"

---

### `order_items` — Rincian item per order

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `order_id` | bigint | FK → `orders`, CASCADE | |
| `position` | integer | default 0 | urutan |
| `product_name` | string(255) | nullable | |
| `variant_unique_id` | string(100) | nullable | |
| `variant_sku` | string(100) | nullable | |
| `quantity` | integer | default 1 | |
| `weight` | integer | default 0 | |
| `product_price` | decimal(15,0) | default 0 | |
| `variant_price` | decimal(15,0) | default 0 | |
| `variant_cogs` | decimal(15,0) | default 0 | |
| `discount` | decimal(15,0) | default 0 | |
| `created_at`, `updated_at` | timestamp | | |

---

### `branches` — Cabang / departemen

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `name` | string(100) | | nama cabang |
| `nocobase_id` | string(50) | nullable, UNIQUE | ID cabang di NocoBase |
| `is_active` | boolean | default `true` | |
| `created_at`, `updated_at` | timestamp | | |

---

### `webhook_logs` — Log event masuk (Scalev)

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `order_id` | string(50) | nullable, INDEX | |
| `event_type` | string(100) | nullable | |
| `payload` | json | | payload asli |
| `status` | string(20) | default `pending`, INDEX | `pending`/`processing`/`processed`/`failed` |
| `error_message` | text | nullable | |
| `processed_at` | timestamp | nullable | |
| `created_at`, `updated_at` | timestamp | INDEX | |

---

### `whatsapp_templates` — Template pesan WA

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint | PK | |
| `name` | string(100) | | nama template |
| `category` | string(10) | default `cold` | `cold`/`warm`/`hot` |
| `message` | text | | isi pesan |
| `is_active` | boolean | default `true` | |
| `created_at`, `updated_at` | timestamp | | |

---

### `sessions`, `cache`, `jobs`, `failed_jobs` — Infrastruktur Laravel

Tabel bawaan Laravel (session, cache, queue). **Jangan diubah manual.**

---

## 4. Relationships Summary

| Table A | Relation | Table B | Foreign Key |
|---------|----------|---------|-------------|
| `users` | hasOne | `handlers` | `handlers.user_id` |
| `handlers` | belongsTo | `branches` | `handlers.branch_id` |
| `leads` | belongsTo | `customers` | `leads.customer_id` |
| `leads` | belongsTo | `handlers` | `leads.handler_id` |
| `leads` | belongsTo | `branches` | `leads.branch_id` |
| `lead_histories` | belongsTo | `leads` | `lead_histories.lead_id` |
| `orders` | belongsTo | `customers` | `orders.customer_id` |
| `orders` | belongsTo | `handlers` | `orders.handler_id` |
| `orders` | belongsTo | `branches` | `orders.branch_id` |
| `order_items` | belongsTo | `orders` | `order_items.order_id` |
| `customers` | hasMany | `leads` | — |
| `customers` | hasMany | `orders` | — |

---

## 5. Indexing Strategy

| Table | Index | Columns | Type | Reason |
|-------|-------|---------|------|--------|
| `leads` | `leads_order_id_unique` | `order_id` | UNIQUE | dedup webhook |
| `leads` | `leads_status_fu_index` | `status_fu` | BTREE | filter follow-up harian |
| `leads` | `leads_timestamp_index` | `timestamp` | BTREE | filter tanggal lead |
| `leads` | `leads_handler_id_index` | `handler_id` | BTREE | statistik per CS |
| `orders` | `orders_order_id_unique` | `order_id` | UNIQUE | dedup webhook |
| `orders` | `orders_paid_time_index` | `paid_time` | BTREE | metrik closing/revenue |
| `customers` | `customers_phone_unique` | `phone` | UNIQUE | dedup pelanggan |
| `webhook_logs` | `webhook_logs_status_index` | `status` | BTREE | monitoring event |

---

## 6. Migration Plan

| # | Migration | Table | Notes |
|---|----------|-------|-------|
| 1 | `create_users_table` | `users` | auth + role |
| 2 | `create_handlers_table` | `handlers` | CS |
| 3 | `create_customers_table` | `customers` | pelanggan |
| 4 | `create_leads_table` | `leads` | pipeline penjualan |
| 5 | `create_lead_histories_table` | `lead_histories` | audit trail |
| 6 | `create_webhook_logs_table` | `webhook_logs` | log Scalev |
| 7 | `add_last_update_at_to_leads` | `leads` | kolom update terakhir |
| 8 | `create_orders_table` | `orders` | transaksi final |
| 9 | `create_order_items_table` | `order_items` | rincian item |
| 10 | `create_branches_table` | `branches` | cabang |
| 11 | `add_follow_up_and_branch_fields_to_leads` | `leads` | follow-up tracking |
| 12 | `add_branch_to_orders_and_handlers` | `orders`, `handlers` | relasi cabang |
| 13 | `create_whatsapp_templates_table` | `whatsapp_templates` | template broadcast |

---

## 7. Seed Data

| Seeder | Purpose | Data Source |
|--------|---------|-------------|
| `DatabaseSeeder` | default users + sample data | hardcoded |

---

## 8. Data Flow

```text
Scalev LP → POST /api/webhook/scalev → webhook_logs (log)
                                          ↓
                              ScalevOrderSync::processEvent()
                                          ↓
                              orders + order_items + leads + customers

NocoBase → manual export CSV → belum ada fitur import web
AppScript → manual export CSV → belum ada fitur import web
```
