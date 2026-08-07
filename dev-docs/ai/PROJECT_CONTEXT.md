# PROJECT_CONTEXT

> **Status:** DATA FILE — konteks project agar AI baru langsung paham.
> **Related:** [CURRENT_STATE.md](./CURRENT_STATE.md) · [MODULE_MAP.md](./MODULE_MAP.md)

---

## Project Overview

**CRM-Utapes** — CRM lead management untuk **Utapes** (bisnis sepatu/sneakers, 2 cabang: Lumajang & Kediri, 11-12 CS). Stack: **Laravel 11 + Livewire + Alpine.js + Tailwind CSS**.

## Business Model

- Volume 10-17 ribu lead/bulan, closing rate ~4-7%.
- 3 kanal lead: Meta Ads (landing page Scalev), Organik socmed, Direct WA ke CS.
- Insentif CS Rp 20rb/closing.
- Customer behaviour unik: lead bisa masuk bulan X tapi bayar bulan Y (nunggu gajian, promise transfer).

## Three Data Sources (KRITIS — baca DATA_ACUAN.md di root)

| Sistem | Isi | Peran |
|--------|-----|-------|
| **NocoBase** | SEMUA transaksi order (ERP) | FAKTA TRANSAKSI (volume, closing, revenue) |
| **AppScript (CSV)** | Order dari webhook Scalev (Meta Ads LP) | KONTEKS & KUALITAS (funnel, notes, traffic, UTM) |
| **Laporan Manager** | Validasi manual per cabang | VALIDASI PER CS |

**Aturan emas:** 1 metrik = 1 sumber. NocoBase = "BERAPA", AppScript = "BAGAIMANA", Manager = "SIAPA". Angka beda antar sumber = INFORMASI, bukan error.

**Source of truth closing/revenue = `orders.paid_time` (Scalev)** — lihat AGENTS.md.

## Tech Stack

- Laravel 11, Livewire 3, Alpine.js, Tailwind CSS
- Database: SQLite (local), NeonDB PostgreSQL (production)
- Production: Coolify (VPS) — push `main` auto-deploy
- Auth: Laravel Breeze (Livewire)

## Struktur Database Inti

- `users` — admin/manager/cs (role)
- `handlers` — CS, relasi user, branch_id, is_active
- `branches` — Lumajang/Kediri, nocobase_id
- `customers` — phone unik, name, stats
- `leads` — order_id unik, customer/handler/branch, financial_status, funnel_stage, status_fu, notes, size, UTM, traffic_type, follow_up_*, timestamps
- `orders` — source of truth Scalev, paid_time, gross_revenue
- `order_items` — item per order
- `lead_histories` — audit trail semua perubahan

## Role & Akses

| Role | Akses |
|------|-------|
| **CS** | Lead miliknya sendiri (handler_id), follow-up, tandai wajib FU selesai |
| **Manager** | Semua lead, tandai wajib FU, bulk reassign, performa per CS |
| **Admin** | Seperti manager + kelola sistem |
| **Owner** | (sesuai PRD, belum diimplementasi khusus) |

## Environment

```env
# LOCAL — SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/crm-utapes/database/database.sqlite

# Auto-assign
LEAD_AUTO_ASSIGN=true
LEAD_ASSIGN_STRATEGY=least_loaded

# Scalev webhook
SCALEV_WEBHOOK_SECRET=***
```

## UI/UX

- Tidak ada HTML template komersial — pakai Tailwind CSS standar
- Livewire untuk interaktivitas, Alpine.js untuk client-side
- Mobile-first: bottom navigation di mobile, top nav di desktop
