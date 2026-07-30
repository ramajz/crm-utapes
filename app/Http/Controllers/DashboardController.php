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
            $query = Lead::byHandler($handler->id)->byDateRange($startDate, $endDate);
            $stats = $this->leadService->getHandlerStats(
                $handler->id,
                $startDate,
                $endDate
            );
        } else {
            // Admin/Manager Dashboard
            $query = Lead::query()->byDateRange($startDate, $endDate);

            $stats = [
                'total' => (clone $query)->count(),
                'followed_up' => (clone $query)->followedUp()->count(),
                'not_followed_up' => (clone $query)->notFollowedUp()->count(),
                'closing' => (clone $query)->closingStatus()->count(),
                'total_revenue' => (clone $query)->sum('total_value'),
                'conversion_rate' => 0,
            ];

            $total = $stats['total'];
            $stats['conversion_rate'] = $total > 0 ? round(($stats['closing'] / $total) * 100, 1) : 0;

            // Average response time (all handlers) — computed in PHP for DB compatibility
            $repliedLeads = (clone $query)
                ->whereNotNull('first_replied_at')
                ->select('created_at', 'first_replied_at')
                ->get();
            $totalMinutes = 0;
            $count = $repliedLeads->count();
            foreach ($repliedLeads as $l) {
                $totalMinutes += $l->created_at->diffInMinutes($l->first_replied_at);
            }
            $stats['avg_response_time_minutes'] = $count > 0 ? round($totalMinutes / $count) : null;
        }

        // Fetch aggregation data for charts
        $dailyData = (clone $query)
            ->select(DB::raw("date(timestamp) as date_val"), DB::raw("COUNT(*) as total_leads"), DB::raw("COUNT(CASE WHEN status_fu = 'closing' THEN 1 END) as total_closing"))
            ->groupBy(DB::raw("date(timestamp)"))
            ->orderBy(DB::raw("date(timestamp)"))
            ->get();

        $funnelData = (clone $query)
            ->select('funnel_stage', DB::raw('count(*) as count'))
            ->groupBy('funnel_stage')
            ->get();

        $statusData = (clone $query)
            ->select('status_fu', DB::raw('count(*) as count'))
            ->groupBy('status_fu')
            ->get();

        $trafficData = (clone $query)
            ->select('traffic_type', DB::raw('count(*) as count'))
            ->groupBy('traffic_type')
            ->get();

        return view('dashboard', compact('stats', 'startDate', 'endDate', 'dailyData', 'funnelData', 'statusData', 'trafficData'));
    }
}
