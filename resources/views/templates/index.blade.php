<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Template WhatsApp</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ number_format($templates->total(), 0, ',', '.') }} template</p>
            </div>
            <a href="{{ route('templates.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Template
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
            @endif

            <div class="mb-4 p-4 rounded-lg bg-sky-50 border border-sky-200 text-sm text-sky-700">
                <strong>Variabel yang didukung:</strong>
                <code class="mx-1 px-1.5 py-0.5 bg-sky-100 rounded">{nama}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-sky-100 rounded">{order_id}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-sky-100 rounded">{size}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-sky-100 rounded">{total}</code>
                <code class="mx-1 px-1.5 py-0.5 bg-sky-100 rounded">{handler}</code>
                — diganti otomatis dengan data lead saat CS kirim.
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Pesan</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($templates as $template)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 font-medium text-slate-900 text-sm">{{ $template->name }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        @if($template->category === 'hot') bg-rose-50 text-rose-600
                                        @elseif($template->category === 'warm') bg-amber-50 text-amber-600
                                        @else bg-sky-50 text-sky-600 @endif
                                    ">{{ ucfirst($template->category) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-slate-600 max-w-lg line-clamp-2">{{ $template->message }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        {{ $template->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('templates.edit', $template) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-rose-600 hover:text-rose-800 transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                <p>Tidak ada template. Klik "Tambah Template" untuk membuat.</p>
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($templates->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">{{ $templates->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
