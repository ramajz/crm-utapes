<?php

namespace App\Console\Commands;

use App\Services\ScalevOrderSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportScalevOrders extends Command
{
    protected $signature = 'scalev:import
        {--path=database/imports/Webhook_Logs.csv : File CSV mentah webhook Scalev}
        {--dry-run : Preview tanpa menyimpan ke DB}';

    protected $description = 'Impor data order dari log webhook Scalev (Webhook_Logs.csv) ke tabel orders & order_items';

    private bool $dryRun = false;
    private int $rowsRead = 0;
    private int $skipped = 0;

    public function handle(): int
    {
        $path = $this->option('path');
        if (!is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $this->dryRun = (bool) $this->option('dry-run');
        if ($this->dryRun) {
            $this->warn('=== DRY-RUN: tidak ada data yang ditulis ke database ===');
        }

        $this->line('Memproses: ' . $path);

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error("Tidak bisa membaca: {$path}");
            return self::FAILURE;
        }

        $sync = $this->dryRun ? null : new ScalevOrderSync();

        fgetcsv($handle, 0, ',', '"', '\\');

        DB::beginTransaction();
        try {
            $chunk = 0;
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $this->rowsRead++;
                if (count($row) < 3) {
                    $this->skipped++;
                    continue;
                }

                $payload = json_decode($row[2], true);
                if (!is_array($payload)) {
                    $this->skipped++;
                    continue;
                }

                $event = $payload['event'] ?? null;
                $data = $payload['data'] ?? null;
                if (!is_array($data) || !isset($data['order_id'])) {
                    $this->skipped++;
                    continue;
                }

                if ($event === 'order.created' || $event === 'order.payment_status_changed') {
                    if ($sync) {
                        $sync->processEvent($event, $data);
                    }
                } else {
                    $this->skipped++;
                }

                $chunk++;
                if ($chunk >= 2000) {
                    if ($sync) {
                        DB::commit();
                        DB::beginTransaction();
                    }
                    $chunk = 0;
                    $this->line(sprintf(
                        '  progress: %d baris (orders: %d, customers: %d)',
                        $this->rowsRead, $sync ? $sync->ordersInserted + $sync->ordersUpdated : 0, $sync ? $sync->customersCreated : 0
                    ));
                }
            }

            if ($sync) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import gagal: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($handle);
        }

        $this->line('');
        $this->info('=== Hasil Impor Scalev ===');
        $this->table(
            ['Item', 'Jumlah'],
            [
                ['Baris CSV dibaca', $this->rowsRead],
                ['Baris dilewati (invalid/non-order)', $this->skipped],
                ['Handlers dibuat', $sync ? $sync->handlersCreated : 0],
                ['Customers dibuat', $sync ? $sync->customersCreated : 0],
                ['Orders ditambah', $sync ? $sync->ordersInserted : 0],
                ['Orders diperbarui', $sync ? $sync->ordersUpdated : 0],
                ['Order items ditambah', $sync ? $sync->itemsInserted : 0],
            ]
        );

        if ($sync) {
            $this->info('Total orders di DB: ' . DB::table('orders')->count());
            $this->info('Total order_items di DB: ' . DB::table('order_items')->count());
        }

        return self::SUCCESS;
    }
}
