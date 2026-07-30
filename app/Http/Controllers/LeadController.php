<?php

namespace App\Http\Controllers;

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

        return view('leads.index', compact('leads'));
    }

    public function show(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($request->user(), $lead);
        $lead->load(['customer', 'handler', 'histories.user']);
        return view('leads.show', compact('lead'));
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
