# 📊 DATA ACUAN CRM-UTAPES — Panduan untuk AI Agents

> **BACA INI DULU sebelum mengerjakan apa pun terkait data/laporan CRM-Utapes.**
> Dokumen ini adalah kesepakatan data yang berlaku permanen (disepakati 2026-08-03).
> Update hanya jika user secara eksplisit menyetujui perubahan.

---

## 1. Konteks Bisnis

**Utapes = bisnis sepatu** (sneakers). Memiliki:
- 11-12 CS (hybrid, jam fleksibel, insentif Rp20rb/closing)
- 2 cabang: Lumajang & Kediri (semua laporan pakai SEMUA cabang)
- 3 kanal lead: Meta Ads (landing page), Organik socmed (link bio/story), Direct WA ke CS
- Volume: 10-17 ribu lead/bulan, closing rate ~4-7%
- Customer behaviour unik: lead bisa masuk bulan X tapi baru bayar bulan Y (nunggu gajian, promise transfer)

---

## 2. Tiga Sumber Data (PENTING — ini akar semua kebingungan)

| Sistem | Isi | Peran | Fungsi |
|--------|-----|-------|--------|
| **NocoBase** | SEMUA transaksi order (67K+ records) | **FAKTA TRANSAKSI** — angka final | ERP: stok, order, payment, revenue |
| **AppScript (CSV)** | Hanya order dari webhook Scalev (Meta Ads LP) | **KONTEKS & KUALITAS** | Funnel stage, notes CS, traffic, UTM |
| **Laporan Manager** | Validasi manual per cabang | **VALIDASI PER CS** | Cross-check realita per CS |

**Aturan emas:**
1. **Satu metrik = satu sumber.** JANGAN pernah mencampur angka antar sumber untuk metrik yang sama.
2. NocoBase = untuk "BERAPA" (volume, closing, revenue)
3. AppScript = untuk "BAGAIMANA" (kualitas lead, funnel, traffic source, notes)
4. Laporan manager = untuk "SIAPA" (validasi closing per CS)
5. **Kalau angka beda antar sumber → itu BUKAN error, itu INFORMASI** (selisih = transaksi di luar Scalev, misal direct WA yang gak lewat LP). Jelaskan di laporan sebagai catatan, jangan paksakan sama.

### Kenapa AppScript selalu lebih kecil dari NocoBase?
AppScript = webhook murni dari Scalev = **hanya lead yang masuk lewat Meta Ads landing page**.
NocoBase = semua transaksi (termasuk direct WA ke CS, offline, dll).
Contoh Juli: NocoBase 1.150 vs AppScript 809 → selisih 341 = transaksi di luar Scalev.

---

## 3. Definisi Metrik (KESEPAKATAN — jangan diubah)

| Metrik | Definisi | Sumber |
|--------|----------|--------|
| **LEAD** | Order masuk bulan itu (`tanggal_transaksi_masuk` $includes "2026-MM-"), **exclude** `checkout=offline_store`, semua cabang | NocoBase |
| **CLOSING** | `order_status=success` **AND** `payment_status=paid`, basis **`tanggal_payment`** (bukan tanggal masuk — karena lead lama bisa bayar belakangan), **exclude** `checkout=offline_store`, semua cabang | NocoBase |
| **KONVERSI** | closing ÷ lead | NocoBase |
| **REVENUE** | sum `total_pendapatan_kotor` dari closing | NocoBase |
| **TRAFFIC SOURCE** | % distribusi Traffic Type (Organik/Direct/Ads) | AppScript |
| **FUNNEL** | Cold/Warm/Hot distribution + konversi per stage | AppScript |
| **NOTES/STATUS FU** | Distribusi Status FU + keyword notes | AppScript |
| **PERFORMA CS** | Closing per CS | Laporan Manager (validasi) |

### Validasi definisi ini (bukti benar):
- Closing Mei 2026 (NocoBase) = **624** → MATCH PERSIS dengan laporan bulanan yang sudah ada
- Per-CS Juli: selisih cuma 2 (Dimas 64 vs 62, dugaan lead Danil diakuisisi Dimas)

---

## 4. Join Key (SOLUSI UTAMA)

**Order ID di AppScript = `id_order` di NocoBase untuk order yang berasal dari Scalev (sudah diverifikasi 5/5 sample match).**

Contoh: `260701MEIWJKJ` ada di kedua sistem.

**⚠️ TAPI tidak semua order match — dan itu NORMAL:**

| Kategori | Di AppScript? | Di NocoBase? | Join match? |
|----------|---------------|--------------|-------------|
| Order dari Scalev (LP Meta Ads) | ✅ | ✅ (id sama) | ✅ MATCH |
| Order dibuat MANUAL oleh CS (chat masuk tapi gak ada lead di DB → CS create order di NocoBase pakai format ID NocoBase) | ❌ | ✅ | ❌ NO MATCH |
| Offline store | ❌ | ✅ | ❌ (sudah di-exclude) |

**Use case manual CS (klarifikasi 2026-08-03):** chat masuk ke CS tapi lead-nya gak ada di database Scalev → CS membuat order manual di NocoBase. Order ini TIDAK akan ketemu di AppScript → join NO MATCH. **Ini bukan error, ini informasi**: selisih join = transaksi di luar Scalev (manual CS / direct WA / offline).

**Join result = cara otomatis deteksi order manual:** NocoBase ada + AppScript tidak ada = kemungkinan besar order manual CS (perlu divalidasi manager).

Artinya: NocoBase dan AppScript bisa **di-join jadi satu tabel per order** untuk order yang dari Scalev:

