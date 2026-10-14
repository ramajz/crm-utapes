<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Fitur A (PRD_IMPORT.md): Import CSV via web.
 *
 * Menjalankan command migrate:sheets-batch yang SUDAH ADA di background,
 * status dibaca dari file log. Tanpa queue — cukup untuk single user.
 */
class ImportController extends Controller
{
    private const IMPORT_DIR = 'app/imports';

    /**
     * Daftar kolom wajib header CSV (format Google Sheets, 18 kolom).
     */
    private const REQUIRED_HEADERS = [
        'Timestamp', 'Order ID', 'Customer Name', 'Phone (WA)',
        'Handler (CS)', 'Financial Status', 'Total Value', 'Funnel Stage',
        'Status FU', 'Notes', 'Last Update', 'Customer Type', 'Size',
        'UTM Source', 'UTM Medium', 'UTM Campaign', 'UTM Content', 'Traffic Type',
    ];

    public function index(): View
    {
        return view('imports.index', [
            'jobs' => $this->jobHistory(),
            'running' => $this->runningJob(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        if ($this->runningJob()) {
            return back()->with('error', 'Masih ada proses import yang berjalan. Tunggu sampai selesai.');
        }

        $file = $request->file('csv');

        // Validasi header — tolak dini kalau formatnya bukan export Sheets
        $firstLine = rtrim((string) fgets(fopen($file->getRealPath(), 'r')));
        $headers = str_getcsv($firstLine);
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing) {
            return back()->with('error', 'Header CSV tidak cocok. Kolom hilang: ' . implode(', ', $missing));
        }

        // Simpan file + salin ke database/imports (dibaca oleh command)
        $dir = storage_path(self::IMPORT_DIR);
        File::ensureDirectoryExists($dir);
        $timestamp = date('Ymd_His');
        $file->move($dir, "leads_{$timestamp}.csv");
        $uploadPath = base_path('database/imports/web_upload.csv');
        File::copy($dir . "/leads_{$timestamp}.csv", $uploadPath);

        $logPath = $dir . "/import_{$timestamp}.log";
        $mode = $request->boolean('dry_run') ? '--dry-run' : '';
        $month = $request->input('month'); // opsional, format YYYY-MM

        // Jalankan di container yang sama (tanpa docker exec dari dalam container)
        $cmd = sprintf(
            'cd %s && nohup php artisan migrate:sheets-batch --looker=database/imports/web_upload.csv %s %s > %s 2>&1 &',
            base_path(),
            $mode,
            $month ? '--month=' . escapeshellarg($month) : '--all',
            escapeshellarg($logPath)
        );
        exec($cmd);

        return redirect()
            ->route('import.index')
            ->with('success', 'Import dimulai. Halaman ini otomatis refresh — pantau progresnya di bawah.');
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'running' => (bool) $this->runningJob(),
            'log' => $this->tailLog(),
        ]);
    }

    // ─── helpers ────────────────────────────────────────────────

    private function runningJob(): bool
    {
        exec('ps -eo args | grep -c "[m]igrate:sheets-batch"', $out);
        return ((int) ($out[0] ?? 0)) > 0;
    }

    private function tailLog(int $lines = 30): string
    {
        $dir = storage_path(self::IMPORT_DIR);
        $logs = File::isDirectory($dir) ? glob($dir . '/import_*.log') : [];
        if (! $logs) {
            return '';
        }
        sort($logs);
        $last = end($logs);
        $content = File::get($last);
        $parts = preg_split('/\r?\n/', trim($content));

        return implode("\n", array_slice($parts, -$lines));
    }

    private function jobHistory(): array
    {
        $dir = storage_path(self::IMPORT_DIR);
        if (! File::isDirectory($dir)) {
            return [];
        }

        $jobs = [];
        foreach (array_reverse(File::files($dir)) as $file) {
            if (! str_ends_with($file->getFilename(), '.log')) {
                continue;
            }
            $content = File::get($file->getPathname());
            $jobs[] = [
                'file' => $file->getFilename(),
                'started_at' => date('d M Y H:i', $file->getMTime()),
                'done' => str_contains($content, 'Selesai dalam'),
                'summary' => $this->parseSummary($content),
                'preview' => implode("\n", array_slice(
                    preg_split('/\r?\n/', trim($content)), -8
                )),
            ];
            if (count($jobs) >= 5) {
                break;
            }
        }

        return $jobs;
    }

    private function parseSummary(string $content): array
    {
        $out = [];
        if (preg_match('/Leads insert\s*\|\s*(\d+)/', $content, $m)) {
            $out['insert'] = (int) $m[1];
        }
        if (preg_match('/Leads update\s*\|\s*(\d+)/', $content, $m)) {
            $out['update'] = (int) $m[1];
        }
        if (preg_match('/Customers baru\s*\|\s*(\d+)/', $content, $m)) {
            $out['customers'] = (int) $m[1];
        }
        if (preg_match('/Dilewati\s*\|\s*(\d+)/', $content, $m)) {
            $out['skipped'] = (int) $m[1];
        }
        if (preg_match('/Selesai dalam (\d+) detik/', $content, $m)) {
            $out['seconds'] = (int) $m[1];
        }

        return $out;
    }
}
