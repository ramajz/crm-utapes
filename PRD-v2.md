# PRD CRM-Utapes v2.0

**Status:** Draft (2026-08-03)
**Penulis:** Rama (founder) + AI Agent
**Referensi:** DATA_ACUAN.md (kesepakatan data acuan)

---

## 1. Visi & Masalah

### Masalah (teridentifikasi dari rekonsiliasi data 2026-08-03):
1. **Data lahir di 3 tempat dengan 3 definisi berbeda** — lead di Scalev (LP Meta Ads), follow-up di AppScript, order/payment di NocoBase. Tidak ada satu sistem yang punya data lengkap satu order.
2. **Order manual CS = data kualitas hilang** — chat masuk ke CS tapi gak ada di database Scalev → CS bikin order manual di NocoBase → order itu gak punya funnel, traffic, notes → analisis timpang.
3. **Revenue tidak konsisten antar sumber** — CSV AppScript 97% Total Value kosong, laporan manager beda (678 vs 741), hanya NocoBase yang benar.
4. **Angka beda tiap bulan bikin bingung** — selisih NocoBase vs AppScript tidak konsisten (Juni -25, Juli +341).
5. **Laporan bulanan manual** — export CSV + join + susun Canva tiap bulan (membuang waktu).

### Visi:
**CRM-Utapes menjadi single source of truth untuk data CRM** (lead lifecycle, follow-up, validasi, laporan). BUKAN ERP — transaksi & dokumen tetap di NocoBase.

---

## 2. Arsitektur

```
MASUK:                           PROSES:                      KELUAR:
┌─────────────┐
│ Scalev (LP) │──webhook──┐
└─────────────┘           │
┌─────────────┐           ▼    ┌──────────────────┐      ┌──────────────┐
│ Chatbot WABA│──lead───►│    │   CRM-UTAPES      │──►  │ Laporan      │
│ (24/7)      │          │    │  (single source)  │      │ bulanan auto │
└─────────────┘           │    │                  │      └──────────────┘
┌─────────────┐           │    │ - orders + leads │◄─┐  ┌──────────────┐
│ NocoBase    │──sync───►│    │ - follow-up CS   │  │  │ Manager      │
│ (ERP/stok)  │◄─sync────┘    │ - validasi       │  │  │ validasi     │
└─────────────┘   balik       └──────────────────┘  │  └──────────────┘
                              ▲                     │
                              │   ┌──────────┐      │
                              └───│ n8n      │◄─────┘
                                  │ (pipeline│
                                  │ + fallback│
                                  └──────────┘
```

### Prinsip pembagian:
| Sistem | Peran | Detail |
|--------|-------|--------|
| **NocoBase** | ERP / tempat order lahir | Stok, order (termasuk manual CS dengan upload foto produk + bukti transfer), payment |
| **Scalev** | Landing page / lead source | Meta Ads LP, webhook ke CRM |
| **CRM-Utapes** | CRM / single source analisis | Lead lifecycle, follow-up CS, funnel, notes, validasi manager, laporan |
| **n8n** | Orchestrator + fallback | Pipeline webhook, sync NocoBase 2 arah, restore data jika salah entri |

### Batasan (BUKAN scope):
- ❌ Bukan ERP — tidak ada fitur upload foto produk / bukti transfer di CRM-Utapes
- ❌ Bukan pengganti NocoBase — order manual CS TETAP dibuat di NocoBase
- ✅ CRM-Utapes sync data NocoBase, CS cuma isi funnel/notes/status

---

## 3. Modul Inti

### M1. Ingest (pipeline data)
- Webhook Scalev → lead masuk otomatis (via n8n)
- Sync NocoBase ↔ CRM-Utapes (2 arah, via n8n): order, payment_status, order_status, produk
- Dedup by Order ID (id_order), kolom `source`: `scalev` / `waba` / `manual` / `nocobase`
- **Deteksi order manual**: NocoBase ada tapi tidak match Scalev/AppScript → tandai `source=manual` (kandidat perlu validasi manager)

### M2. Lead & Order Management
- Satu tabel: leads + orders (join key: Order ID)
- Kolom: id_order, customer (nama, WA), produk, harga, checkout, traffic source, UTM, funnel stage, status FU, notes, handler CS, tanggal masuk, tanggal payment, payment_status, order_status, revenue, source
- CS bisa buat order manual → tetap via NocoBase, CRM tampilkan hasil sync

### M3. Follow-up CS (pengganti AppScript)
- Form follow-up langsung di CRM-Utapes: Funnel Stage (Cold/Warm/Hot), Status FU (New/Chatted/Replied/Interested/Closing/Rejected/Nunggu Gajian/Promise Transfer), Notes
- Auto-assign lead ke CS (rotasi / per cabang)
- **AppScript DIMATIKAN** setelah CS pindah penuh ke CRM-Utapes

### M4. Chatbot WABA (24/7 qualify lead)
- Flow: Welcome → Pilih Ukuran → Pilih Brand → Pilih Tipe → Katalog → Konfirmasi → `ready_to_close` → CS Handoff
- Template 9 flow (draft: ~/utapes-waba-templates.md, perlu review developer WABA)
- Status `ready_to_close` (BUKAN "closed" — customer belum bayar, cuma qualified)
- Integrasi: chatbot → n8n webhook → CRM-Utapes (lead distribution, CS notification)
- **Strategi blasting** (template broadcast — dibahas terpisah, bukan di PRD ini)