```
Order ID (join key)
├── NocoBase: order_status, payment_status, checkout, revenue, tanggal_payment, customer_service_id
└── AppScript: Funnel Stage, Status FU, Notes, Traffic Type, UTM, Handler (CS)
```

**Alur laporan bulanan yang benar:**
1. Export NocoBase order bulan X (filter tanggal_transaksi_masuk, semua cabang)
2. Export CSV AppScript bulan X
3. Join by Order ID → 1 tabel master lengkap
4. Hitung semua metrik dari tabel ini — konsisten selamanya

---

## 5. Nama CS & Alias (PENTING untuk join)

### Alias nama CS di AppScript → nama resmi:
| AppScript | NocoBase | ID |
|-----------|----------|----|
| Lana | Hafiz | 5 |
| Rafli Bahar | Rafli | (user id beda) |
| ikiobeng | Oben / Obeng | 25 |
| febrifjr | Babe (febri) | 7 |
| erpann | Erpan | 4 |
| Ikbal cjr | Iqbal | 19 |
| Kiki ternyata | Kiki (kiki_maulana) | 38 |
| Andhi Yanuar | Andhi | 11 |
| Peler | iwan | 18 |

### 12 CS utama (pakai ini untuk breakdown performa):
Hafiz(5), Farhan(8), Kiki(38), Ardha(9), Yusron(24), Erpan(4), Oben(25), Andhi(11), Peler/iwan(18), Iqbal(19), Babe/febri(7), Dimas(20)

### Yang DIABAIKAN (bukan CS tim utama):
- ilhanmanzis (CS baru), rendi (CS baru), maya (CS baru), danil (keluar), offlinestore-kediri (akun offline), Yusril/emirzan (bukan CS)

### Nama non-standar di AppScript yang perlu diperhatikan:
- `adyaksa rendian ramadhan baskara` = rendi (CS baru)
- `Ilhan Manzis` = ilhanmanzis (CS baru)
- Handler kosong `""` di AppScript = lead belum di-assign CS (ini masalah — Juli ada 61 paid tanpa handler)

---

## 6. Angka Referensi (Juli 2026 — untuk validasi hasil)

| Metrik | Juni | Juli |
|--------|------|------|
| Lead (NocoBase) | 11.153 | 17.538 |
| Closing (NocoBase) | 741 | 1.150 |
| Konversi | 6,64% | 6,56% |
| Revenue | Rp 418,8 jt | Rp 654,3 jt |
| Avg per order | Rp 565rb | Rp 569rb |
| Leads AppScript | 10.847 | 16.210 |
| Paid AppScript | 524 | 809 |
| Closing 12 CS utama | 660 | 1.072 |

### Closing per CS (Juli, NocoBase 12 CS):
Hafiz 133 | Farhan 130 | Kiki 107 | Ardha 99 | Yusron 88 | Erpan 85 | Oben 83 | Andhi 81 | Peler 74 | Iqbal 67 | Babe 63 | Dimas 62

### Funnel Juli (AppScript):
Cold 15.046 (92,8%) | Warm 1.023 (6,3%) | Hot 141 (0,9%)
Konversi: Hot 85,1% | Cold 4,4% | Warm 2,3%
Status FU: Closing 117 = 100% paid | Nunggu Gajian 20 | Promise Transfer 4

### Traffic Juli (AppScript):
Organik 6.480 (40,0%) | Direct 4.994 (30,8%) | Ads 4.736 (29,2%)

---

## 7. Pitfalls yang Sudah Diketahui

7. **`tanggal_payment` jam selalu 00:00:00** — cuma tanggal yang akurat, jam default. Untuk laporan bulanan tanggal cukup.
8. **Order `createdById=null` = lead LAMA (Januari–Mei) yang baru closing di bulan tsb** — bukan error, bukan order_id null (id_order tetap normal). Order dibuat era lama/import tanpa tracking pembuat, lalu di-sweep jadi success+paid oleh FAJAR (user id 41, admin/backend, bukan CS utama). Pola ini bukti "lag behaviour" customer Utapes (bisa 1-6 bulan nunggu bayar) → closing bulan X selalu mengandung order dari bulan² sebelumnya. Konsekuensi: konversi (closing/lead) sedikit bias, tapi basis tanggal_payment tetap benar utk revenue real.
2. **`Total Value` di CSV AppScript 97% kosong** — JANGAN pakai untuk revenue. Revenue selalu dari NocoBase.
3. **NocoBase `checkout` field**: online_organik, online_direct, online_ads, offline_store, "0" (numeric), null. Exclude offline_store untuk lead/closing. "0" = legacy.
4. **Funnel Warm konversi (2,3%) lebih rendah dari Cold (4,4%)** — anomali yang perlu diwaspadai, jangan kaget.
5. **Laporan manager Juni = 678 paid** itu bulan JUNI (bukan Juli) — jangan ketuker.
6. **Selisih NocoBase vs AppScript tidak konsisten** antar bulan (Juni: -25, Juli: +341) — selalu cek polanya, jangan asumsi.
7. NocoBase API: login POST /api/auth:signIn, GET + URL encode filter, operator $includes/$ne/$notEmpty/$empty.

---

## 8. Konvensi Laporan Bulanan

Format laporan (Canva presentation) konsisten:
1. **Cover** — bulan + nama
2. **Executive Summary** — lead, closing, konversi, growth MoM, revenue
3. **Performa CS** — chart per CS (12 CS utama)
4. **Traffic Source** — pie chart (Organik/Ads/Direct)
5. **Funnel Stage** — Cold/Warm/Hot + konversi
6. **Kesimpulan + Strategic Recommendations** — insight + action items

Growth dihitung MoM (bulan ini vs bulan sebelumnya) dengan sumber yang SAMA.
