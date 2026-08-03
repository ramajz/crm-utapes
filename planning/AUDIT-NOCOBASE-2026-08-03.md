# Audit Data NocoBase vs Scalev — Juli 2026

> **Tanggal:** 3 Agustus 2026
> **Sumber:** `database/imports/nocobase-orders-juli-2026.csv` (export NocoBase), tabel `orders` (Scalev dari `Webhook_Logs.csv`)
> **Status:** Analisis selesai — NocoBase TIDAK layak untuk metrik closing/revenue

---

## 1. Angka Kunci

| Sumber | Jumlah paid | Total pendapatan | Basis tanggal |
|---|---|---|---|
| NocoBase export | 1,277 | Rp 712,778,287 | Tanggal **transaksi masuk** (created, WIB) |
| Scalev (sumber kebenaran) | **863** | **Rp 480,475,000** | **paid_time** (UTC) |

Catatan: rekap manager NocoBase = 1,074 paid — **tidak konsisten** dengan export-nya sendiri (1,277).

---

## 2. Perbandingan Set Order ID (paid Juli)

| Kategori | Jumlah |
|---|---|
| Ada di NocoBase & Scalev | 759 |
| Hanya di NocoBase | 511 |
| Hanya di Scalev | 104 |

### 2a. "Hanya di NocoBase" (511)

| Breakdown | Jumlah |
|---|---|
| ID numerik (format `2607010698565`) | 424 |
| ID alphanumeric (format Scalev) | 87 |
| — di antaranya: paid_time NULL di Scalev | 65 |
| — di antaranya: payment_status unpaid di Scalev | 69 |
| — di antaranya: paid di Agustus | 22 |

By checkout: online_direct 226, offline_store 185, online_organik 91, online_ads 6, 0/empty 3.
By cabang: 358537632219136 = 361, 358537655287808 = 150.

### 2b. "Hanya di Scalev" (104)

| created_time bulan | Jumlah |
|---|---|
| 2026-07 | 19 |
| 2026-06 | 60 |
| 2026-05 | 14 |
| 2026-04 | 9 |
| 2026-02 | 1 |
| (kosong) | 1 |

By payment_status: paid 94, unpaid 10.
Penyebab: order dibuat bulan sebelumnya tapi **dibayar di Juli** — tidak masuk export NocoBase Juli (basis created).

---

## 3. Temuan

1. **Basis tanggal beda.** NocoBase memfilter `tanggal_transaksi_masuk` (created), Scalev memfilter `paid_time`. Contoh: `260701JACHIGJ` → NocoBase `2026-07-01 05:56:45`, Scalev created `2026-06-30 22:56:44 UTC` (shift +7 jam). Angka 1,277 vs 863 tidak bisa dibandingkan 1:1.

2. **424 order "paid" ber-ID numerik tidak ada di Scalev** (0 dari 425 ketemu di tabel orders). Bukan format ID Scalev → kemungkinan offline store / channel lain tanpa webhook, atau entri manual.

3. **Duplikat ID order** di NocoBase (11 baris dari 4 id):
   - `260719CFLKUUY` ×2
   - `260726PLBCEOD` ×2
   - `260726PODLINZ` ×5 (2 cabang beda, nominal beda)
   - `2607316877538` ×2

4. **Revenue beda definisi.** Di 759 order overlap: NocoBase `total_pendapatan_kotor` = Rp 435,079,761 vs Scalev `gross_revenue` = Rp 421,450,000 → selisih **Rp 13,629,761**.

5. **NocoBase menandai paid lebih awal.** 87 order Scalev-format yang "hanya di NocoBase" → 69 masih unpaid & 65 paid_time NULL di Scalev.

6. **188 `offline_store` ikut terhitung.** Setelah dikeluarkan tetap 1,089 ≠ rekap manager 1,074.

7. **Kualitas data**: 45 baris `checkout=0`, 3 kosong, 1 `payment_status` kosong.

---

## 4. Rekomendasi

- **Scalev tetap sumber kebenaran** closing/revenue (keputusan AGENTS.md). Angka NocoBase tidak dipakai untuk metrik.
- NocoBase hanya untuk lapisan follow-up/lead manual, bukan angka paid/revenue.
- Jika NocoBase tetap dipakai: dedup `id_order`, pisahkan `offline_store`, samakan basis tanggal (paid vs created), dan perbaiki nilai `checkout=0`/kosong.

---

## 5. File Terkait

- `database/imports/nocobase-orders-juli-2026.csv` — export NocoBase
- `database/imports/Webhook_Logs.csv` — log webhook Scalev (mentah)
- `planning/AUDIT-NOCOBASE-2026-08-03.xlsx` — data tabulasi (Excel)
- `planning/AUDIT-NOCOBASE-2026-08-03.pdf` — laporan PDF
