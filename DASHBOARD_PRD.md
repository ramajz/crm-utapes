# PRD — Dashboard Evaluasi CRM-Utapes (Lokal Saja)

> **Status:** Siap dikerjakan — 1 Oktober 2026
> **Tujuan:** Dashboard lokal di MacBook untuk **evaluasi/presentasi Rama** — SEMUA metrik dari laporan bulanan harus tampil interaktif.
> **Baca juga:** `AGENTS.md` (aturan umum), `DATA_ACUAN.md` (definisi metrik), `PRD-v2.md` (visi besar — tapi scope di sini JAUH lebih kecil).

---

## 0. SCOPE — BACA DULU, INI PALING PENTING

### Yang INI dikerjakan
- Dashboard lokal (`php artisan serve` / Laravel Herd) yang menampilkan **semua metrik laporan bulanan** secara interaktif.
- **Import CSV** dari dua sumber: NocoBase dan AppScript.
- Pencocokan (join) kedua sumber jadi satu tampilan.
- Filter bulan, filter CS, filter source.
- Ekspor CSV segmentasi (untuk bahan blasting manual di Everpro).

### Yang TIDAK dikerjakan (jangan ditawarkan, jangan dikerjakan)
- ❌ Deploy/live/ke production. Ini lokal saja.
- ❌ Webhook, auto-sync, koneksi API NocoBase, atau n8n.
- ❌ Mengirim pesan/blast dari aplikasi ini.
- ❌ Mengubah data production manapun.
- ❌ Fitur CRM baru (follow-up form, validasi manager) dari PRD-v2.

**Jika user meminta salah satu di atas, tanyakan dulu sebelum melanjutkan.**

---

## 1. Data Input: 2 CSV per Bulan

### A. CSV NocoBase (`nocobase_YYYY-MM.csv`)
Export dari NocoBase `tb_transaksi_order`. Kolom wajib:

| Kolom | Dipakai untuk |
|---|---|
| `id_order` | Join key dengan AppScript |
| `tanggal_transaksi_masuk` | Metrik LEAD |
| `tanggal_payment` | Metrik CLOSING & lag analysis |
| `order_status`, `payment_status` | Filter closing (`success` + `paid`) |
| `checkout` | Filter `!= offline_store`; konversi per source |
| `total_pendapatan_kotor` | Revenue |
| `customer_service_id` / nama CS | Breakdown per CS |
| `filter_produk_cabang_id` | Closing per cabang (Lumajang=358537632219136, Kediri=358537655287808) |
| `payment_method` | Statistik payment |
| `jenis_order` | Single vs multiple |
| `createdById` | Data hygiene (null creator) |

### B. CSV AppScript (`appscript_YYYY-MM.csv`)
Export sheet `Leads_<Bulan>_2026` (Google Sheet ID: `129SM9Nr7-ZCXR6CUX0WjQWPkyb4R7Xp_AlSoLQX_3mM`). Kolom wajib:

| Kolom | Dipakai untuk |
|---|---|
| `Order ID` | Join key dengan NocoBase |
| `Timestamp` | Lead AppScript |
| `Traffic Type` | Analisis traffic (Organik/Direct/Ads) |
| `Funnel Stage` | Funnel Cold/Warm/Hot |
| `Status FU` | Funnel × paid, kategori segmentasi |
| `Notes` | Top notes, kategori segmentasi |
| `Financial Status` | Funnel × paid (paid/unpaid) |
| `Handler (CS)` | Referensi CS di AppScript |
| `Customer Type` | New vs Repeat |

### Aturan Import
1. **Idempoten** — import ulang file yang sama tidak boleh menduplikasi data (upsert by `order_id`).
2. **Validasi sebelum simpan**: tampilkan jumlah baris, baris error, kolom hilang. Gagal total kalau kolom wajib tidak ada.
3. Simpan nama file sumber + tanggal import di tabel `import_logs` (audit trail).

