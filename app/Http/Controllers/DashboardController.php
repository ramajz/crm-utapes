<?php

namespace App\Http\Controllers;

use App\Models\Handler;
use App\Models\Lead;
use App\Models\Order;
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

        $handlerStats = collect();

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

            // Closing & revenue dihitung berdasarkan kapan order dibayar (orders.paid_time,
            // sumber kebenaran Scalev), tanpa filter status final — selaras rekap Python/NocoBase.
            $closingQuery = Order::query()
                ->whereBetween('paid_time', [$startDate, Carbon::parse($endDate)->endOfDay()]);

            $stats = [
                'total' => (clone $query)->count(),
                'followed_up' => (clone $query)->followedUp()->count(),
                'not_followed_up' => (clone $query)->notFollowedUp()->count(),
                'closing' => (clone $closingQuery)->count(),
                'total_revenue' => (clone $closingQuery)->sum('gross_revenue'),
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

            // Performa per CS (hanya untuk admin/manager)
            $handlerStats = $this->buildHandlerStats($startDate, $endDate);
        }

        // Fetch aggregation data for charts
        // Total leads dikelompokkan per tanggal masuk (timestamp),
        // closing dikelompokkan per tanggal paid (orders.paid_time).
        $leadsDaily = (clone $query)
            ->select(DB::raw("date(timestamp) as date_val"), DB::raw("COUNT(*) as total_leads"))
            ->groupBy(DB::raw("date(timestamp)"))
            ->orderBy(DB::raw("date(timestamp)"))
            ->get()
            ->keyBy('date_val');

        $closingDaily = Order::query()
            ->whereBetween('paid_time', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->select(DB::raw("date(paid_time) as date_val"), DB::raw("COUNT(*) as total_closing"))
            ->groupBy(DB::raw("date(paid_time)"))
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

        return view('dashboard', compact('stats', 'startDate', 'endDate', 'dailyData', 'funnelData', 'statusData', 'trafficData', 'handlerStats'));
    }

    private function buildHandlerStats(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        $endOfDay = Carbon::parse($endDate)->endOfDay();

        // Leads masuk per handler (by timestamp)
        $leadAgg = Lead::whereBetween('timestamp', [$startDate, $endOfDay])
            ->select(
                'handler_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status_fu <> 'new' THEN 1 ELSE 0 END) as followed"),
                DB::raw("SUM(CASE WHEN status_fu = 'new' THEN 1 ELSE 0 END) as not_followed")
            )
            ->groupBy('handler_id')
            ->get()
            ->keyBy('handler_id');

        // Closing & revenue per handler berdasarkan orders.paid_time (Scalev)
        $closeAgg = Order::whereBetween('paid_time', [$startDate, $endOfDay])
            ->whereNotNull('handler_id')
            ->select('handler_id', DB::raw('COUNT(*) as closing'), DB::raw('SUM(gross_revenue) as revenue'))
            ->groupBy('handler_id')
            ->get()
            ->keyBy('handler_id');

        // Rata-rata response time per handler — dihitung di PHP
        $responseByHandler = [];
        Lead::whereBetween('timestamp', [$startDate, $endOfDay])
            ->where('status_fu', '!=', 'new')
            ->whereNotNull('last_update_at')
            ->whereNotNull('handler_id')
            ->get(['handler_id', 'timestamp', 'last_update_at', 'first_replied_at'])
            ->each(function ($l) use (&$responseByHandler) {
                $end = $l->first_replied_at ?? $l->last_update_at;
                $minutes = $l->timestamp->diffInMinutes($end);
                $responseByHandler[$l->handler_id]['sum'] = ($responseByHandler[$l->handler_id]['sum'] ?? 0) + $minutes;
                $responseByHandler[$l->handler_id]['count'] = ($responseByHandler[$l->handler_id]['count'] ?? 0) + 1;
            });

        // Leads tanpa CS (handler_id NULL) — agar jumlah baris konsisten dengan statistik dashboard
        $unassignedLead = Lead::whereBetween('timestamp', [$startDate, $endOfDay])
            ->whereNull('handler_id')
            ->selectRaw(
                'COUNT(*) as total, '
                ."SUM(CASE WHEN status_fu <> 'new' THEN 1 ELSE 0 END) as followed, "
                ."SUM(CASE WHEN status_fu = 'new' THEN 1 ELSE 0 END) as not_followed"
            )
            ->first();

        $unassignedClose = Order::whereBetween('paid_time', [$startDate, $endOfDay])
            ->whereNull('handler_id')
            ->selectRaw('COUNT(*) as closing, SUM(gross_revenue) as revenue')
            ->first();

        $handlerIds = $leadAgg->keys()->merge($closeAgg->keys())->unique();

        $rows = Handler::whereIn('id', $handlerIds)->orderBy('name')->get()
            ->map(function ($handler) use ($leadAgg, $closeAgg, $responseByHandler) {
                $lead = $leadAgg[$handler->id] ?? null;
                $close = $closeAgg[$handler->id] ?? null;
                $total = (int) ($lead->total ?? 0);
                $closing = (int) ($close->closing ?? 0);
                $resp = $responseByHandler[$handler->id] ?? null;

                return [
                    'name' => $handler->name,
                    'initial' => strtoupper(substr($handler->name, 0, 1)),
                    'total' => $total,
                    'followed_up' => (int) ($lead->followed ?? 0),
                    'not_followed_up' => (int) ($lead->not_followed ?? 0),
                    'closing' => $closing,
                    'revenue' => (int) ($close->revenue ?? 0),
                    'conversion_rate' => $total > 0 ? round(($closing / $total) * 100, 2) : 0,
                    'avg_response_time_minutes' => ($resp['count'] ?? 0) > 0 ? round($resp['sum'] / $resp['count']) : null,
                ];
            });

        if ((int) $unassignedLead->total > 0 || (int) $unassignedClose->closing > 0) {
            $rows->push([
                'name' => 'Tanpa CS (unassigned)',
                'initial' => '?',
                'total' => (int) $unassignedLead->total,
                'followed_up' => (int) $unassignedLead->followed,
                'not_followed_up' => (int) $unassignedLead->not_followed,
                'closing' => (int) $unassignedClose->closing,
                'revenue' => (int) $unassignedClose->revenue,
                'conversion_rate' => (int) $unassignedLead->total > 0 ? round(((int) $unassignedClose->closing / (int) $unassignedLead->total) * 100, 2) : 0,
                'avg_response_time_minutes' => null,
            ]);
        }

        return $rows
            ->sortByDesc('closing')
            ->values();
    }
}
