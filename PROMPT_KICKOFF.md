# Perintah Kickoff — Jalankan Agent Lokal untuk Dashboard Evaluasi

> Copy blok di bawah ini ke Claude Code / Cursor / agent lokal apa pun setelah membuka folder `crm-utapes`.
> Aturan lengkapnya sudah otomatis terbaca dari `AGENTS.md`; kalau agent lupa, sebut `DASHBOARD_PRD.md` sebagai rujukan.

---

## Blok perintah (copy mulai dari garis bawah)

```
Kamu bekerja di project CRM-Utapes (Laravel 11 + Livewire + Alpine + Tailwind, lokal saja).

TUGAS: Bangun DASHBOARD EVALUASI lokal yang menampilkan SEMUA metrik laporan bulanan
dari gabungan CSV NocoBase + CSV AppScript, untuk bahan presentasi/evaluasi aku.

WAJIB baca dulu, urut:
1. AGENTS.md (aturan project + scope task ini)
2. DASHBOARD_PRD.md (PRD lengkap: input CSV, checklist metrik, definisi, DoD)
3. DATA_ACUAN.md (definisi metrik yang tidak boleh diubah)

ATURAN KERAS:
- Lokal saja. JANGAN deploy, JANGAN sentuh production/API/webhook/n8n.
- Semua metrik harus pakai definisi di DASHBOARD_PRD.md — jangan bikin definisi sendiri.
- Join NocoBase dan AppScript hanya by order_id; kalau tidak match, tandai status match-nya apa adanya (matched / nb_only / app_only). JANGAN dipaksakan.
- Query harus jalan di SQLite dan PostgreSQL (Eloquent/Query Builder saja).
- Angka harus diverifikasi dengan baseline September 2026: lead 16.192, closing 1.155,
  revenue Rp641.448.250, konversi 7,1%. Setelah itu tes regression Juli (1.150 closing)
  dan Agustus (1.247 closing).
- PII pelanggan jangan tampil apa adanya di mode presentasi (mask nomor HP/nama).

LANGKAH KERJA:
1. Baca 3 file acuan di atas + audit models/migrations yang sudah ada (Lead, Order, Customer).
2. Susun rencana implementasi + estimasi dulu, lalu tunjukkan ke aku SEBELUM mulai coding besar-besaran.
3. Kerjakan bertahap: migrasi + import CSV → perhitungan metrik → UI dashboard → segmentasi + export CSV.
4. Laporkan angka hasil hitung tiap tahap sebelum lanjut ke tahap berikutnya.
5. Jangan commit/push tanpa instruksiku.

CSV input ada di (isi path setelah file tersedia):
- NocoBase: <path>/nocobase_YYYY-MM.csv
- AppScript: <path>/appscript_YYYY-MM.csv

Mulai dari langkah 1.
```

---

## Catatan untuk Rama

- Kalau agent-mu **Claude Code**: file `AGENTS.md` + `CLAUDE.md` otomatis terbaca saat session dibuka. `DASHBOARD_PRD.md` akan diarahkan oleh aturan di `AGENTS.md`.
- Kalau agent-mu **tanpa auto-AGENTS.md** (misal ChatGPT/Cursor custom): selipkan perintah di atas sebagai pesan pertama — sudah cukup karena isinya self-contained.
- Kalau butuh iterasi/fitur tambahan setelah MVP, buat perintah lanjutan:
  ```
  Lanjutkan tugas di DASHBOARD_PRD.md. Checklist yang belum selesai: <sebutkan>.
  Verifikasi ulang angka baseline Sep 2026 sebelum lanjut.
  ```