### Join Rules
- Join key utama: **`order_id` format sama di kedua sumber** (contoh: `260703WCUBRJR`).
- Match rate historis ±67% — **itu NORMAL, bukan bug.** Sisanya:
  - ada di NocoBase saja → transaksi di luar Scalev (direct/repeat) → tampilkan sebagai "NocoBase only"
  - ada di AppScript saja → status belum ter-sync → tampilkan sebagai "AppScript only"
- **Jangan pernah memaksakan match** dengan fuzzy/tebakan. Tandai status match apa adanya: `matched` / `nb_only` / `app_only`.

---

## 2. Checklist Metrik WAJIB Semua Tampil di Dashboard

Ambil acuan dari laporan: `/home/rmjz/ai-resume-vault/Projects/CRM-Utapes/Laporan CRM-Utapes September 2026.md`
(bisa dibaca langsung — di Mac mungkin path-nya berbeda; minta user memberi path vault-nya, atau replikasi dari aturan di bawah).

### 2.1 Ringkasan (KPI cards)
- [ ] Lead (NocoBase, basis `tanggal_transaksi_masuk`, exclude `offline_store`)
- [ ] Closing (`success`+`paid`, basis `tanggal_payment`, exclude `offline_store`)
- [ ] Revenue gross (`SUM(total_pendapatan_kotor)`)
- [ ] Konversi = closing ÷ lead (tampil %, perubahan antar bulan dalam **poin persentase**)
- [ ] AOV = revenue ÷ closing
- [ ] Lead AppScript + Paid AppScript
- [ ] Perbandingan vs bulan sebelumnya (delta + %)

### 2.2 Harian
- [ ] 1 baris per hari: tanggal, lead, closing, revenue, konversi
- [ ] Kolom hari (Sen–Min), rata-rata/hari
- [ ] Highlight best/worst day

### 2.3 Mingguan
- [ ] 5 blok (W1: 1–7, W2: 8–14, W3: 15–21, W4: 22–28, W5: 29–akhir): lead, closing, revenue, konversi

### 2.4 Breakdown SELURUH CS (bukan hanya 12 CS utama)
- [ ] Tabel peringkat: CS, lead, closing, konversi, revenue, AOV
- [ ] Delta closing vs bulan sebelumnya per CS
- [ ] Peringkat konversi (efisiensi)
- [ ] Lead per CS (beban kerja)
- [ ] Lead aging per CS: total lead, `new_order` count, % new
- [ ] Royalti = closing × Rp20.000, total + per CS

### 2.5 Traffic (AppScript)
- [ ] Share Organik/Direct/Ads bulan ini
- [ ] Tren 3 bulan terakhir (share per sumber)
- [ ] Konversi per source dari NocoBase (`online_organik/direct/ads` → lead, close, konv%, revenue)

### 2.6 Funnel & Follow-up (AppScript)
- [ ] Funnel stage: Cold/Warm/Hot + persentase
- [ ] **Funnel stage × Financial Status** (paid vs unpaid per stage)
- [ ] Distribusi Status FU (New, Chatted, Replied, ..., %)
- [ ] Top 10 Notes

### 2.7 Lead Aging & Lag
- [ ] Total lead masih `new_order` + persentase
- [ ] Lag analysis (lead masuk → tanggal payment), bucket: 0 hari / 1–6 / 7–30 / >30 hari

### 2.8 Rekonsiliasi
- [ ] Match Order ID: matched / NocoBase only / AppScript only (dengan %)
- [ ] Data hygiene: checkout null, checkout="0", closing tanpa cabang, closing tanpa creator
- [ ] Closing per cabang: Lumajang / Kediri / Tanpa Cabang
- [ ] Payment method (BCA/Mandiri/Shopee/...) + order type (single/multiple)

### 2.9 Segmentasi (untuk bahan blasting)
Enam kategori yang sudah disepakati user:
1. `rejected` → `status_fu = rejected`
2. `size` → notes/size menunjukkan ukuran
3. `brand` → notes menyebut brand (nike, adidas, nb, salomon, asics, ...)
4. `janji_tf` → `status_fu = promise_transfer`
5. `nunggu_gajian` → `status_fu = nunggu_gajian`
6. `paid` → `financial_status = paid`

