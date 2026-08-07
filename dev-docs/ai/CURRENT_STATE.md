# CURRENT_STATE

> **Status:** DATA FILE — snapshot kondisi development terkini.
> **Related:** [TASKS.md](./TASKS.md) · [CHANGELOG.md](../CHANGELOG.md) · [VERSION.md](./VERSION.md)

---

## Snapshot

| Repo | Branch | Last Commit | Notes |
|------|--------|------------|-------|
| `crm-utapes` | `feat/lead-auto-assign` | `31a257a` | Fitur template WA terakhir; 52 test pass |

| Last Updated | `2026-08-07` |
| Updated By | `Command Code AI` |

---

## Recent Development Highlights

| Repo | Commit | Date | Summary |
|------|--------|------|---------|
| `crm-utapes` | `31a257a` | 2026-08-07 | feat: template follow-up WhatsApp + panel pilih template |
| `crm-utapes` | `b164bc0` | 2026-08-07 | feat: tombol tandai Wajib FU di daftar lead |
| `crm-utapes` | `d208cf7` | 2026-08-07 | feat: wajib follow-up + bulk reassign CS + fondasi cabang |
| `crm-utapes` | `b83100a` | 2026-08-07 | feat: import data real Juli 2026 dari AppScript CSV |
| `crm-utapes` | `c2d0171` | 2026-08-06 | feat: add automatic lead assignment |

---

## Module Maturity (Practical State)

| Module | Repo | State | Notes |
|--------|------|-------|-------|
| Auth (Breeze) | backend | Production | Login admin/manager/CS |
| Lead & Customer Management | backend | Production | CRUD + follow-up + audit history |
| Scalev Webhook Sync | backend | Production | `orders.paid_time` = source of truth closing/revenue |
| Auto-Assign Lead | backend | Production | `least_loaded` / `round_robin` |
| Wajib Follow-Up | backend | Beta | Manager tandai, CS kerjakan; menu khusus |
| Bulk Reassign CS | backend | Beta | Manager pindahkan lead massal |
| Cabang (Branches) | backend | Beta | Fondasi ada, data handler cabang masih kosong |
| Template WhatsApp | backend | Beta | Panel template + variabel dinamis |
| Import AppScript | backend | Production | `migrate:sheets` + `--flush` |

---

## Active Backlog (Non-Done Tasks)

| Priority | ID | Status | Task | Repo | Notes |
|----------|----|--------|------|------|-------|
| P1 | M3 | Todo | CS pindah penuh dari AppScript → CRM-Utapes | backend | AppScript dimatikan |
| P1 | M5 | Todo | Validasi Manager (approve/reject closing) | backend | Ditunda (user pilih fitur lain dulu) |
| P2 | M1 | Todo | Sync NocoBase → CRM (satu arah via n8n) | backend | Akan isi branch_id otomatis |
| P2 | M4 | Todo | Chatbot WABA (24/7 qualify lead) | backend | Perlu review template WABA |
| P2 | — | Todo | Assign branch ke handler CS | backend | User memilih biarkan kosong dulu |
| P3 | M6 | Todo | Report Generator bulanan (migrasi join_laporan.py ke Laravel) | backend | |
| P3 | M7 | Todo | Data Reconciliation otomatis + alert anomali | backend | |

---

## Test / QA State

| Repo | Area | Coverage | Status |
|------|------|----------|--------|
| `crm-utapes` | feature_test | ~52 test | Passing |
| `crm-utapes` | assertions | 150 | Passing |

---

## Important Nuance

- **Source of truth closing/revenue = `orders.paid_time` (Scalev)**, bukan leads. Lihat AGENTS.md untuk aturan hitung.
- **`total_value` di CSV AppScript tidak reliabel** (95% kosong) — revenue selalu dari NocoBase/Scalev.
- **Handler CS branch masih kosong** — AppScript CSV tidak punya data cabang; akan terisi dari sync NocoBase.
- **Ardha tidak ada di data AppScript Juli** — 11 CS utama aktif (dari 12).
- **Template WhatsApp di-hardcode di config** — untuk ubah, edit `config/whatsapp_templates.php`.
