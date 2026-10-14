# PRD — Modul Import Data CRM-Utapes (Web)

> **Status:** DRAFT — siap implementasi setelah Rama approve
> **Tanggal:** 13 Oktober 2026
> **Owner:** Rama (single user)

---

## 1. Problem

Rama saat ini bergantung pada agent untuk mengimpor data ke CRM Utapes
(`crm.rmjz.my.id`). Sumber data produksi adalah tab bulanan di Google
Sheets (`Leads_Jul_2026`, `Leads_Aug_2026`, `Leads_Sep_2026`,
`Leads_Okt_2026`, dst.) berisi lead + closing dari NocoBase, termasuk
catatan CS. Belum ada cara untuk mengimpor sendiri tanpa lewat CLI/agent.

## 2. Goal

Rama bisa import/update data sendiri lewat web `/admin/import`, tanpa
tergantung agent. Import aman diulang (idempotent) — data sama di-update,
bukan diduplikasi.

## 3. Feature List

### Fitur A — Import CSV via Web (rutin bulanan)

| # | Fitur | Detail |
|---|-------|--------|
| A1 | Halaman `/admin/import` | Daftar riwayat import terakhir + tombol upload |
| A2 | Upload CSV leads | Validasi header (18 kolom standar) + max 20MB |
| A3 | Pilihan mode | "Semua bulan" atau "bulan tertentu" (deteksi otomatis dari Timestamp) |
| A4 | Proses background | Jalankan `migrate:sheets-batch` sebagai proses terpisah; halaman auto-refresh menampilkan log |
| A5 | Ringkasan hasil | Leads insert/update, customers baru, baris dilewati, durasi |
| A6 | Tombol dry-run | Preview dulu tanpa menulis (wajib tersedia) |

### Fitur B — Convert NocoBase backup.sql (sekali jalan / migrasi)

| # | Fitur | Detail |
|---|-------|--------|
| B1 | Upload `backup.sql` | File dari panel database NocoBase (bisa didownload) |
| B2 | Deteksi format | PostgreSQL dump vs SQLite — tampilkan ke user sebelum jalan |
| B3 | Preview | Tampilkan tabel yang terdeteksi + jumlah baris + mapping ke tabel CRM |
| B4 | Konversi | Parse INSERT statement → mapping ke `leads`, `customers`, `orders` |
| B5 | Laporan mapping | Kolom yang cocok / tidak cocok / dilewati, jujur tanpa asumsi |

**Catatan B:** mapping final menunggu isi `backup.sql` diketahui (B2-B5
diimplementasi setelah file contel dilihat). Jangan menebak struktur
tabel NocoBase.

## 4. Data Rules (dari keputusan rekonsiliasi 2026-10-10)

1. **`order_id` = kunci utama.** ID sama → update record yang ada.
   ID beda → record terpisah, TIDAK pernah dipaksa merge / dibuang.
2. `customer.phone` unik → jadi kunci pencocokan customer.
3. Import **idempotent** — re-run aman, tidak menggandakan data.
4. **Tidak ada fitur hapus/bulk-delete** di modul ini (prinsip non-destruktif).
5. Nomor HP selalu dinormalisasi ke format `62`.

## 5. Sumber Data yang Didukung

```text
PRIORITAS (data produksi real — NocoBase):
  Tab Leads_Jul_2026, Leads_Aug_2026, Leads_Sep_2026, Leads_Okt_2026, ...
  → 18 kolom identik dengan Looker_Master
  → berisi catatan CS (kolom Notes)

SEKUNDER (sudah diimport 2026-10-13):
  Looker_Master    → 85.041 lead (Meta Ads LP Scalev)
  Customer_Master  → data kurang matang, opsional

SOURCES MASA DEPAN:
  backup.sql NocoBase → via Fitur B
```

## 6. Technical Notes

- Command `migrate:sheets-batch` SUDAH ADA & sudah teruji (85K rows).
  Fitur A tinggal memanggilnya dari web — jangan tulis ulang logikanya.
- Kode run via `exec()` / proses background; status dibaca dari file log
  (`storage/logs/import-<id>.log`). **Tanpa queue/Redis** — cukup untuk
  single user.
- File upload disimpan di `storage/app/imports/`, dibersihkan setelah 7 hari.
- Format CSV identik 18 kolom Google Sheets (sudah ada alias header di
  command).
- Laravel 11 + Livewire + Tailwind — ikuti pola controller/view yang ada.

## 7. Acceptance Criteria

- [ ] Rama bisa upload CSV leads dari halaman web tanpa akses server/CLI
- [ ] Dry-run berfungsi: preview jumlah per bulan tanpa menulis data
- [ ] Import nyata menulis data + menampilkan ringkasan akhir
- [ ] Re-run CSV yang sama tidak menggandakan data (order_id → update)
- [ ] Progres terlihat di halaman (log / auto-refresh)
- [ ] Semua route di balik `auth:admin`
- [ ] Logika import pakai command yang sudah ada (tidak duplikasi kode)
- [ ] Fitur B: upload backup.sql → preview tabel → mapping dilaporkan
      jujur (kolom tak dikenal = "dilewati", bukan ditebak)

## 8. Security Minimums

- Semua route `auth:admin`.
- Upload divalidasi: ekstensi `.csv`/`.sql`, ukuran maksimal, header wajib.
- File SQL tidak pernah dieksekusi mentah ke database production —
  hanya **di-parse** (regex/lexer) menjadi data, lalu lewat Eloquent.
- Jangan log isi CSV (berisi PII: nama + no HP).

## 9. Non-Goals (Explicit)

- ❌ Sync otomatis terjadwal (cron) — rutinitas masih manual
- ❌ Integrasi API NocoBase langsung (fitur nanti kalau API-nya sudah jelas)
- ❌ Multi-user / role khusus import
- ❌ Hapus data via web
- ❌ Import orders webhook Scalev (command `scalev:import` sudah ada,
      beda jalur — bisa jadi fitur terpisah)

## 10. Do-Not-Touch Rules

1. Jangan ubah/migrasi tabel yang sudah ada (leads, customers, orders).
2. Jangan tulis ulang `migrate:sheets-batch` — panggil, jangan duplikat.
3. Jangan eksekusi SQL mentah dari file user ke DB production.
4. Jangan tambah dependency baru tanpa approval.
5. Deploy wajib lewat `deploy-crm.sh` (termasuk sync `public/build` ke host).
6. Perubahan file PHP ke container = rebuild image (`--no-cache` kalau
   layer cache menolak — lesson 2026-10-13).
7. Jangan commit ke main sebelum Rama review.

## 11. Urutan Implementasi

```text
PRD ini (approve) → Fitur A (web import CSV) → tes dengan Leads_Okt_2026
→ Rama download backup.sql → lihat isi → Fitur B (convert)
```
