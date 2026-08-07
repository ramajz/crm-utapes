<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Wajib Follow-Up</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ number_format($leads->total(), 0, ',', '.') }} lead wajib follow-up</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
            @endif

            @if(!auth()->user()->isCs())
            {{-- Filter (manager/admin) --}}
            <div x-data="{ showFilters: false }" class="bg-white rounded-xl shadow-sm border border-slate-200/60 mb-5">
                <div class="p-4 sm:p-5">
                    <form method="GET" action="{{ route('leads.follow-up') }}">
                        <div class="flex flex-col sm:flex-row gap-3 mb-3">
                            <div class="flex-1 relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Cari nama, no. HP, atau order ID..."
                                    class="block w-full pl-10 pr-3 py-2.5 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm placeholder:text-slate-400">
                            </div>
                            <button type="button" x-on:click="showFilters = !showFilters"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors sm:hidden">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Filter
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-3 items-end" :class="showFilters ? 'block' : 'hidden sm:flex'">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">CS</label>
                                <select name="handler_id"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                    <option value="">Semua CS</option>
                                    @foreach($handlers as $handler)
                                    <option value="{{ $handler->id }}" @selected(request('handler_id') == $handler->id)>{{ $handler->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Cabang</label>
                                <select name="branch_id"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                    <option value="">Semua Cabang</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                                <select name="status_fu"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                    <option value="">Semua Status</option>
                                    @foreach(\App\Models\Lead::STATUSES as $status)
                                    <option value="{{ $status }}" @selected(request('status_fu') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Dari</label>
                                <input type="date" name="start_date" value="{{ request('start_date') }}"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Sampai</label>
                                <input type="date" name="end_date" value="{{ request('end_date') }}"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 shadow-sm shadow-indigo-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Cari
                                </button>
                                <a href="{{ route('leads.follow-up') }}"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Desktop Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 hidden md:block overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Handler</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Cabang</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status FU</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Wajib FU</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($leads as $lead)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $lead->order_id }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-900 text-sm">{{ $lead->customer?->name ?? '-' }}</div>
                                    <div class="text-slate-400 text-xs mt-0.5">{{ $lead->customer?->phone ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $lead->handler?->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $lead->branch?->name ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        @switch($lead->status_fu) @case('new') bg-slate-100 text-slate-700 @case('chatted') bg-blue-50 text-blue-700 @case('replied') bg-indigo-50 text-indigo-700 @case('interested') bg-amber-50 text-amber-700 @case('closing') bg-emerald-50 text-emerald-700 @case('rejected') bg-rose-50 text-rose-700 @default bg-orange-50 text-orange-700 @endswitch
                                    ">{{ str_replace('_', ' ', ucfirst($lead->status_fu)) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @if($lead->follow_up_status === 'done')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Selesai
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Pending
                                    </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(auth()->user()->isCs())
                                        <form method="POST" action="{{ route('leads.complete-follow-up', $lead) }}">
                                            @csrf
                                            @if($lead->follow_up_status === 'done')
                                            <button type="button" disabled class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-400 cursor-not-allowed">
                                                Selesai
                                            </button>
                                            @else
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                                Tandai Selesai
                                            </button>
                                            @endif
                                        </form>
                                        @else
                                        <a href="{{ route('leads.show', $lead) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                            Detail
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <p>Tidak ada lead wajib follow-up.</p>
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($leads->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">{{ $leads->links() }}</div>
                @endif
            </div>

            {{-- Mobile Card View --}}
            <div class="block md:hidden space-y-3">
                @forelse($leads as $lead)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-slate-900 truncate">{{ $lead->customer?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500 mt-1">{{ $lead->customer?->phone ?? '-' }}</div>
                        </div>
                        <div class="flex gap-1.5 ml-2 flex-shrink-0">
                            @if($lead->follow_up_status === 'done')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700">Selesai</span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-slate-500">{{ $lead->handler?->name ?? '-' }}</span>
                        <span class="text-slate-400">{{ $lead->branch?->name ?? '-' }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium
                            @switch($lead->status_fu) @case('new') bg-slate-100 text-slate-700 @case('chatted') bg-blue-50 text-blue-700 @case('replied') bg-indigo-50 text-indigo-700 @case('interested') bg-amber-50 text-amber-700 @case('closing') bg-emerald-50 text-emerald-700 @case('rejected') bg-rose-50 text-rose-700 @default bg-orange-50 text-orange-700 @endswitch
                        ">{{ str_replace('_', ' ', ucfirst($lead->status_fu)) }}</span>
                        <span class="text-slate-400">{{ $lead->timestamp?->format('d M') }}</span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        @if(auth()->user()->isCs())
                        @if($lead->follow_up_status !== 'done')
                        <form method="POST" action="{{ route('leads.complete-follow-up', $lead) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                Tandai Selesai
                            </button>
                        </form>
                        @endif
                        @else
                        <a href="{{ route('leads.show', $lead) }}" class="flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                            Buka Detail
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="text-slate-400 font-medium">Tidak ada data</p>
                    <p class="text-slate-400 text-sm mt-1">Belum ada lead wajib follow-up.</p>
                </div>
                @endforelse

                @if($leads->hasPages())
                <div class="pt-2">
                    {{ $leads->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
