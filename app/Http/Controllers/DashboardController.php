<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

            // Closing & revenue dihitung berdasarkan kapan lead menjadi paid (last_update_at),
            // bukan tanggal lead masuk. Jadi lead Juni yang dibayar Juli ikut terhitung di Juli.
            $closingQuery = Lead::query()
                ->where('financial_status', 'paid')
                ->whereBetween('last_update_at', [$startDate, Carbon::parse($endDate)->endOfDay()]);

            $stats = [
                'total' => (clone $query)->count(),
                'followed_up' => (clone $query)->followedUp()->count(),
                'not_followed_up' => (clone $query)->notFollowedUp()->count(),
                'closing' => (clone $closingQuery)->count(),
                'total_revenue' => (clone $closingQuery)->sum('total_value'),
                'conversion_rate' => 0,
            ];

            $total = $stats['total'];
            $stats['conversion_rate'] = $total > 0 ? round(($stats['closing'] / $total) * 100, 2) : 0;

            // Average response time (all handlers) — computed in PHP for DB compatibility.
            // Basis: timestamp (lead masuk) → first_replied_at, atau last_update_at
            // (proxy waktu respon) untuk data migrasi yang tidak punya first_replied_at.
            $repliedLeads = (clone $query)
                ->where('status_fu', '!=', 'new')
                ->whereNotNull('last_update_at')
                ->select('timestamp', 'last_update_at', 'first_replied_at')
                ->get();
            $totalMinutes = 0;
            $count = $repliedLeads->count();
            foreach ($repliedLeads as $l) {
                $end = $l->first_replied_at ?? $l->last_update_at;
                $totalMinutes += $l->timestamp->diffInMinutes($end);
            }
            $stats['avg_response_time_minutes'] = $count > 0 ? round($totalMinutes / $count) : null;
        }

        // Fetch aggregation data for charts
        // Total leads dikelompokkan per tanggal masuk (timestamp),
        // closing dikelompokkan per tanggal paid (last_update_at).
        $leadsDaily = (clone $query)
            ->select(DB::raw("date(timestamp) as date_val"), DB::raw("COUNT(*) as total_leads"))
            ->groupBy(DB::raw("date(timestamp)"))
            ->orderBy(DB::raw("date(timestamp)"))
            ->get()
            ->keyBy('date_val');

        $closingDaily = Lead::query()
            ->where('financial_status', 'paid')
            ->whereBetween('last_update_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->select(DB::raw("date(last_update_at) as date_val"), DB::raw("COUNT(*) as total_closing"))
            ->groupBy(DB::raw("date(last_update_at)"))
            ->get()
            ->keyBy('date_val');

        $dailyData = collect();
        $day = Carbon::parse($startDate)->startOfDay();
        $lastDay = Carbon::parse($endDate)->endOfDay();
        while ($day->lte($lastDay)) {
            $key = $day->toDateString();
            $dailyData->push([
                'date_val' => $key,
                'total_leads' => (int) ($leadsDaily[$key]->total_leads ?? 0),
                'total_closing' => (int) ($closingDaily[$key]->total_closing ?? 0),
            ]);
            $day->addDay();
        }

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
