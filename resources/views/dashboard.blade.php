<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Dashboard CRM</h2>
                <p class="text-sm text-slate-500 mt-0.5">Overview performa bisnis Anda</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Filters --}}
            <div x-data="{ showFilters: false }" class="bg-white rounded-xl shadow-sm border border-slate-200/60 mb-6">
                <button x-on:click="showFilters = !showFilters"
                    class="w-full flex justify-between items-center p-4 sm:hidden text-sm font-medium text-slate-700">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Tanggal
                    </span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="p-4 sm:p-5" :class="showFilters ? 'block' : 'hidden sm:block'">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ $startDate }}"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ $endDate }}"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 shadow-sm shadow-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Total Leads</p>
                            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Follow-up</p>
                            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ $stats['followed_up'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Belum FU</p>
                            <p class="text-xl font-bold text-amber-600 mt-0.5">{{ $stats['not_followed_up'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Closing</p>
                            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ $stats['closing'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Revenue</p>
                            <p class="text-lg font-bold text-purple-600 mt-0.5 truncate">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Konversi</p>
                            <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ $stats['conversion_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Rata-rata Response</p>
                            <p class="text-xl font-bold text-teal-600 mt-0.5">
                                @if($stats['avg_response_time_minutes'])
                                    {{ $stats['avg_response_time_minutes'] }} <span class="text-xs font-normal">mnt</span>
                                @else
                                    <span class="text-base font-normal text-slate-400">—</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 hidden md:block overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Daftar Leads Terbaru</h3>
                    <a href="{{ route('leads.index') }}" class="flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Handler</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Funnel</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
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
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                        @switch($lead->status_fu)
                                            @case('new') bg-slate-100 text-slate-700 @break
                                            @case('chatted') bg-blue-50 text-blue-700 @break
                                            @case('replied') bg-indigo-50 text-indigo-700 @break
                                            @case('interested') bg-amber-50 text-amber-700 @break
                                            @case('promise_transfer') bg-orange-50 text-orange-700 @break
                                            @case('closing') bg-emerald-50 text-emerald-700 @break
                                            @case('rejected') bg-rose-50 text-rose-700 @break
                                            @default bg-slate-100 text-slate-700
                                        @endswitch
                                    ">{{ str_replace('_', ' ', ucfirst($lead->status_fu)) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                        @if($lead->funnel_stage === 'hot') bg-rose-50 text-rose-700
                                        @elseif($lead->funnel_stage === 'warm') bg-amber-50 text-amber-700
                                        @else bg-sky-50 text-sky-700 @endif
                                    ">
                                        {{ ucfirst($lead->funnel_stage) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-mono text-sm font-medium text-slate-900">Rp {{ number_format($lead->total_value, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('leads.show', $lead) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                        Detail
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p>Tidak ada data lead pada periode ini.</p>
                                </td>
                            </tr>
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
                <div @click="window.location='{{ route('leads.show', $lead) }}'" class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 active:scale-[0.99] transition-all duration-150 cursor-pointer hover:shadow-md">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-slate-900 truncate flex items-center gap-2">
                                {{ $lead->customer?->name ?? '-' }}
                                @if($lead->lead_type === 'repeat')
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-600 font-medium">Repeat</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                <span>{{ $lead->customer?->phone ?? '-' }}</span>
                                @if($lead->customer?->phone)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead->customer->phone) }}" target="_blank" rel="noopener noreferrer"
                                    onclick="event.stopPropagation()"
                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-1.5 ml-2 flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-medium
                                @switch($lead->status_fu)
                                    @case('new') bg-slate-100 text-slate-700 @break
                                    @case('chatted') bg-blue-50 text-blue-700 @break
                                    @case('replied') bg-indigo-50 text-indigo-700 @break
                                    @case('interested') bg-amber-50 text-amber-700 @break
                                    @case('closing') bg-emerald-50 text-emerald-700 @break
                                    @case('rejected') bg-rose-50 text-rose-700 @break
                                    @default bg-orange-50 text-orange-700
                                @endswitch
                            ">{{ str_replace('_', ' ', ucfirst($lead->status_fu)) }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-500">{{ $lead->handler?->name ?? '-' }}</span>
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-medium
                                @if($lead->funnel_stage === 'hot') bg-rose-50 text-rose-700
                                @elseif($lead->funnel_stage === 'warm') bg-amber-50 text-amber-700
                                @else bg-sky-50 text-sky-700 @endif
                            ">
                                {{ ucfirst($lead->funnel_stage) }}
                            </span>
                            <span class="text-slate-400">{{ $lead->timestamp->format('d M H:i') }}</span>
                        </div>
                        <div class="text-sm font-bold text-slate-900">Rp {{ number_format($lead->total_value, 0, ',', '.') }}</div>
                    </div>
                </div>
                @empty
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-slate-400 font-medium">Tidak ada data</p>
                    <p class="text-slate-400 text-sm mt-1">Belum ada lead pada periode ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
