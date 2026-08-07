<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Handler;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateFromSheets extends Command
{
    protected $signature = 'migrate:sheets
        {--path=database/imports : Direktori tempat file CSV}
        {--looker=Looker_Master.csv : Nama file CSV gabungan leads}
        {--customers=Customer_Master.csv : Nama file CSV customer}
        {--flush : Hapus semua lead, customer, dan handler yang ada sebelum import}
        {--dry-run : Preview tanpa menyimpan ke DB}';

    protected $description = 'Migrasi data dari Google Sheets (Looker_Master & Customer_Master) ke database';

    private array $headerMap = [];
    private bool $dryRun = false;
    private array $handlerMap = [];
    private array $customerMap = [];
    private int $handlersCreated = 0;
    private int $customersCreated = 0;
    private int $customersUpdated = 0;
    private int $leadsInserted = 0;
    private int $leadsUpdated = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $path = $this->option('path');
        $lookerPath = rtrim($path, '/') . '/' . $this->option('looker');
        $customersPath = rtrim($path, '/') . '/' . $this->option('customers');

        if (!is_file($lookerPath)) {
            $this->error("File leads tidak ditemukan: {$lookerPath}");
            $this->line("Export Looker_Master dari Google Sheets (File → Download → CSV), lalu taruh di database/imports/");
            return self::FAILURE;
        }

        $this->dryRun = (bool) $this->option('dry-run');
        if ($this->dryRun) {
            $this->warn('=== DRY-RUN: tidak ada data yang ditulis ke database ===');
        }

        if (!$this->dryRun && $this->option('flush')) {
            $this->warn('=== FLUSH: menghapus data lama (leads, customers, handlers) ===');
            Lead::query()->forceDelete();
            Customer::query()->delete();
            Handler::query()->delete();
            $this->info('Data lama dihapus. Mulai import data baru...');
        }

        DB::transaction(function () use ($lookerPath, $customersPath) {
            $this->loadMaps();
            $this->importCustomers(is_file($customersPath) ? $customersPath : null);
            $this->importLeads($lookerPath);
            $this->recalculateCustomerStats();
        });

        $this->line('');
        $this->info('=== Hasil Migrasi ===');
        $this->table(
            ['Item', 'Jumlah'],
            [
                ['Handlers dibuat', $this->handlersCreated],
                ['Customers dibuat', $this->customersCreated],
                ['Customers diperbarui', $this->customersUpdated],
                ['Leads ditambah', $this->leadsInserted],
                ['Leads diperbarui', $this->leadsUpdated],
                ['Baris dilewati (invalid)', $this->skipped],
            ]
        );

        if (!$this->dryRun) {
            $this->info("Total leads di DB: " . Lead::count());
            $this->info("Total customers di DB: " . Customer::count());
        }

        return self::SUCCESS;
    }

    private function importCustomers(?string $path): void
    {
        if (!$path) {
            $this->warn('Customer_Master.csv tidak ditemukan, lewati import customer.');
            return;
        }

        foreach ($this->readCsv($path) as $row) {
            $phone = $this->normalizePhone($row['phone'] ?? '');
            if ($phone === null) {
                continue;
            }

            $data = [
                'name' => $row['name'] ?? null,
                'total_orders' => (int) ($row['total_orders'] ?? 0),
                'total_spend' => (int) $row['total_spend'] ?? 0,
                'last_purchase_at' => $this->parseDate($row['last_purchase'] ?? null),
            ];

            $customer = Customer::where('phone', $phone)->first();
            if ($customer) {
                if ($this->dryRun) {
                    $this->customersUpdated++;
                    continue;
                }
                $customer->update(array_filter($data, fn ($v) => $v !== null));
                $this->customersUpdated++;
            } else {
                if ($this->dryRun) {
                    $this->customersCreated++;
                    continue;
                }
                Customer::create(array_filter($data, fn ($v) => $v !== null) + ['phone' => $phone]);
                $this->customersCreated++;
            }
        }

        $this->info("Customer_Master: {$this->customersCreated} dibuat, {$this->customersUpdated} diperbarui");
    }

    private function importLeads(string $path): void
    {
        $statusMap = [
            'new' => 'new', 'chatted' => 'chatted', 'replied' => 'replied', 'interested' => 'interested',
            'nunggu gajian' => 'nunggu_gajian', 'promise transfer' => 'promise_transfer',
            'closing' => 'closing', 'rejected' => 'rejected',
        ];
        $knownStatuses = Lead::STATUSES;

        $this->line('');
        $this->line("Memproses: {$path}");
        $count = 0;
        foreach ($this->readCsv($path) as $row) {
            $count++;
            if ($count % 10000 === 0) {
                $this->line("  progress: {$count} baris (leads: {$this->leadsInserted}, customers: {$this->customersCreated})");
            }

            $orderId = trim((string) ($row['order_id'] ?? ''));
            $phone = $this->normalizePhone($row['phone'] ?? '');
            if ($orderId === '' || $phone === null) {
                $this->skipped++;
                continue;
            }

            $statusRaw = strtolower(trim((string) ($row['status_fu'] ?? 'new')));
            $statusFu = $statusMap[$statusRaw] ?? (in_array($statusRaw, $knownStatuses) ? $statusRaw : 'new');

            $timestamp = $this->parseDate($row['timestamp'] ?? null);
            $customerName = trim((string) ($row['customer_name'] ?? '')) ?: 'Customer ' . substr($phone, -4);

            $data = [
                'order_id' => $orderId,
                'financial_status' => $this->normalizeFinancialStatus($row['financial_status'] ?? ''),
                'total_value' => (int) (preg_replace('/[^0-9]/', '', (string) ($row['total_value'] ?? 0)) ?: 0),
                'funnel_stage' => strtolower(trim((string) ($row['funnel_stage'] ?? 'cold'))) ?: 'cold',
                'status_fu' => $statusFu,
                'notes' => $row['notes'] ?? null,
                'size' => $row['size'] ?? null,
                'utm_source' => $row['utm_source'] ?? null,
                'utm_medium' => $row['utm_medium'] ?? null,
                'utm_campaign' => $row['utm_campaign'] ?? null,
                'utm_content' => $row['utm_content'] ?? null,
                'traffic_type' => strtolower(trim((string) ($row['traffic_type'] ?? ''))) ?: null,
                'lead_type' => strtolower(trim((string) ($row['lead_type'] ?? 'new'))) ?: 'new',
                'timestamp' => $timestamp ?? now(),
                'last_update_at' => $this->parseDate($row['last_update'] ?? null) ?? $timestamp ?? now(),
            ];

            $handlerName = trim((string) ($row['handler'] ?? ''));
            $handlerId = $handlerName !== '' ? $this->resolveHandler($handlerName) : null;
            $data['handler_id'] = $handlerId;

            $customer = $this->resolveCustomer($phone, $customerName, $data['timestamp']);
            $data['customer_id'] = $customer?->id;

            if ($this->dryRun) {
                $this->leadsInserted++;
                continue;
            }

            $existing = Lead::where('order_id', $orderId)->first();
            if ($existing) {
                $existing->update($data);
                $this->leadsUpdated++;
            } else {
                Lead::create($data);
                $this->leadsInserted++;
            }
        }

        $this->info("Lead rows dibaca: {$count}, inserted: {$this->leadsInserted}, updated: {$this->leadsUpdated}");
    }

    private function loadMaps(): void
    {
        $this->handlerMap = Handler::all()->pluck('id', 'name')->all();
        $this->customerMap = Customer::query()
            ->whereNotNull('phone')
            ->get()
            ->keyBy('phone')
            ->all();
    }

    /**
     * Alias nama CS dari AppScript ke nama resmi (sesuai PRD-v2).
     */
    private function resolveHandlerName(string $name): string
    {
        $aliases = [
            'lana' => 'Hafiz',
            'rafli bahar' => 'Rafli',
            'ikiobeng' => 'Oben',
            'febrifjr' => 'Babe',
            'erpann' => 'Erpan',
            'ikbal cjr' => 'Iqbal',
            'kiki ternyata' => 'Kiki',
            'andhi yanuar' => 'Andhi',
        ];

        $key = strtolower(trim($name));

        return $aliases[$key] ?? $name;
    }

    private function resolveHandler(string $name): ?int
    {
        $canonical = $this->resolveHandlerName($name);

        if (isset($this->handlerMap[$canonical])) {
            return $this->handlerMap[$canonical];
        }
        if ($this->dryRun) {
            return null;
        }
        $handler = Handler::create(['name' => $canonical, 'is_active' => true]);
        $this->handlerMap[$canonical] = $handler->id;
        $this->handlersCreated++;
        return $handler->id;
    }

    private function resolveCustomer(string $phone, string $name, $firstTimestamp): ?Customer
    {
        if (isset($this->customerMap[$phone])) {
            $customer = $this->customerMap[$phone];
            if ($customer->first_purchase_at === null && $firstTimestamp && !$this->dryRun) {
                $customer->update(['first_purchase_at' => $firstTimestamp]);
            }
            return $customer;
        }
        if ($this->dryRun) {
            return null;
        }
        $customer = Customer::create([
            'phone' => $phone,
            'name' => $name,
            'first_purchase_at' => $firstTimestamp,
        ]);
        $this->customerMap[$phone] = $customer;
        $this->customersCreated++;
        return $customer;
    }

    private function recalculateCustomerStats(): void
    {
        if ($this->dryRun) {
            return;
        }

        $stats = Lead::select('customer_id')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total_value) as total_spend')
            ->groupBy('customer_id')
            ->get();

        foreach ($stats as $stat) {
            Customer::where('id', $stat->customer_id)->update([
                'total_orders' => $stat->total_orders,
                'total_spend' => $stat->total_spend,
            ]);
        }

        $this->info('Statistik customer (total_orders, total_spend) diperbarui dari data leads.');
    }

    private function readCsv(string $path): iterable
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error("Tidak bisa membaca: {$path}");
            return;
        }

        $header = null;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => $this->normalizeHeader($h), $row);
                $this->headerMap = array_combine($header, array_keys($header));
                $this->info('Header: ' . implode(' | ', $header));
                continue;
            }
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $mapped = [];
            foreach ($this->headerMap as $headerName => $idx) {
                $mapped[$headerName] = trim((string) ($row[$idx] ?? ''));
            }
            yield $mapped;
        }

        fclose($handle);
    }

    private function normalizeHeader(string $header): string
    {
        $key = Str::slug(strtolower(trim($header)), '_');

        return match ($key) {
            'phone_wa', 'phone_id', 'phone_number', 'no_wa', 'nomor' => 'phone',
            'handler_cs', 'cs', 'handler_name', 'pic' => 'handler',
            'customer_type', 'tipe_customer' => 'lead_type',
            'order_id', 'no_order', 'id_order' => 'order_id',
            'customer_name', 'nama' => 'customer_name',
            'status_fu', 'status' => 'status_fu',
            'total_value', 'nominal', 'amount' => 'total_value',
            'financial_status', 'payment_status' => 'financial_status',
            'timestamp', 'tanggal_masuk', 'tanggal_transaksi_masuk', 'order_date' => 'timestamp',
            'last_update', 'last_update_at' => 'last_update',
            'funnel_stage', 'funnel' => 'funnel_stage',
            'traffic_type', 'traffic' => 'traffic_type',
            'size', 'ukuran' => 'size',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content' => $key,
            default => $key,
        };
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }
        return $digits;
    }

    private function normalizeFinancialStatus(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        if ($s === 'lunas' || $s === 'paid' || $s === 'lunaskas' || $s === 'lunastransfer') {
            return 'paid';
        }
        return $s !== '' ? $s : 'unpaid';
    }

    private function parseDate(?string $value): mixed
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        if (is_numeric($value) && $value > 25569) {
            return \Carbon\Carbon::createFromTimestamp(($value - 25569) * 86400);
        }

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}(?: \d{1,2}:\d{2}(?::\d{2})?)?$/', $value)) {
            $time = preg_match('/:\d{2}:\d{2}$/', $value) ? ' H:i:s' : (str_contains($value, ':') ? ' H:i' : '');
            return \Carbon\Carbon::createFromFormat('!' . 'm/d/Y' . $time, $value);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}(?::\d{2})?)?$/', $value)) {
            $time = preg_match('/:\d{2}:\d{2}$/', $value) ? ' H:i:s' : (str_contains($value, ':') ? ' H:i' : '');
            return \Carbon\Carbon::createFromFormat('!' . 'Y-m-d' . $time, $value);
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
