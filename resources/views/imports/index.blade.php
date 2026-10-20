<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">
            Import Data
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Upload CSV leads (format Google Sheets, 18 kolom). Proses jalan di background — halaman ini refresh otomatis.
        </p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto space-y-6">

            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            {{-- Error validasi. TANPA blok ini, upload yang ditolak tampil seperti
                 "tidak terjadi apa-apa" (halaman balik ke form kosong, tanpa pesan). --}}
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold mb-1">Upload gagal:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-red-700">
                        Batas server saat ini — PHP upload: {{ ini_get('upload_max_filesize') }},
                        PHP post: {{ ini_get('post_max_size') }}.
                    </p>
                </div>
            @endif

            {{-- Form upload --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="font-semibold text-slate-800 mb-1">Upload CSV baru</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Format: export CSV dari tab bulanan Google Sheets (Leads_Jul_2026, Leads_Ags, dst).
                    Import bersifat <strong>idempotent</strong> — Order ID yang sama di-update, tidak digandakan.
                </p>

                <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">File CSV *</label>
                            <input type="file" name="csv" accept=".csv,.txt" required
                                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Bulan (opsional)</label>
                            <select name="month" class="w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                <option value="">Semua bulan di CSV</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ date('Y', strtotime('first day of this month')) }}-{{ sprintf('%02d', $i) }}">
                                        {{ date('Y', strtotime('first day of this month')) }}-{{ sprintf('%02d', $i) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                            <button type="submit" name="mode" value="dry_run" @disabled($running)
                                class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 py-2.5 text-sm font-semibold text-amber-900 hover:bg-amber-100 disabled:opacity-50">
                                Preview (dry-run)
                            </button>
                            <button type="submit" name="mode" value="import" @disabled($running)
                                onclick="return confirm('Import sungguhan akan menulis atau memperbarui data di database. Lanjutkan?')"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                                Import Sungguhan
                            </button>
                            @if ($running)
                                <span class="text-sm text-amber-700">Proses import sedang berjalan…</span>
                            @endif
                        </div>
                </form>
            </div>

            {{-- Status live --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6" x-data="importStatus()" x-init="poll()">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-slate-800">Status proses</h3>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                        :class="running ? 'text-amber-600' : 'text-emerald-600'">
                        <span class="w-2 h-2 rounded-full" :class="running ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
                        <span x-text="running ? 'Berjalan…' : 'Tidak ada proses aktif'"></span>
                    </span>
                </div>
                <pre class="text-xs bg-slate-950 text-slate-100 rounded-xl p-4 overflow-x-auto max-h-64 whitespace-pre-wrap" x-text="log || 'Belum ada log. Mulai import untuk melihat progres.'"></pre>
            </div>

            {{-- Riwayat --}}
            @if ($jobs)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Riwayat import terakhir</h3>
                    <div class="space-y-4">
                        @foreach ($jobs as $job)
                            <div class="border border-slate-100 rounded-xl p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <span class="text-sm font-medium text-slate-700">{{ $job['started_at'] }}</span>
                                    @if ($job['done'])
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Selesai {{ $job['summary']['seconds'] ?? '' }}{{ isset($job['summary']['seconds']) ? 's' : '' }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Belum selesai</span>
                                    @endif
                                </div>
                                @if ($job['summary'])
                                    <div class="flex flex-wrap gap-3 text-sm text-slate-600">
                                        @if (isset($job['summary']['insert']))<span>Leads: <strong>{{ number_format($job['summary']['insert']) }}</strong> masuk</span>@endif
                                        @if (isset($job['summary']['update']))<span>Update: <strong>{{ number_format($job['summary']['update']) }}</strong></span>@endif
                                        @if (isset($job['summary']['customers']))<span>Customer baru: <strong>{{ number_format($job['summary']['customers']) }}</strong></span>@endif
                                        @if (isset($job['summary']['skipped']))<span>Dilewati: <strong>{{ number_format($job['summary']['skipped']) }}</strong></span>@endif
                                    </div>
                                @endif
                                <details class="mt-2">
                                    <summary class="text-xs text-slate-400 cursor-pointer hover:text-slate-600">Lihat log</summary>
                                    <pre class="text-xs bg-slate-50 rounded-lg p-3 mt-2 overflow-x-auto">{{ $job['preview'] }}</pre>
                                </details>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script>
        function importStatus() {
            return {
                running: @json($running),
                log: '',
                async poll() {
                    try {
                        const r = await fetch('{{ route('import.status') }}', { headers: { 'Accept': 'application/json' } });
                        const d = await r.json();
                        this.running = d.running;
                        this.log = d.log;
                    } catch (e) { /* network glitch — poll lagi */ }
                    // Poll cepat saat berjalan, lambat saat idle
                    setTimeout(() => this.poll(), this.running ? 3000 : 15000);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
