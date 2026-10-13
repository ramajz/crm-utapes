<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Handler;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import Looker_Master + Customer_Master ke DB dengan BATCH INSERT.
 *
 * Beda dengan migrate:sheets (row-by-row, ~9 jam karena RTT NeonDB-US):
 * command ini preload semua map sekali, lalu bulk insert per bulan.
 * Target: 85K leads dalam ~10-15 menit.
 *
 * Pakai:
 *   php artisan migrate:sheets-batch --dry-run              # preview semua bulan
 *   php artisan migrate:sheets-batch --month=2026-07        # import Juli saja
 *   php artisan migrate:sheets-batch --all                  # semua bulan (sekali jalan)
 */
class MigrateFromSheetsBatch extends Command
{
    protected $signature = 'migrate:sheets-batch
        {--path=database/imports : Direktori file CSV}
        {--looker=Looker_Master.csv : File leads}
        {--customers=Customer_Master.csv : File customers}
        {--month= : Import per bulan saja (format YYYY-MM) — contoh 2026-07}
        {--all : Import semua bulan (default: hanya bulan pertama yang terdeteksi)}
        {--dry-run : Preview tanpa menulis}';

    protected $description = 'Import leads + customers dari Sheets dengan batch insert (cepat, per bulan)';

    private bool $dryRun = false;
    private int $batchSize = 1000;

    // Preloaded maps (satu kali query, bukan per-row)
    /** @var array<string,int> name => id */
    private array $handlerMap = [];
    /** @var array<string,int> nocobase_id => id */
    private array $branchMap = [];
    /** @var array<string,int> phone => id */
    private array $customerMap = [];
    /** @var array<string,true> order_id => exists */
    private array $existingOrderIds = [];

    private int $skipped = 0;
    private int $leadsInserted = 0;
    private int $leadsUpdated = 0;
    private int $customersCreated = 0;

    /** Alias nama CS AppScript -> canonical */
    private array $handlerAliases = [
        'lana' => 'Hafiz', 'rafli bahar' => 'Rafli', 'ikiobeng' => 'Oben',
        'febrifjr' => 'Babe', 'erpann' => 'Erpan', 'ikbal cjr' => 'Iqbal',
        'kiki ternyata' => 'Kiki', 'andhi yanuar' => 'Andhi',
    ];

    public function handle(): int
    {
        // CSV 85K baris + Carbon object meledakin memory_limit default 128M
        ini_set('memory_limit', '768M');
        set_time_limit(0);

        $path = rtrim($this->option('path'), '/');
        $lookerPath = $path . '/' . $this->option('looker');
        $customersPath = $path . '/' . $this->option('customers');

        if (! is_file($lookerPath)) {
            $this->error("File leads tidak ditemukan: {$lookerPath}");
            return self::FAILURE;
        }

        $this->dryRun = (bool) $this->option('dry-run');
        if ($this->dryRun) {
            $this->warn('=== DRY-RUN: tidak ada data yang ditulis ===');
        }

        $start = microtime(true);

        $this->info('[1/4] Load map (handler, branch, customer, order_id)...');
        $this->loadMaps();

        $this->info('[2/4] Baca CSV + group per bulan...');
        $byMonth = $this->groupCsvByMonth($lookerPath);
        $months = array_keys($byMonth);
        sort($months);
        $this->info('Bulan terdeteksi: ' . implode(', ', $months));

        $target = $this->option('month')
            ? [$this->option('month')]
            : ($this->option('all') ? $months : [$months[0] ?? null]);
        $target = array_values(array_filter($target));
        if ($this->option('month') && ! in_array($this->option('month'), $months, true)) {
            $this->error("Bulan {$this->option('month')} tidak ada di CSV. Yang tersedia: " . implode(', ', $months));
            return self::FAILURE;
        }

        $this->info('[3/4] Import customers...');
        if (is_file($customersPath)) {
            $this->importCustomersBulk($customersPath);
        } else {
            $this->warn('Customer_Master.csv tidak ada, lewati.');
        }

        $this->info('[4/4] Import leads per bulan...');
        foreach ($target as $m) {
            if ($m === null) {
                continue;
            }
            $this->importMonth($m, $byMonth[$m]);
        }

        $elapsed = round(microtime(true) - $start, 1);
        $this->newLine();
        $this->info("=== Selesai dalam {$elapsed} detik ===");
        $this->table(['Item', 'Jumlah'], [
            ['Leads insert', $this->leadsInserted],
            ['Leads update', $this->leadsUpdated],
            ['Customers baru', $this->customersCreated],
            ['Dilewati', $this->skipped],
            ['Total leads di DB', $this->dryRun ? '-' : Lead::count()],
            ['Total customers di DB', $this->dryRun ? '-' : Customer::count()],
        ]);

        if ($this->dryRun) {
            foreach ($target as $m) {
                if ($m === null) {
                    continue;
                }
                $this->line("  {$m}: " . count($byMonth[$m]) . ' lead (dry-run)');
            }
        }

        return self::SUCCESS;
    }

