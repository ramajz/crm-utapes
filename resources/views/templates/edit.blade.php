<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Edit Template</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ $template->name }}</p>
            </div>
            <a href="{{ route('templates.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($errors->any())
            <div class="mb-4 px-4 py-3 rounded-lg bg-rose-50 border border-rose-200 text-sm text-rose-700">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('templates.update', $template) }}" class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Nama Template</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" required maxlength="100"
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Kategori</label>
                    <select name="category" required
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                        @foreach(\App\Models\WhatsAppTemplate::CATEGORIES as $category)
                        <option value="{{ $category }}" @selected(old('category', $template->category) === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Isi Pesan</label>
                    <textarea name="message" rows="5" required
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm resize-none">{{ old('message', $template->message) }}</textarea>
                    @error('message') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-slate-400">
                        Variabel: <code class="px-1 py-0.5 bg-slate-100 rounded">{nama}</code>
                        <code class="px-1 py-0.5 bg-slate-100 rounded">{order_id}</code>
                        <code class="px-1 py-0.5 bg-slate-100 rounded">{size}</code>
                        <code class="px-1 py-0.5 bg-slate-100 rounded">{total}</code>
                        <code class="px-1 py-0.5 bg-slate-100 rounded">{handler}</code>
                        — diganti otomatis dengan data lead.
                    </p>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20"
                            @checked(old('is_active', $template->is_active))>
                        Template aktif (muncul untuk CS)
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                    <a href="{{ route('templates.index') }}"
                        class="px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all duration-200">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 shadow-sm shadow-indigo-200">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
