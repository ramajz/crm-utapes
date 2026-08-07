# LAPORAN TASK BACKEND

> Tanggal: 2026-08-07
> Task: fitur-wajib-followup-dan-bulk-reassign-cs
> Repo: crm-utapes
> Branch: feat/lead-auto-assign
> Commit: d208cf7

1) Deskripsi Perubahan

Menambahkan fitur **Wajib Follow-Up** dan **Bulk Reassign CS**, plus fondasi struktur cabang (branch) yang sebelumnya tidak ada di database.

- **Wajib Follow-Up**: Manager/Admin bisa menandai lead sebagai "wajib follow-up" secara manual (toggle). Lead yang ditandai masuk ke halaman khusus "Wajib FU" yang hanya menampilkan lead tersebut. CS hanya melihat lead wajib FU miliknya sendiri dan bisa menandai "Selesai" (done).
- **Bulk Reassign CS**: Manager/Admin bisa memilih beberapa lead sekaligus (checkbox) lalu memindahkannya ke CS lain. Status wajib follow-up ikut terbawa.
- **Fondasi Cabang**: Tabel `branches` baru (Lumajang, Kediri) dengan mapping `nocobase_id`. Kolom `branch_id` ditambahkan ke `leads`, `orders`, dan `handlers` (CS per cabang). Import `cabang_id` dari CSV NocoBase didukung di `MigrateFromSheets`.
- Semua perubahan (tandai wajib FU, selesai, reassign) tercatat di tabel `lead_histories` (audit trail).

2) Tujuan dan Manfaat

- Manager bisa memprioritaskan lead yang wajib di-follow-up oleh CS, sehingga follow-up tidak terlewat.
- CS punya satu menu khusus berisi daftar tugas follow-up yang harus dikerjakan.
- Manager bisa memperbaiki alokasi lead antar CS secara massal tanpa edit satu per satu.
- Struktur cabang menyiapkan database untuk data per cabang (Lumajang/Kediri) dari NocoBase.

3) Daftar File yang Diubah

- `database/migrations/2026_08_07_000001_create_branches_table.php` (baru)
- `database/migrations/2026_08_07_000002_add_follow_up_and_branch_fields_to_leads_table.php` (baru)
- `database/migrations/2026_08_07_000003_add_branch_to_orders_and_handlers_table.php` (baru)
- `app/Models/Branch.php` (baru)
- `app/Models/Lead.php`
- `app/Models/Order.php`
- `app/Models/Handler.php`
- `app/Services/LeadService.php` (markFollowUp, completeFollowUp, reassignHandler, bulkReassign)
- `app/Http/Controllers/LeadController.php` (followUpIndex, toggleFollowUp, completeFollowUp, bulkReassign)
- `routes/web.php`
- `resources/views/leads/follow-up.blade.php` (baru)
- `resources/views/leads/index.blade.php` (checkbox bulk reassign + kolom cabang)
- `resources/views/livewire/layout/navigation.blade.php` (menu Wajib FU)
- `database/seeders/BranchSeeder.php` (baru)
- `app/Console/Commands/MigrateFromSheets.php` (support cabang_id)
- `tests/Feature/FollowUpReassignTest.php` (baru, 10 test)

4) Snapshot Kode (Before → After)

4.1 Kolom baru di tabel leads

File: `database/migrations/2026_08_07_000002_add_follow_up_and_branch_fields_to_leads_table.php`

After:
```
Schema::table('leads', function (Blueprint $table) {
    $table->foreignId('branch_id')->nullable()->after('handler_id')->constrained()->nullOnDelete();
    $table->boolean('follow_up_required')->default(false)->after('last_update_at');
    $table->string('follow_up_status', 10)->nullable()->after('follow_up_required');
    $table->timestamp('follow_up_completed_at')->nullable()->after('follow_up_status');
    ...
});
```

4.2 Service method markFollowUp

File: `app/Services/LeadService.php`

After:
```
public function markFollowUp(Lead $lead, bool $required, int $userId): Lead
{
    return DB::transaction(function () use ($lead, $required, $userId) {
        $oldValue = $lead->follow_up_required ? '1' : '0';
        $newValue = $required ? '1' : '0';
        $lead->update([
            'follow_up_required' => $required,
            'follow_up_status' => $required ? 'pending' : null,
            'follow_up_completed_at' => $required ? $lead->follow_up_completed_at : null,
        ]);
        if ($oldValue !== $newValue) {
            LeadHistory::create([... 'field_changed' => 'follow_up_required' ...]);
        }
        return $lead->fresh();
    });
}
```

5) Verifikasi UAT (User Acceptance Test)

| # | Tes | Hasil | Detail |
|---|-----|-------|--------|
| 1 | `php artisan migrate` | ✅ | 3 migration sukses |
| 2 | `php artisan db:seed --class=BranchSeeder` | ✅ | Lumajang + Kediri ter-seed |
| 3 | `php artisan test --filter=FollowUpReassignTest` | ✅ | 10 test pass (30 assertions) |
| 4 | `php artisan test` (full suite) | ✅ | 52 test pass (150 assertions), tanpa regresi |
| 5 | Manager tandai lead wajib FU | ✅ | follow_up_required=true, status=pending, history tercatat |
| 6 | CS lihat lead wajib FU sendiri | ✅ | Hanya lead handler-nya yang tampil |
| 7 | CS tandai selesai | ✅ | status=done, follow_up_completed_at terisi |
| 8 | Manager bulk reassign | ✅ | handler_id berubah, status FU ikut, history tercatat |
| 9 | CS tidak bisa toggle/reassign | ✅ | 403 forbidden |
| 10 | Filter by branch di halaman follow-up | ✅ | Hanya lead cabang terpilih yang tampil |
