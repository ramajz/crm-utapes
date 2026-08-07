<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;

class WhatsAppTemplateController extends Controller
{
    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->isManager() || $request->user()->isAdmin(), 403, 'Hanya manager/admin yang bisa mengelola template.');
    }

    public function index(Request $request)
    {
        $this->authorizeManage($request);

        $templates = WhatsAppTemplate::orderBy('category')->orderBy('name')->paginate(20);

        return view('templates.index', compact('templates'));
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);

        return view('templates.create');
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:cold,warm,hot',
            'message' => 'required|string',
        ]);

        WhatsAppTemplate::create($validated + ['is_active' => true]);

        return redirect()->route('templates.index')->with('success', 'Template berhasil ditambahkan.');
    }

    public function edit(Request $request, WhatsAppTemplate $template)
    {
        $this->authorizeManage($request);

        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, WhatsAppTemplate $template)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:cold,warm,hot',
            'message' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('templates.index')->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(Request $request, WhatsAppTemplate $template)
    {
        $this->authorizeManage($request);

        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template dihapus.');
    }
}
