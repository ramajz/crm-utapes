<div>
    {{-- WA Callback Banner --}}
    @if($waCallback)
    <div class="mb-5 p-4 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-emerald-800 text-sm">Kembali dari WhatsApp!</p>
                    <span class="animate-pulse flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                </div>
                <p class="text-emerald-600 text-xs mt-1">Catat hasil chat dengan <strong>{{ $lead->customer?->name ?? 'customer' }}</strong> di bawah ini:</p>
            </div>
            <button type="button" wire:click="clearWaCallback"
                class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-200 text-emerald-600 hover:bg-emerald-300 hover:text-emerald-700 flex items-center justify-center transition-colors"
                title="Tutup">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- Status FU --}}
    <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Status Follow-up</label>
        <select wire:model.live="statusFu"
            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm transition-all">
            <option value="chatted">Chatted</option>
            <option value="replied">Replied</option>
            <option value="interested">Interested</option>
            <option value="nunggu_gajian">Nunggu Gajian</option>
            <option value="promise_transfer">Promise Transfer</option>
            <option value="closing">Closing</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    {{-- Funnel — Auto-calculated --}}
    <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
            Funnel Stage <span class="text-[10px] font-normal text-slate-400 normal-case">(otomatis)</span>
        </label>
        <div class="flex gap-2">
            <span class="flex-1 text-center px-2 py-2 rounded-lg text-xs font-medium transition-all duration-200
                {{ $funnelStage === 'cold' ? 'bg-sky-100 text-sky-800 ring-2 ring-sky-500/30 scale-105 shadow-sm' : 'bg-slate-50 text-slate-400' }}">
                Cold
            </span>
            <span class="flex-1 text-center px-2 py-2 rounded-lg text-xs font-medium transition-all duration-200
                {{ $funnelStage === 'warm' ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-500/30 scale-105 shadow-sm' : 'bg-slate-50 text-slate-400' }}">
                Warm
            </span>
            <span class="flex-1 text-center px-2 py-2 rounded-lg text-xs font-medium transition-all duration-200
                {{ $funnelStage === 'hot' ? 'bg-rose-100 text-rose-800 ring-2 ring-rose-500/30 scale-105 shadow-sm' : 'bg-slate-50 text-slate-400' }}">
                Hot
            </span>
        </div>
    </div>

    {{-- Auto-payment notice --}}
    @if($statusFu === 'closing')
    <div class="mb-4 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-lg text-sm text-emerald-700 flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Status <strong>Closing</strong> akan otomatis mengubah payment menjadi <strong>Paid</strong></span>
    </div>
    @endif

    {{-- Size --}}
    <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Size</label>
        <select wire:model="size"
            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
            <option value="">- Pilih Size -</option>
            @foreach(range(35, 45) as $s)
            <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
        </select>
    </div>

    {{-- Notes --}}
    <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
            Notes
            @if($waCallback)
            <span class="text-[10px] text-emerald-600 font-normal normal-case animate-pulse">(Tulis catatan chat)</span>
            @else
            <span class="text-[10px] text-slate-400 font-normal normal-case">(Klik Chat WhatsApp dulu untuk aktif)</span>
            @endif
        </label>
        <textarea wire:model="notes" rows="3"
            id="wa-callback-notes"
            @disabled(!$waCallback)
            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm transition-all resize-none {{ $waCallback ? 'border-emerald-400 ring-1 ring-emerald-400/30 bg-emerald-50/30' : 'bg-slate-50 text-slate-400 cursor-not-allowed' }}"
            placeholder="{{ $waCallback ? 'Hasil follow-up...' : 'Klik Chat WhatsApp terlebih dahulu' }}"></textarea>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
        <button type="button" wire:click="clearWaCallback"
            class="px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200">
            Batal
        </button>
        <button type="button" wire:click="save" wire:loading.attr="disabled"
            class="px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 transition-all duration-200 shadow-sm shadow-indigo-200 active:scale-[0.98]">
            <span wire:loading.remove class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan
            </span>
            <span wire:loading class="flex items-center gap-1.5">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Menyimpan...
            </span>
        </button>
    </div>
</div>
