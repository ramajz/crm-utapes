# LAPORAN TASK BACKEND

> Tanggal: 2026-08-07
> Task: import-data-real-juli-2026
> Repo: crm-utapes
> Branch: feat/lead-auto-assign
> Commit: b83100a

1) Deskripsi Perubahan

Mengganti data dummy dengan **data real dari AppScript** (CSV `App-Utapes - Leads_Jul_2026.csv`, 16.212 lead).

- `MigrateFromSheets` diperluas untuk mendukung header CSV AppScript: `Phone (WA)`, `Handler (CS)`, `Financial Status`, `Timestamp`, `Last Update`, `Order ID`, `Customer Name`, `Status FU`, `Total Value`, `Funnel Stage`, `Traffic Type`, `Customer Type`, `Size`, `UTM*`.
- **Alias handler** AppScript → nama resmi sesuai DATA_ACUAN.md (Lana→Hafiz, Kiki ternyata→Kiki, ikiobeng→Oben, Rafli Bahar→Rafli, erpann→Erpan, febrifjr→Babe, Ikbal cjr→Iqbal, Andhi Yanuar→Andhi).
- Flag `--flush` untuk menghapus data lama sebelum import.
- Data dummy (622 lead + 5 handler dummy Siti/Rina/Budi/Dewi/Ahmad) dihapus.
- 16 handler dibuat dari CSV (12 CS utama + 4 minor: Ilhan Manzis, adyaksa, Danil, EMIRZAN). 11 CS utama diaktifkan (Ardha tidak ada di data Juli AppScript).
- 63 lead tanpa handler di-assign otomatis via `leads:assign-unassigned`.

2) Tujuan dan Manfaat

- Database kini berisi data produksi nyata, bukan dummy.
- Validasi angka terhadap DATA_ACUAN.md: funnel (Cold 15.048/Warm 1.022/Hot 142) dan traffic (Organik 6.480/Direct 4.994/Ads 4.738) match referensi ±1-2.
- Import bisa dipakai ulang bulanan dengan `--looker="App-Utapes - Leads_<bulan>.csv" --flush`.

3) Daftar File yang Diubah

- `app/Console/Commands/MigrateFromSheets.php` (header mapping AppScript, alias handler, flag --flush, resolveBranch)
- `database/imports/App-Utapes - Leads_Jul_2026.csv` (data source, 16.217 baris)

4) Snapshot Kode (Before → After)

4.1 Header mapping AppScript

File: `app/Console/Commands/MigrateFromSheets.php`

After:
```
private function normalizeHeader(string $header): string
{
    $key = Str::slug(strtolower(trim($header)), '_');
    return match ($key) {
        'phone_wa', 'phone_id', 'phone_number', 'no_wa', 'nomor' => 'phone',
        'handler_cs', 'cs', 'handler_name', 'pic' => 'handler',
        ...
        'cabang_id', 'branch_id', 'branch', 'cabang' => 'branch_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' => $key,
        default => $key,
    };
}
```

4.2 Flag --flush

File: `app/Console/Commands/MigrateFromSheets.php`

After:
```
if (!$this->dryRun && $this->option('flush')) {
    Lead::query()->forceDelete();
    Customer::query()->delete();
    Handler::query()->delete();
}
```

5) Verifikasi UAT (User Acceptance Test)

| # | Tes | Hasil | Detail |
|---|-----|-------|--------|
| 1 | Dry-run import | ✅ | 16.212 lead terbaca, 0 invalid |
| 2 | Import + flush | ✅ | 16.212 lead, 15.140 customer, 16 handler |
| 3 | Alias handler ter-map | ✅ | Lana→Hafiz (1344), Kiki ternyata→Kiki (1222), dll |
| 4 | Funnel match referensi | ✅ | Cold 15.048 / Warm 1.022 / Hot 142 (±1) |
| 5 | Traffic match referensi | ✅ | Organik 6.480 / Direct 4.994 / Ads 4.738 (±2) |
| 6 | Lead tanpa handler = 63 | ✅ | Semua di-assign ke CS aktif (least_loaded) |
| 7 | `php artisan test` | ✅ | 38 test pass |
