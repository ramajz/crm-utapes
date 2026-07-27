<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $handler = $user->handler;

        // Default date range = today
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        if ($user->isCs() && $handler) {
            // CS Personal Dashboard
            $stats = $this->leadService->getHandlerStats(
                $handler->id,
                $startDate,
                $endDate
            );

            $leads = Lead::byHandler($handler->id)
                ->byDateRange($startDate, $endDate)
                ->with(['customer', 'handler'])
                ->orderBy('timestamp', 'desc')
                ->paginate(50);

            return view('dashboard', compact('stats', 'leads', 'startDate', 'endDate'));
        }

        // Admin/Manager Dashboard
        $query = Lead::query()->byDateRange($startDate, $endDate);

        $stats = [
            'total' => (clone $query)->count(),
            'followed_up' => (clone $query)->followedUp()->count(),
            'not_followed_up' => (clone $query)->notFollowedUp()->count(),
            'closing' => (clone $query)->closing()->count(),
            'total_revenue' => (clone $query)->sum('total_value'),
            'conversion_rate' => 0,
        ];

        $total = $stats['total'];
        $stats['conversion_rate'] = $total > 0 ? round(($stats['closing'] / $total) * 100, 1) : 0;

        // Average response time (all handlers)
        $avgResponseTime = (clone $query)
            ->whereNotNull('first_replied_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_replied_at)) as avg_time'))
            ->value('avg_time');
        $stats['avg_response_time_minutes'] = $avgResponseTime ? round($avgResponseTime) : null;

        $leads = $query->with(['customer', 'handler'])
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return view('dashboard', compact('stats', 'leads', 'startDate', 'endDate'));
    }
}