- [ ] Setiap kategori: jumlah record, preview, tombol **Export CSV** (kolom: `phone`, `name`, `category`, `notes`, `campaign_tag`)
- [ ] Selalu **exclude**: nomor kosong/invalid, `rejected` (kecuali kategorinya emang rejected untuk analisis), opt-out bila ada kolomnya
- [ ] **Satu lead boleh masuk beberapa kategori** — tampilkan sebagai tag, jangan dipaksa satu-satu; tapi ekspor per kategori tetap file terpisah

---

## 3. Ketentuan Teknis (mengikuti AGENTS.md)

- Laravel 11 + Livewire + Alpine.js + Tailwind. **Tanpa jQuery/Bootstrap.**
- **Eloquent/Query Builder saja.** `DB::raw()` hanya untuk fungsi sederhana (`count`, `SUM`, `CASE WHEN`). DILARANG `DB::select()` dan sintaks PostgreSQL spesifik — semua query harus jalan di **SQLite DAN PostgreSQL**.
- Semua query metrik harus berdasarkan **bulan yang dipilih** (date range picker), jangan hardcode bulan.
- Pisahkan logika: `app/Services/ReportMetricService.php` untuk perhitungan, Controller/Livewire hanya ambil hasil → memudahkan angka dicek satu-satu.

---

## 4. Definisi Definitif (jangan diubah sendiri)

| Metrik | Definisi | Sumber |
|---|---|---|
| LEAD | `tanggal_transaksi_masuk`, exclude `checkout=offline_store` | NocoBase |
| CLOSING | `order_status=success` AND `payment_status=paid`, basis `tanggal_payment`, exclude `offline_store` | NocoBase |
| REVENUE | `SUM(total_pendapatan_kotor)` baris closing | NocoBase |
| KONVERSI | closing ÷ lead | NocoBase |
| AOV | revenue ÷ closing | Turunan |
| TRAFFIC | `Traffic Type` | AppScript |
| FUNNEL | `Funnel Stage` + `Status FU` | AppScript |
| ROYALTI | closing × 20.000 | Turunan |
| Satuan perubahan % antar-persentase | **poin persentase** (6,9% → 7,1% = +0,2 poin) | Konvensi |

---

## 5. Definition of Done (DoD)

- [ ] `php artisan migrate` sukses di SQLite
- [ ] Import kedua CSV sukses, jumlah baris cocok dengan file
- [ ] Seluruh checklist §2 tampil di UI, dan **angkanya sama dengan laporan bulanan** untuk bulan September 2026 (angka acuan: lead 16.192 / closing 1.155 / revenue Rp641.448.250 — verifikasi ±0, kecuali data CSV sudah berubah sejak ekstrak 1 Okt)
- [ ] Filter bulan mengganti seluruh angka (tes Juli: closing 1.150, Agustus: 1.247)
- [ ] Export CSV segmentasi menghasilkan file valid yang bisa langsung ditempel ke Everpro
- [ ] Tidak ada koneksi jaringan ke API eksternal saat dashboard berjalan

---

## 6. Urutan Pengerjaan (disarankan)

1. Migrasi tabel `imports` + kolom pendukung (match status, category tag)
2. Service import CSV NocoBase → tampilkan hasil validasi
3. Service import CSV AppScript → hasil validasi
4. `ReportMetricService` — per metrik dari §2.1 s/d §2.8, verifikasi angka vs laporan Sep 2026
5. UI dashboard (KPI cards + tabel + chart pakai Alpine.js / Chart.js ringan)
6. Segmentasi + export CSV (§2.9)
7. Tes bulan Juli & Agustus (regression: angka harus cocok laporan masing-masing)

**Setiap selesai satu bagian, laporkan angka hasil hitungannya** — jangan langsung lanjut sebelum angka cocok.
