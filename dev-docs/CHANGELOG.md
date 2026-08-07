# CHANGELOG

> **Status:** DATA FILE — mencatat perubahan signifikan per milestone/sprint.
> **Related:** [dev-docs/ai/VERSION.md](./ai/VERSION.md)

---

## [Unreleased] — Current Sprint/Milestone

### Added
- Fitur Wajib Follow-Up: manager/admin menandai lead wajib follow-up, CS lihat di menu khusus & tandai selesai.
- Bulk Reassign CS: manager/admin pindahkan banyak lead sekaligus ke CS lain, status FU ikut terbawa.
- Fondasi cabang: tabel `branches` (Lumajang/Kediri), kolom `branch_id` di leads/orders/handlers, import `cabang_id` dari NocoBase.
- Template Pesan WhatsApp: panel pilih template di detail lead, variabel `{nama}`, `{order_id}`, `{size}`, `{total}`, `{handler}` terisi otomatis, buka wa.me dengan pesan siap kirim.
- Tombol Wajib FU di daftar lead (toggle cepat untuk manager/admin).
- Import data real Juli 2026 (16.212 lead) dari AppScript CSV, dengan alias handler sesuai DATA_ACUAN.md.

### Changed
- `MigrateFromSheets` mendukung header AppScript (`Phone (WA)`, `Handler (CS)`, dll), alias CS, dan flag `--flush`.
- Halaman detail lead: tombol Chat WhatsApp tunggal diganti panel "Template Pesan WhatsApp".

### Fixed
- (tidak ada)

---

## Archived Changelogs

- (belum ada)
