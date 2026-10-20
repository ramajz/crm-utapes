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
        // PHP membuang file yang melebihi upload_max_filesize SEBELUM Laravel
        // melihatnya. Akibatnya $_FILES kosong dan pesan default cuma
        // "The csv field is required" — menyesatkan. Deteksi dulu di sini.
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $phpUploadLimit = $this->phpBytes(ini_get('upload_max_filesize'));
        // Catatan: kalau kiriman melebihi post_max_size, PHP membuang SELURUH
        // body termasuk _token → request berhenti di middleware CSRF (419),
        // controller ini tidak pernah jalan. Itu sebabnya cek di bawah hanya
        // menangani rentang upload_max_filesize..post_max_size.

        if (! $request->hasFile('csv') && $contentLength > 0 && $phpUploadLimit > 0 && $contentLength > $phpUploadLimit) {
            return back()->with('error', sprintf(
                'File ditolak server sebelum sampai ke aplikasi. Ukuran kiriman %s MB, batas PHP %s MB. '
                .'Kecilkan CSV-nya (pecah per bulan) atau naikkan upload_max_filesize/post_max_size.',
                number_format($contentLength / 1048576, 1),
                number_format($phpUploadLimit / 1048576, 1)
            ));
        }

        // Batas validasi diambil dari PHP, bukan angka hardcode — supaya pesan
        // tidak pernah menjanjikan 20MB sementara PHP cuma menerima 2MB.
        $maxKb = $phpUploadLimit > 0 ? max(1, (int) floor($phpUploadLimit / 1024)) : 20480;

        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:'.$maxKb],
        ], [
            'csv.required' => 'File CSV tidak terkirim. Ukuran kiriman '
                .number_format($contentLength / 1048576, 1).' MB, batas PHP '
                .number_format($phpUploadLimit / 1048576, 1).' MB.',
            'csv.max' => 'File CSV terlalu besar (batas server '.number_format($maxKb / 1024, 1).' MB).',
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

        // Simpan file ke storage/app/imports (pasti bisa ditulis www-data).
        // JANGAN pakai database/imports — folder itu sering root-owned
        // (hasil docker cp) dan bikin copy() gagal 500.
        // Nama file pakai WIB explicit (config app timezone tetap UTC —
        // jangan diganti, data historis di DB juga UTC).
        $dir = storage_path(self::IMPORT_DIR);
        File::ensureDirectoryExists($dir);
        $wib = \Illuminate\Support\Carbon::now('Asia/Jakarta');
        $timestamp = $wib->format('Ymd_His');
        $uploadName = "leads_{$timestamp}.csv";
        $file->move($dir, $uploadName);

        $logPath = $dir . "/import_{$timestamp}.log";
        // Tombol baru mengirim mode=dry_run|import; checkbox lama tetap dry_run=1 (backward compatible)
        $isDry = $request->input('mode') === 'dry_run' || $request->boolean('dry_run');
        $mode = $isDry ? '--dry-run' : '';
        $month = $request->input('month'); // opsional, format YYYY-MM

        // Jalankan di container yang sama (tanpa docker exec dari dalam container)
        $cmd = sprintf(
            'cd %s && nohup php artisan migrate:sheets-batch --path=%s --looker=%s %s %s > %s 2>&1 &',
            base_path(),
            escapeshellarg($dir),
            escapeshellarg($uploadName),
            $mode,
            $month ? '--month=' . escapeshellarg($month) : '--all',
            escapeshellarg($logPath)
        );
        exec($cmd);

        $flash = $isDry
            ? 'MODE DRY-RUN — preview saja, TIDAK ada data yang ditulis. Lihat ringkasan di bawah.'
            : 'MODE IMPORT — data ditulis ke database. Pantau progres di bawah.';

        return redirect()
            ->route('import.index')
            ->with('success', $flash);
    }

    public function status(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'running' => (bool) $this->runningJob(),
            'log' => $this->tailLog(),
        ]);
    }

    // ─── helpers ────────────────────────────────────────────────

    /**
     * Konversi nilai ini (upload_max_filesize / post_max_size) ke byte.
     * Contoh: "2M" => 2097152, "8M" => 8388608, "64m" => 67108864.
     */
    private function phpBytes(string|false $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }

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
                'started_at' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime(), 'Asia/Jakarta')->format('d M Y H:i WIB'),
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
