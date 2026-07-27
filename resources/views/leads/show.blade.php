<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Detail Lead</h2>
                <p class="text-sm text-slate-500 mt-0.5">Order: {{ $lead->order_id }}</p>
            </div>
            <a href="{{ route('leads.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Lead Info Card --}}
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="font-semibold text-slate-900">Informasi Lead</h3>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Order ID</dt>
                                    <dd class="mt-1 font-mono text-sm text-slate-900 font-medium">{{ $lead->order_id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Tanggal Order</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->timestamp->format('d M Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</dt>
                                    <dd class="mt-1 text-sm text-slate-900 font-medium flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $lead->customer?->name ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">No. WhatsApp</dt>
                                    <dd class="mt-1 text-sm text-slate-900 font-mono">{{ $lead->customer?->phone ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Handler (CS)</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->handler?->name ?? 'Unassigned' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total</dt>
                                    <dd class="mt-1 text-lg font-bold text-slate-900">Rp {{ number_format($lead->total_value, 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Size</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->size ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Lead Type</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                            @if($lead->lead_type === 'repeat') bg-purple-50 text-purple-700
                                            @else bg-emerald-50 text-emerald-700 @endif
                                        ">
                                            @if($lead->lead_type === 'repeat') 🔄 @else 🆕 @endif
                                            {{ ucfirst($lead->lead_type) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Payment</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium
                                            @if(in_array($lead->financial_status, ['paid', 'lunas'])) bg-emerald-50 text-emerald-700
                                            @else bg-rose-50 text-rose-700 @endif
                                        ">
                                            @if(in_array($lead->financial_status, ['paid', 'lunas'])) 💰 @else 💳 @endif
                                            {{ ucfirst($lead->financial_status) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Response Time</dt>
                                    <dd class="mt-1 text-sm">
                                        @if($lead->response_time_minutes)
                                            <span class="text-slate-900">{{ $lead->response_time_minutes }} menit</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Funnel Stage</dt>
                                    <dd class="mt-1">
                                        <div class="flex gap-2">
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium
                                                {{ $lead->funnel_stage === 'cold' ? 'bg-sky-100 text-sky-800 ring-2 ring-sky-500/30' : 'bg-slate-50 text-slate-400' }}">
                                                Cold
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium
                                                {{ $lead->funnel_stage === 'warm' ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-500/30' : 'bg-slate-50 text-slate-400' }}">
                                                Warm
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-medium
                                                {{ $lead->funnel_stage === 'hot' ? 'bg-rose-100 text-rose-800 ring-2 ring-rose-500/30' : 'bg-slate-50 text-slate-400' }}">
                                                Hot
                                            </span>
                                        </div>
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Status Follow-up</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
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
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Notes</dt>
                                    <dd class="mt-1 text-sm text-slate-700 bg-slate-50 rounded-lg p-3 border border-slate-100">{{ $lead->notes ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- UTM Info Card --}}
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <h3 class="font-semibold text-slate-900">UTM & Traffic</h3>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">Traffic Type</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                            @if($lead->traffic_type === 'ads') bg-rose-50 text-rose-700
                                            @elseif($lead->traffic_type === 'organik') bg-emerald-50 text-emerald-700
                                            @else bg-slate-100 text-slate-700 @endif
                                        ">{{ ucfirst($lead->traffic_type ?? 'direct') }}</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">UTM Source</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->utm_source ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">UTM Medium</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->utm_medium ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">UTM Campaign</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->utm_campaign ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wider">UTM Content</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $lead->utm_content ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- History Timeline --}}
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="font-semibold text-slate-900">Riwayat Perubahan</h3>
                            <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $lead->histories->count() }} perubahan</span>
                        </div>
                        <div class="p-6">
                            @forelse($lead->histories as $history)
                            <div class="flex gap-4 pb-4 {{ !$loop->last ? 'border-l-2 border-slate-100 ml-2 pl-4' : 'ml-2 pl-4' }}">
                                <div class="flex-shrink-0 w-2 h-2 rounded-full mt-1.5 -ml-[17px] 
                                    @if($history->field_changed === 'status_fu') bg-indigo-400
                                    @elseif($history->field_changed === 'funnel_stage') bg-amber-400
                                    @else bg-slate-400 @endif
                                    ring-2 ring-white">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-semibold text-slate-700 uppercase">{{ str_replace('_', ' ', $history->field_changed) }}</span>
                                        <span class="text-[11px] text-slate-400">{{ $history->created_at->format('d M H:i') }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        <span class="line-through text-rose-500">{{ $history->old_value ?? '-' }}</span>
                                        <span class="mx-1.5 text-slate-300">→</span>
                                        <span class="text-emerald-600 font-medium">{{ $history->new_value }}</span>
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">oleh {{ $history->user?->name ?? 'System' }}</div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium">Belum ada perubahan</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Update Status Card --}}
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <h3 class="font-semibold text-slate-900">Update Status</h3>
                        </div>
                        <div class="p-5">
                            @livewire('update-lead-status', ['lead' => $lead], key($lead->id))
                        </div>
                    </div>

                    {{-- WhatsApp Action --}}
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <h3 class="font-semibold text-slate-900">Aksi Cepat</h3>
                        </div>
                        <div class="p-5">
                            <a href="https://wa.me/{{ $lead->customer?->phone }}" target="_blank"
                                id="wa-button-{{ $lead->id }}"
                                data-wa-lead-id="{{ $lead->id }}"
                                class="wa-chat-button w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:from-emerald-600 hover:to-emerald-700 transition-all duration-200 shadow-sm shadow-emerald-200 active:scale-[0.98]">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Chat WhatsApp
                                <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- WA Callback Notes: Page Visibility API --}}
    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('showWaNotesModal', (data) => {
                setTimeout(() => {
                    document.getElementById('wa-callback-notes')?.focus();
                }, 300);
            });
        });

        (function() {
            document.addEventListener('click', function(e) {
                const waBtn = e.target.closest('.wa-chat-button');
                if (waBtn) {
                    const leadId = waBtn.dataset.waLeadId;
                    if (leadId) {
                        sessionStorage.setItem('wa_callback_lead_id', leadId);
                        sessionStorage.setItem('wa_callback_timestamp', Date.now().toString());
                    }
                }
            });

            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    const storedLeadId = sessionStorage.getItem('wa_callback_lead_id');
                    const storedTimestamp = sessionStorage.getItem('wa_callback_timestamp');
                    if (storedLeadId) {
                        const elapsed = Date.now() - parseInt(storedTimestamp || '0');
                        if (elapsed >= 3000) {
                            sessionStorage.removeItem('wa_callback_lead_id');
                            sessionStorage.removeItem('wa_callback_timestamp');
                            Livewire.dispatch('showWaNotesModal', { leadId: parseInt(storedLeadId) });
                        }
                    }
                }
            });
        })();
    </script>
    @endpush
</x-app-layout>
