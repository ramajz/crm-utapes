# VERSION

> **Status:** DATA FILE — rekomendasi versi rilis berdasarkan CHANGELOG.
> **Related:** [CHANGELOG.md](../CHANGELOG.md)

---

## Current Version

- **Versi:** 0.4.0 (rekomendasi, belum dirilis)
- **Terakhir rilis:** (belum ada rilis resmi — semua masih `[Unreleased]`)

## Rekomendasi Versi Berikutnya

Berdasarkan isi `[Unreleased]` di CHANGELOG (beberapa fitur baru Added → MINOR):

| Item | Kategori | Trigger |
|------|----------|---------|
| Wajib Follow-Up | Added | MINOR |
| Bulk Reassign CS | Added | MINOR |
| Fondasi Cabang | Added | MINOR |
| Template WhatsApp | Added | MINOR |
| Tombol Wajib FU di index | Added | MINOR |
| Import data real Juli | Added | MINOR |

**Rekomendasi:** `0.4.0` — MINOR bump karena banyak fitur baru Added, tanpa breaking change.

## Version History

| Versi | Tanggal | Catatan |
|-------|---------|---------|
| — | — | Belum ada rilis resmi; semua fitur masih `[Unreleased]` |

## Prosedur

1. CHANGELOG mencatat perubahan di `[Unreleased]` setiap task.
2. Sebelum merge `dev → main`: rekomendasikan versi, minta approval user, tutup `[Unreleased]`.