### M5. Validasi Manager
- Manager cabang approve/reject closing per CS
- Cross-check angka: closing CRM vs closing NocoBase vs closing manager
- Rekonsiliasi otomatis (lihat M7)

### M6. Report Generator (bulanan)
- Formulasi sesuai DATA_ACUAN.md:
  - LEAD = order masuk (tanggal_transaksi_masuk), excl offline_store
  - CLOSING = success + paid, basis tanggal_payment, excl offline_store
  - REVENUE = total_pendapatan_kotor closing (semua CS)
  - PER CS = 12 CS utama
  - TRAFFIC/FUNNEL/NOTES = AppScript (sampai migrasi selesai, lalu dari CRM langsung)
- Output: executive summary, per CS, traffic source, funnel, kesimpulan + rekomendasi
- Export: konten Canva + CSV data chart

### M7. Data Reconciliation
- Laporan selisih otomatis: NocoBase vs CRM vs manager
- Flag anomali: funnel Warm < Cold, closing tanpa handler, selisih tidak konsisten antar bulan
- Audit trail semua perubahan (n8n fallback)

---

## 4. Role & Permission

| Role | Akses |
|------|-------|
| **CS** | Lihat lead yang di-assign, isi follow-up (funnel/notes/status), lihat data customer |
| **Manager Cabang** | Validasi closing per CS (approve/reject), lihat data cabangnya, laporan cabang |
| **Owner** | Lihat semua, laporan semua cabang, tidak bisa edit data transaksi |
| **Admin CRM** | Kelola user, CS, produk mapping, setting sinkronisasi, trigger sync manual |
| **Super Admin** | Semua akses + konfigurasi sistem, akses n8n, fallback/restore data |

---

## 5. Tech Stack (existing)
- Laravel 11 + Livewire + Alpine.js + Tailwind CSS (sudah deployed di Coolify)
- Database: NeonDB PostgreSQL (production), SQLite (local dev)
- n8n: orchestrator pipeline + fallback
- NocoBase: ERP backend (external, API access)

---

## 6. Definisi Data (acuan tetap — dari DATA_ACUAN.md)

| Metrik | Definisi | Sumber |
|--------|----------|--------|
| LEAD | Order masuk (tanggal_transaksi_masuk), excl `checkout=offline_store`, semua cabang | NocoBase |
| CLOSING | `order_status=success` AND `payment_status=paid`, basis `tanggal_payment`, excl offline_store | NocoBase |
| KONVERSI | closing ÷ lead | NocoBase |
| REVENUE | sum(total_pendapatan_kotor) dari closing | NocoBase |
| TRAFFIC | % Traffic Type (Organik/Direct/Ads) | AppScript → CRM |
| FUNNEL | Cold/Warm/Hot + konversi per stage | AppScript → CRM |
| PER CS | Closing per 12 CS utama | Manager (validasi) |

### 12 CS utama:
Hafiz(5), Farhan(8), Kiki(38), Ardha(9), Yusron(24), Erpan(4), Oben(25), Andhi(11), Peler/iwan(18), Iqbal(19), Babe/febri(7), Dimas(20)

### Alias CS (AppScript → resmi):
Lana=Hafiz, Rafli Bahar=Rafli, ikiobeng=Oben, febrifjr=Babe, erpann=Erpan, Ikbal cjr=Iqbal, Kiki ternyata=Kiki, Andhi Yanuar=Andhi

---

## 7. Roadmap

### Fase 1 — Fondasi Data (prioritas tertinggi)
- [ ] Script join_laporan.py sudah ada (validated Juni & Juli) — jadi dasar
- [ ] Migrasi script ke dalam Laravel (Report Generator module)
- [ ] Sync NocoBase → CRM (satu arah dulu) via n8n cron
- [ ] Tabel orders + leads + kolom source + dedup

### Fase 2 — Follow-up CS
- [ ] Form follow-up CS (funnel, notes, status) di Livewire
- [ ] Auto-assign lead
- [ ] CS pindah dari AppScript → CRM (AppScript dimatikan)

### Fase 3 — Validasi & Chatbot
- [ ] Validasi manager (approve/reject closing)
- [ ] Integrasi Chatbot WABA → n8n → CRM
- [ ] Strategi blasting (template broadcast — diskusi terpisah)

### Fase 4 — Sync 2 arah & Reconciliation
- [ ] Sync CRM → NocoBase (order manual tetap di NocoBase, status follow-up dari CRM)
- [ ] Reconciliation otomatis + alert anomali
- [ ] n8n fallback/restore penuh

---

## 8. Open Questions
1. Detail strategi blasting / template broadcast (diskusi terpisah)
2. Flow Chatbot WABA final (review developer WABA dulu)
3. Format output laporan: Canva manual atau generate PDF otomatis?
4. Kebutuhan real-time (lead hari ini) — saat ini out of scope (laporan bulanan aja)
