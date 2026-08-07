<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Handler;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    public function index(Request $request)
    {
        $query = Lead::with(['customer', 'handler']);

        // Filter by handler (CS can only see their own leads)
        $user = $request->user();
        if ($user->isCs() && $user->handler) {
            $query->byHandler($user->handler->id);
        } elseif ($request->filled('handler_id')) {
            $query->byHandler($request->handler_id);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->byDateRange(
                $request->start_date,
                $request->end_date ?? now()->toDateString()
            );
        }

        // Status filter
        if ($request->filled('status_fu')) {
            $query->where('status_fu', $request->status_fu);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $leads = $query->orderBy('timestamp', 'desc')->paginate(50);

        $handlers = Handler::where('is_active', true)->orderBy('name')->get();

        return view('leads.index', compact('leads', 'handlers'));
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request->user(), $lead);
        $lead->load(['customer', 'handler', 'histories.user']);
        return view('leads.show', compact('lead'));
    }

    public function followUpIndex(Request $request)
    {
        $user = $request->user();

        $query = Lead::with(['customer', 'handler', 'branch'])
            ->followUpRequired();

        // CS hanya lihat lead wajib follow-up miliknya
        if ($user->isCs()) {
            $handler = $user->handler;
            if ($handler) {
                $query->byHandler($handler->id);
            }
        } else {
            if ($request->filled('handler_id')) {
                $query->byHandler($request->handler_id);
            }
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('status_fu')) {
                $query->where('status_fu', $request->status_fu);
            }
            if ($request->filled('start_date')) {
                $query->byDateRange(
                    $request->start_date,
                    $request->end_date ?? now()->toDateString()
                );
            }
        }

        // Pending di atas, lalu terbaru
        $query->orderByRaw('CASE WHEN follow_up_status = \'pending\' THEN 0 ELSE 1 END')
            ->orderBy('timestamp', 'desc');

        $leads = $query->paginate(50)->withQueryString();

        $handlers = Handler::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('leads.follow-up', compact('leads', 'handlers', 'branches'));
    }

    public function toggleFollowUp(Request $request, Lead $lead)
    {
        $user = $request->user();
        abort_unless($user->isManager() || $user->isAdmin(), 403, 'Hanya manager/admin yang bisa menandai wajib follow-up.');

        $validated = $request->validate([
            'follow_up_required' => 'required|boolean',
        ]);

        $this->leadService->markFollowUp($lead, (bool) $validated['follow_up_required'], $user->id);

        return redirect()->back()->with('success', $validated['follow_up_required']
            ? 'Lead ditandai wajib follow-up.'
            : 'Penanda wajib follow-up dihapus.');
    }

    public function completeFollowUp(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request->user(), $lead);

        $this->leadService->completeFollowUp($lead, $request->user()->id);

        return redirect()->back()->with('success', 'Follow-up ditandai selesai.');
    }

    public function bulkReassign(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isManager() || $user->isAdmin(), 403, 'Hanya manager/admin yang bisa memindahkan lead.');

        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'integer',
            'handler_id' => 'required|exists:handlers,id',
        ]);

        $handler = Handler::findOrFail($validated['handler_id']);
        $count = $this->leadService->bulkReassign($validated['lead_ids'], $handler->id, $user->id);

        return redirect()->back()->with('success', "{$count} lead dipindahkan ke {$handler->name}.");
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request->user(), $lead);
        $validated = $request->validate([
            'status_fu' => 'required|string|in:' . implode(',', Lead::STATUSES),
            'notes' => 'nullable|string',
            'size' => 'nullable|string|max:5',
        ]);

        $lead = $this->leadService->updateStatus(
            $lead,
            $validated,
            $request->user()->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status lead berhasil diupdate',
                'lead' => $lead->load(['customer', 'handler']),
            ]);
        }

        return redirect()->back()->with('success', 'Status lead berhasil diupdate');
    }

    private function authorizeLeadAccess($user, Lead $lead): void
    {
        if ($user->isCs()) {
            $handler = $user->handler;
            if (!$handler || $lead->handler_id !== $handler->id) {
                abort(403, 'Anda tidak memiliki akses ke lead ini.');
            }
        }
    }
}
