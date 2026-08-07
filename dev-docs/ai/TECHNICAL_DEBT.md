# TECHNICAL_DEBT

> **Status:** DATA FILE — tech debt yang diketahui. Update saat ada baru/terselesaikan.

---

## Open Debt

### TDB-001: `DatabaseSeeder.php` masih generate data dummy

- **Area:** Seeder
- **Deskripsi:** `DatabaseSeeder` membuat user/handler/customer/lead dummy (Siti, Rina, dll) yang sudah tidak relevan — data produksi di-import via `migrate:sheets`.
- **Risiko:** `php artisan migrate:fresh --seed` akan menimpa data real dengan dummy.
- **Saran:** Pisahkan seeder data master (branches, users, handlers) dari seeder data dummy, atau ganti `createLeads`/`createCustomers` dengan import.

### TDB-002: `MigrateFromSheets` dipakai untuk 2 format CSV berbeda

- **Area:** Import
- **Deskripsi:** Command ini menangani Looker_Master lama + format AppScript baru (header beda). Ada dua jalur mapping di `normalizeHeader`.
- **Risiko:** Ambigu kalau dua format dipakai bersamaan.
- **Saran:** Buat command import terpisah untuk format AppScript (mis. `leads:import-appscript`).

### TDB-003: View `leads/index.blade.php` dan `leads/follow-up.blade.php` banyak duplikasi

- **Area:** Frontend
- **Deskripsi:** Tabel desktop + card mobile di-copy hampir identik di dua halaman.
- **Saran:** Ekstrak ke Blade component (mis. `<x-lead-table>` / `<x-lead-card>`).

### TDB-004: `total_value` lead tidak terisi (data AppScript)

- **Area:** Data
- **Deskripsi:** 95% lead `total_value=0` karena CSV AppScript kosong. Revenue dari NocoBase.
- **Risiko:** Kalau ada yang menampilkan total_value sebagai revenue, angkanya salah.
- **Saran:** Pertimbangkan backfill dari `orders.gross_revenue` saat sync NocoBase.

## Resolved

- (belum ada)
