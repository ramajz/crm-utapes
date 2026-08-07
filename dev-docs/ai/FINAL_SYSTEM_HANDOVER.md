# FINAL_SYSTEM_HANDOVER

> **Status:** DATA FILE — referensi handover untuk engineer/AI agent baru.
> **Note:** Boleh di-include ke `main` saat merge (tidak ada credential).

---

## Sistem: CRM-Utapes

CRM lead management untuk Utapes (bisnis sepatu, 2 cabang: Lumajang & Kediri).

## Akses Cepat

- **Local dev:** `php artisan serve` → `http://localhost:8000`
- **Test:** `php artisan test` (52 test, 150 assertions)
- **Login dev:** admin@crm.com / manager@crm.com / peler@crm.com / kiki@crm.com / oben@crm.com — password `password`

## Fitur Utama (Juli 2026)

1. **Lead Management** — daftar lead, filter, detail, follow-up status (funnel auto, auto-paid saat closing), audit history.
2. **Scalev Webhook** — sync order real-time, `orders.paid_time` = source of truth closing/revenue.
3. **Auto-Assign Lead** — `leads:assign-unassigned`, strategi least_loaded (default) / round_robin.
4. **Wajib Follow-Up** — manager tandai lead prioritas, CS lihat di menu "Wajib FU" dan tandai selesai.
5. **Bulk Reassign CS** — manager pindahkan banyak lead antar CS.
6. **Template WhatsApp** — panel template di detail lead, variabel dinamis `{nama}` dll, buka wa.me dengan pesan siap kirim.
7. **Import AppScript** — `migrate:sheets --looker="App-Utapes - Leads_<bulan>.csv" --flush`.

## Arsitektur Singkat

- Laravel 11 + Livewire + Alpine + Tailwind
- DB: SQLite (local) / PostgreSQL (prod, NeonDB)
- Kode bisnis di `app/Services/`, UI di `resources/views/`
- Module map lengkap: `dev-docs/ai/MODULE_MAP.md`

## Data Penting

- **16.212 lead** (Juli 2026, AppScript), 15.140 customer, 16 handler (11 aktif utama).
- **Source of truth closing/revenue = `orders.paid_time` + `gross_revenue`** — BUKAN `leads.total_value` (95% kosong).
- Cabang: Lumajang (`358537632219136`), Kediri (`358537655287808`) — `branch_id` di leads/orders/handlers masih NULL sampai sync NocoBase.
- Aturan data lengkap: `DATA_ACUAN.md` (root).

## Role

| Role | Akses |
|------|-------|
| CS | Lead sendiri, follow-up, wajib FU selesai |
| Manager | Semua, tandai wajib FU, bulk reassign |
| Admin | Seperti manager + sistem |

## Deployment

- Push `main` → Coolify auto-deploy (production: NeonDB PostgreSQL).
- Jangan pernah push kode rusak ke `main`. Test local dulu.

## Roadmap Selanjutnya

1. CS pindah penuh dari AppScript → CRM-Utapes (matikan AppScript).
2. Sync NocoBase → CRM (isi branch_id).
3. Validasi Manager (approve/reject closing).
4. Chatbot WABA.
5. Report Generator bulanan (migrasi join_laporan.py).
6. Reconciliation otomatis.
