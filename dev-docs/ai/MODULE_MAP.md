# MODULE_MAP

> **Status:** DATA FILE — mapping antara modul bisnis dan komponen kode.
> **Related:** [CURRENT_STATE.md](./CURRENT_STATE.md)

---

## Module to Code Map (Monolith)

| Module | Route File | Controllers | Models | Services | Views |
|--------|-----------|-------------|--------|----------|-------|
| Auth | `routes/auth.php` | `App\Livewire\Pages\Auth\*` | `App\Models\User` | — | `resources/views/livewire/pages/auth/`, `livewire/auth/` |
| Dashboard | `routes/web.php` | `App\Http\Controllers\DashboardController` | `Lead`, `Order`, `Handler` | `App\Services\LeadService` | `resources/views/dashboard.blade.php` |
| Leads (daftar) | `routes/web.php` | `App\Http\Controllers\LeadController` | `Lead`, `Customer`, `Handler`, `Branch` | `App\Services\LeadService` | `resources/views/leads/index.blade.php` |
| Detail Lead + Follow-up | `routes/web.php` | `LeadController@show/updateStatus` | `Lead`, `LeadHistory` | `LeadService::updateStatus` | `resources/views/leads/show.blade.php` + `livewire/update-lead-status.blade.php` |
| Wajib Follow-Up | `routes/web.php` | `LeadController@followUpIndex/toggleFollowUp/completeFollowUp` | `Lead` (follow_up_*) | `LeadService::markFollowUp/completeFollowUp` | `resources/views/leads/follow-up.blade.php` |
| Bulk Reassign CS | `routes/web.php` | `LeadController@bulkReassign` | `Lead`, `Handler` | `LeadService::bulkReassign/reassignHandler` | `resources/views/leads/index.blade.php` |
| Auto-Assign Lead | — (console) | — | `Lead`, `Handler` | `App\Services\LeadAssignmentService` | — |
| Template WhatsApp | `routes/web.php` (via show) | `LeadController@show` | `Lead::renderTemplate()` | — | `resources/views/leads/show.blade.php` |
| Scalev Webhook | `routes/api.php` | `App\Http\Controllers\Api\*` | `Order`, `OrderItem`, `Lead` | `App\Services\ScalevOrderSync` | — |
| Import AppScript | — (console) | — | `Lead`, `Customer`, `Handler`, `Branch` | `App\Console\Commands\MigrateFromSheets` | — |
| Import Scalev | — (console) | — | `Order`, `OrderItem` | `App\Console\Commands\ImportScalevOrders` | — |
| Cabang (Branches) | — | — | `App\Models\Branch` | `Database\Seeders\BranchSeeder` | — |

---

## Shared Infrastructure Map

| Area | Path | Repo | Notes |
|------|------|------|-------|
| Auth middleware | `routes/web.php` (auth + verified) | backend | Breeze session auth |
| Role check | `App\Models\User` (isAdmin/isManager/isCs) | backend | Dipakai di controller/views |
| Audit trail | `App\Models\LeadHistory` | backend | field_changed + old/new value |
| Config | `config/leadassignment.php`, `config/whatsapp_templates.php` | backend | Auto-assign & template WA |
| Database compat | `database/migrations/` | backend | SQLite + PostgreSQL compatible |

---

## Konvensi Penting

- **Auto-funnel**: `status_fu` → `funnel_stage` otomatis via `Lead::mapStatusToFunnel()`.
- **Auto-paid**: status `closing` → `financial_status=paid` otomatis di `LeadService::updateStatus()`.
- **Access control CS**: `LeadController::authorizeLeadAccess()` — CS hanya boleh akses lead `handler_id`-nya sendiri.
- **Audit**: semua perubahan status/notes/size/handler/follow-up dicatat ke `lead_histories`.
