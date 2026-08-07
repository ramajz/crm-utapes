# LAPORAN TASK BACKEND

> Tanggal: 2026-08-07
> Task: template-followup-whatsapp
> Repo: crm-utapes
> Branch: feat/lead-auto-assign
> Commit: 31a257a

1) Deskripsi Perubahan

Menambahkan fitur **Template Pesan WhatsApp** dan tombol **Wajib FU** di daftar lead.

- **Template Pesan WhatsApp**: Halaman detail lead kini memiliki panel "Template Pesan WhatsApp" yang menggantikan tombol Chat WhatsApp tunggal. Panel menampilkan daftar template pesan yang bisa dipilih CS, masing-masing dengan preview pesan yang sudah terisi data lead. Klik "Kirim WA" membuka `wa.me/{nomor}?text={pesan}` dengan pesan terisi otomatis.
- **Variabel template**: `{nama}`, `{order_id}`, `{size}`, `{total}`, `{handler}` diganti otomatis dengan data lead (method `Lead::renderTemplate()`).
- **Tombol Wajib FU di daftar lead**: Manager/Admin bisa menandai lead sebagai wajib follow-up langsung dari halaman daftar lead (toggle tombol "Wajib FU" di kolom aksi), tanpa harus buka halaman follow-up. CS tidak melihat tombol ini.
- Flow notes callback (catat hasil chat setelah balik dari WA) tetap berjalan — class `wa-chat-button` dan `data-wa-lead-id` dipertahankan.

2) Tujuan dan Manfaat

- CS menghemat waktu mengetik pesan follow-up — tinggal pilih template yang sesuai.
- Pesan otomatis personal dengan nama customer (dan data order), sehingga follow-up lebih efektif.
- Manager bisa menandai lead wajib FU lebih cepat dari daftar lead.
- Template mudah dikelola lewat config file tanpa ubah kode view.

3) Daftar File yang Diubah

- `config/whatsapp_templates.php` (baru — 5 template)
- `app/Models/Lead.php` (getTemplatePlaceholders, renderTemplate)
- `resources/views/leads/show.blade.php` (panel template WhatsApp)
- `resources/views/leads/index.blade.php` (tombol Wajib FU di kolom aksi + card mobile)
- `tests/Feature/TemplateWaTest.php` (baru, 4 test)

4) Snapshot Kode (Before → After)

4.1 Helper render template

File: `app/Models/Lead.php`

After:
```
public function getTemplatePlaceholders(): array
{
    return [
        '{nama}' => $this->customer?->name ?? 'Customer',
        '{order_id}' => $this->order_id,
        '{size}' => $this->size ?? '',
        '{total}' => $this->formatted_total,
        '{handler}' => $this->handler?->name ?? 'CS',
    ];
}

public function renderTemplate(string $message): string
{
    $placeholders = $this->getTemplatePlaceholders();
    return str_replace(array_keys($placeholders), array_values($placeholders), $message);
}
```

4.2 Panel template di detail lead

File: `resources/views/leads/show.blade.php`

After:
```
@forelse($waTemplates as $template)
    @php
        $rendered = $lead->renderTemplate($template['message']);
        $waUrl = 'https://wa.me/' . ($lead->customer?->wa_number ?? '') . '?text=' . rawurlencode($rendered);
    @endphp
    ... badge kategori + preview pesan + tombol "Kirim WA" (class wa-chat-button, data-wa-lead-id) ...
@empty
    ... fallback tombol Chat WhatsApp tanpa template ...
@endforelse
```

5) Verifikasi UAT (User Acceptance Test)

| # | Tes | Hasil | Detail |
|---|-----|-------|--------|
| 1 | `php artisan test --filter=TemplateWaTest` | ✅ | 4 test pass (9 assertions) |
| 2 | `php artisan test` (full suite) | ✅ | 52 test pass (150 assertions), tanpa regresi |
| 3 | render template ganti placeholder | ✅ | `{nama}` → Budi Santoso, `{total}` → Rp 575.000, dll |
| 4 | placeholder fallback data kosong | ✅ | size kosong → string kosong, handler null → "CS" |
| 5 | Halaman detail menampilkan panel template | ✅ | CS melihat "Template Pesan WhatsApp" + nama template + tombol Kirim WA |
| 6 | Link WA berisi pesan ter-render | ✅ | `wa.me/6281234567890?text=...Budi%20Santoso...` |
| 7 | Manager lihat tombol Wajib FU di index | ✅ | assertSee 'Wajib FU' |
| 8 | CS tidak lihat tombol Wajib FU | ✅ | assertDontSee toggle-follow-up |
