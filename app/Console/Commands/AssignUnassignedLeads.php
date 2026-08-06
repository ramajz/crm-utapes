<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\LeadAssignmentService;
use Illuminate\Console\Command;

class AssignUnassignedLeads extends Command
{
    protected $signature = 'leads:assign-unassigned
        {--limit= : Maksimal lead yang diproses}
        {--dry-run : Preview tanpa menyimpan ke DB}';

    protected $description = 'Auto-assign lead yang belum punya handler (CS) menggunakan strategi rotasi';

    public function handle(LeadAssignmentService $assignment): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = Lead::query()->whereNull('handler_id')->orderBy('timestamp');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Tidak ada lead tanpa handler.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('=== DRY-RUN: tidak ada data yang ditulis ke database ===');
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $processed = 0;
        $assigned = 0;

        $leads = $query->get();
        $previewAssignments = $dryRun ? $assignment->preview($leads->count()) : [];

        foreach ($leads as $index => $lead) {
            $handlerId = $dryRun
                ? ($previewAssignments[$index] ?? null)
                : $assignment->assign();

            if ($handlerId === null) {
                $this->warn("Tidak ada CS aktif — lead {$lead->order_id} dilewati.");
                continue;
            }

            if (!$dryRun) {
                $lead->update(['handler_id' => $handlerId, 'last_update_at' => now()]);
            }

            $processed++;
            $assigned++;
            $this->line(sprintf('  %s → handler #%d', $lead->order_id, $handlerId));
        }

        $this->newLine();
        $this->info("=== Hasil ({$processed} lead diproses, {$assigned} di-assign) ===");

        return self::SUCCESS;
    }
}