    private function loadMaps(): void
    {
        $this->handlerMap = Handler::query()->pluck('id', 'name')->all();

        // Tambah key canonical agar lookup by canonical name bisa
        foreach ($this->handlerAliases as $canonical) {
            foreach ($this->handlerMap as $name => $id) {
                if ($name === $canonical && ! isset($this->handlerMap[$canonical])) {
                    $this->handlerMap[$canonical] = $id;
                }
            }
        }

        $this->branchMap = Branch::query()->pluck('id', 'nocobase_id')->all();
        $this->customerMap = Customer::query()->pluck('id', 'phone')->all();
        $this->existingOrderIds = Lead::query()->pluck('order_id')->flip()->all();

        $this->info(sprintf(
            '  handlers=%d branches=%d customers=%d existing_leads=%d',
            count($this->handlerMap), count($this->branchMap),
            count($this->customerMap), count($this->existingOrderIds)
        ));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupCsvByMonth(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->error("Tidak bisa baca: {$path}");
            return [];
        }

        $header = null;
        $statusMap = [
            'new' => 'new', 'chatted' => 'chatted', 'replied' => 'replied',
            'interested' => 'interested', 'nunggu gajian' => 'nunggu_gajian',
            'promise transfer' => 'promise_transfer', 'closing' => 'closing',
            'rejected' => 'rejected',
        ];
        $knownStatuses = Lead::STATUSES;
        $byMonth = [];
        $count = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => $this->normalizeHeader($h), $row);
                continue;
            }
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $r = [];
            foreach ($header as $i => $h) {
                $r[$h] = trim((string) ($row[$i] ?? ''));
            }
            $count++;

            $orderId = $r['order_id'] ?? '';
            $phone = $this->normalizePhone($r['phone'] ?? '');
            if ($orderId === '' || $phone === null) {
                $this->skipped++;
                continue;
            }

            $timestamp = $this->parseDate($r['timestamp'] ?? null);
            if (! $timestamp) {
                $this->skipped++;
                continue;
            }
            $month = $timestamp->format('Y-m');

            $statusRaw = strtolower($r['status_fu'] ?? 'new');
            $statusFu = $statusMap[$statusRaw] ?? (in_array($statusRaw, $knownStatuses, true) ? $statusRaw : 'new');

            $handlerName = strtolower(trim($r['handler'] ?? ''));
            $canonical = $this->handlerAliases[$handlerName] ?? $handlerName;

            $data = [
                'order_id' => $orderId,
                'customer_id' => $this->customerMap[$phone] ?? null,
                '_handler_canonical' => $handlerName !== '' ? $canonical : null,
                'branch_id' => $this->branchMap[$r['branch_id'] ?? ''] ?? null,
                'financial_status' => $this->normalizeFinancialStatus($r['financial_status'] ?? ''),
                'total_value' => (int) (preg_replace('/[^0-9]/', '', $r['total_value'] ?? '0') ?: 0),
                'funnel_stage' => strtolower($r['funnel_stage'] ?? 'cold') ?: 'cold',
                'status_fu' => $statusFu,
                'notes' => $r['notes'] ?: null,
                'size' => $r['size'] ?: null,
                'utm_source' => $r['utm_source'] ?: null,
                'utm_medium' => $r['utm_medium'] ?: null,
                'utm_campaign' => $r['utm_campaign'] ?: null,
                'utm_content' => $r['utm_content'] ?: null,
                'traffic_type' => strtolower($r['traffic_type'] ?? '') ?: null,
                'lead_type' => strtolower($r['lead_type'] ?? 'new') ?: 'new',
                'timestamp' => $timestamp,
                'last_update_at' => $this->parseDate($r['last_update'] ?? null) ?? $timestamp,
                '_customer_phone' => $phone,
                '_customer_name' => $r['customer_name'] ?: ('Customer ' . substr($phone, -4)),
            ];

            $byMonth[$month][] = $data;

            if ($count % 20000 === 0) {
                $this->line("  baca: {$count} baris");
            }
        }

        fclose($handle);
        $this->info("  total dibaca: {$count}, dilewati: {$this->skipped}");
        return $byMonth;
    }

    private function importMonth(string $month, array $rows): void
    {
        $insertRows = [];
        $updateRows = [];

        foreach ($rows as $data) {
            $phone = $data['_customer_phone'];
            $cname = $data['_customer_name'];
            $canonicalHandler = $data['_handler_canonical'];
            unset($data['_customer_phone'], $data['_customer_name'], $data['_handler_canonical']);

            // Auto-create handler CS (dari nama unik di CSV) — hanya sekali per nama.
            // handler_id HARUS selalu ada di setiap row (null kalau kosong),
            // kalau tidak batch INSERT pecah: "VALUES lists must all be the same length".
            if ($canonicalHandler !== null && ! $this->dryRun) {
                $data['handler_id'] = $this->handlerMap[$canonicalHandler] ??= Handler::insertGetId([
                    'name' => $canonicalHandler,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $data['handler_id'] = $data['handler_id'] ?? null;
            }

            // Auto-create customer — cek map dulu, lalu DB (idempotent, aman re-run)
            if ($data['customer_id'] === null && ! $this->dryRun) {
                if (! isset($this->customerMap[$phone])) {
                    $existingId = Customer::where('phone', $phone)->value('id');
                    if ($existingId !== null) {
                        $this->customerMap[$phone] = $existingId;
                    } else {
                        $newId = Customer::insertGetId([
                            'phone' => $phone, 'name' => $cname,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $this->customerMap[$phone] = $newId;
                        $this->customersCreated++;
                    }
                }
                $data['customer_id'] = $this->customerMap[$phone];
            }

            if (isset($this->existingOrderIds[$data['order_id']])) {
                $updateRows[] = $data;
            } else {
                $data['created_at'] = now();
                $data['updated_at'] = now();
                $insertRows[] = $data;
            }
        }

        // Normalisasi: semua row wajib punya set kolom yang sama
        $unionKeys = [];
        foreach ($insertRows as $r) {
            foreach (array_keys($r) as $k) {
                $unionKeys[$k] = true;
            }
        }
        $unionKeys = array_keys($unionKeys);
        $insertRows = array_map(function ($r) use ($unionKeys) {
            foreach ($unionKeys as $k) {
                if (! array_key_exists($k, $r)) {
                    $r[$k] = null;
                }
            }
            return $r;
        }, $insertRows);
        $updateRows = array_map(function ($r) use ($unionKeys) {
            foreach ($unionKeys as $k) {
                if (! array_key_exists($k, $r)) {
                    $r[$k] = null;
                }
            }
            return $r;
        }, $updateRows);

        if ($this->dryRun) {
            $this->info("  {$month}: dry-run — " . count($insertRows) . ' insert, ' . count($updateRows) . ' update');
            $this->leadsInserted += count($insertRows);
            $this->leadsUpdated += count($updateRows);
            return;
        }

        foreach (array_chunk($insertRows, $this->batchSize) as $chunk) {
            Lead::insert($chunk);
            $this->leadsInserted += count($chunk);
        }

        // Update = kejadian hanya saat re-import (jarang). Bulk via updateOrCreate per baris
        // tetap jauh lebih sedikit dari total, karena mayoritas sudah di-insert.
        foreach ($updateRows as $data) {
            $orderId = $data['order_id'];
            Lead::where('order_id', $orderId)->update($data + ['updated_at' => now()]);
            $this->leadsUpdated++;
        }

        $this->info("  {$month}: +".count($insertRows).' insert, '.count($updateRows).' update');
    }

    private function importCustomersBulk(string $path): void
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return;
        }
        $header = null;
        $batch = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => $this->normalizeHeader($h), $row);
                continue;
            }
            $r = [];
            foreach ($header as $i => $h) {
                $r[$h] = trim((string) ($row[$i] ?? ''));
            }
            $phone = $this->normalizePhone($r['phone'] ?? '');
            if ($phone === null || isset($this->customerMap[$phone])) {
                continue;
            }
            $batch[] = [
                'phone' => $phone,
                'name' => ($r['customer_name'] ?? $r['name'] ?? '') ?: ('Customer ' . substr($phone, -4)),
                'total_orders' => (int) ($r['total_orders'] ?? 0),
                'total_spend' => (int) (preg_replace('/[^0-9]/', '', $r['total_spend'] ?? '0') ?: 0),
                'last_purchase_at' => $this->parseDate($r['last_purchase'] ?? null),
                'created_at' => now(), 'updated_at' => now(),
            ];

            if (count($batch) >= $this->batchSize) {
                if (! $this->dryRun) {
                    Customer::insert($batch);
                }
                $this->customersCreated += count($batch);
                foreach ($batch as $b) {
                    $this->customerMap[$b['phone']] = 0; // tandai sudah diproses
                }
                $batch = [];
            }
        }
        if ($batch) {
            if (! $this->dryRun) {
                Customer::insert($batch);
            }
            $this->customersCreated += count($batch);
        }
        fclose($handle);

        if (! $this->dryRun) {
            // Reload dari DB — menutup bug: baris terakhir batch tidak ikut ter-cache
            $this->customerMap = Customer::query()->pluck('id', 'phone')->all();
        }
        $this->info('  customers: ' . $this->customersCreated . ($this->dryRun ? ' (dry-run)' : ' baru'));
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);
        $h = trim($h, '_');

        // Alias header sheet -> key yang dipakai command
        $aliases = [
            'phone_wa' => 'phone',
            'phone_id' => 'phone',
            'handler_cs' => 'handler',
            'customer_type' => 'lead_type',
            'last_purchase_date' => 'last_purchase',
        ];

        return $aliases[$h] ?? $h;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        if ($digits === '' || strlen($digits) < 9) {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        if (! str_starts_with($digits, '62')) {
            return null;
        }
        return $digits;
    }

    private function normalizeFinancialStatus(?string $raw): string
    {
        $v = strtolower(trim((string) $raw));
        if (in_array($v, ['paid', 'lunas'], true)) {
            return 'paid';
        }
        if (in_array($v, ['refunded', 'refund'], true)) {
            return 'refunded';
        }
        if (in_array($v, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        return $v === 'unpaid' || $v === '' ? 'unpaid' : 'unpaid';
    }

    private function parseDate(?string $value): ?\Illuminate\Support\Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Sheet export memakai format M/D/YYYY (US locale) — contoh "2/1/2026", "7/26/2026"
        $formats = [
            'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y',
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/Y H:i:s', 'd/m/Y',
        ];
        foreach ($formats as $fmt) {
            try {
                $d = \Illuminate\Support\Carbon::createFromFormat($fmt, $value);
                if ($d) {
                    return $d;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
