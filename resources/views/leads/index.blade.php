<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Daftar Leads</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ $leads->total() }} total leads</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Search & Filters --}}
            <div x-data="{ showFilters: false }" class="bg-white rounded-xl shadow-sm border border-slate-200/60 mb-5">
                <div class="p-4 sm:p-5">
                    <form method="GET" action="{{ route('leads.index') }}">
                        {{-- Search row (always visible) --}}
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

                        {{-- Filter fields --}}
                        <div class="flex flex-wrap gap-3 items-end" :class="showFilters ? 'block' : 'hidden sm:flex'">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                                <select name="status_fu"
                                    class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="new" @selected(request('status_fu') === 'new')>New</option>
                                    <option value="chatted" @selected(request('status_fu') === 'chatted')>Chatted</option>
                                    <option value="replied" @selected(request('status_fu') === 'replied')>Replied</option>
                                    <option value="interested" @selected(request('status_fu') === 'interested')>Interested</option>
                                    <option value="nunggu_gajian" @selected(request('status_fu') === 'nunggu_gajian')>Nunggu Gajian</option>
                                    <option value="promise_transfer" @selected(request('status_fu') === 'promise_transfer')>Promise Transfer</option>
                                    <option value="closing" @selected(request('status_fu') === 'closing')>Closing</option>
                                    <option value="rejected" @selected(request('status_fu') === 'rejected')>Rejected</option>
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
                                <a href="{{ route('leads.index') }}"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reset
                                </a>
                            </div>
                            {{-- Export --}}
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" x-on:click="open = !open"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Export
                                </button>
                                <div x-show="open" x-on:click.outside="open = false"
                                    class="absolute right-0 mt-1.5 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50">
                                    <a href="{{ route('leads.export.csv', request()->query()) }}" target="_blank"
                                        class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Export CSV
                                    </a>
                                    <a href="{{ route('leads.export.pdf', request()->query()) }}" target="_blank"
                                        class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 hidden md:block overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Order</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Handler</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Funnel</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Payment</th>
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
                                        @switch($lead->status_fu) @case('new') bg-slate-100 text-slate-700 @case('chatted') bg-blue-50 text-blue-700 @case('replied') bg-indigo-50 text-indigo-700 @case('interested') bg-amber-50 text-amber-700 @case('closing') bg-emerald-50 text-emerald-700 @case('rejected') bg-rose-50 text-rose-700 @default bg-orange-50 text-orange-700 @endswitch
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
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                        @if(in_array($lead->financial_status, ['paid', 'lunas'])) bg-emerald-50 text-emerald-700 @else bg-rose-50 text-rose-700 @endif
                                    ">
                                        @if(in_array($lead->financial_status, ['paid', 'lunas']))
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                        {{ ucfirst($lead->financial_status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right font-mono text-sm font-medium text-slate-900">Rp {{ number_format($lead->total_value, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('leads.show', $lead) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                        Detail
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p>Tidak ada data lead ditemukan.</p>
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
                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 transition-all"
                                    title="Chat WhatsApp">
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
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-medium
                                @if(in_array($lead->financial_status, ['paid', 'lunas'])) bg-emerald-50 text-emerald-700 @else bg-rose-50 text-rose-700 @endif
                            ">
                                @if(in_array($lead->financial_status, ['paid', 'lunas']))
                                    Paid
                                @else
                                    Unpaid
                                @endif
                            </span>
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
                            @if($lead->size)
                            <span class="text-slate-400">size {{ $lead->size }}</span>
                            @endif
                            <span class="text-slate-400">{{ $lead->timestamp->format('d M') }}</span>
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
                    <p class="text-slate-400 text-sm mt-1">Belum ada lead ditemukan.</p>
                </div>
                @endforelse

                {{-- Mobile pagination --}}
                @if($leads->hasPages())
                <div class="pt-2">
                    {{ $leads->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
