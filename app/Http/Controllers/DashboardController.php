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
            $handlerStats = $this->buildHandlerStats($startDate, $endDate, $handler->id);
            $row = $handlerStats->firstWhere('handler_id', $handler->id);
            $stats = [
                'total' => $row['total'] ?? 0,
                'followed_up' => $row['followed_up'] ?? 0,
                'not_followed_up' => $row['not_followed_up'] ?? 0,
                'closing' => $row['closing'] ?? 0,
                'total_revenue' => $row['revenue'] ?? 0,
                'conversion_rate' => $row['conversion_rate'] ?? 0,
                'avg_response_time_minutes' => $row['avg_response_time_minutes'] ?? null,
            ];
        } else {
            // Admin/Manager Dashboard
            $query = Lead::query()->byDateRange($startDate, $endDate);

            // Closing & revenue dihitung berdasarkan kapan order dibayar (orders.paid_time,
            // sumber kebenaran Scalev), tanpa filter status final — selaras rekap Python/NocoBase.
            $closingQuery = Order::query()
                ->whereBetween('paid_time', [$startDate, Carbon::parse($endDate)->endOfDay()]);

            // Statistik total/followed_up/not_followed_up dalam satu pass (leads).
            $leadStats = (clone $query)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status_fu <> 'new' THEN 1 ELSE 0 END) as followed_up")
                ->selectRaw("SUM(CASE WHEN status_fu = 'new' THEN 1 ELSE 0 END) as not_followed_up")
                ->first();

            // Closing & revenue dalam satu pass (orders, sumber kebenaran Scalev).
            $closeStats = (clone $closingQuery)
                ->selectRaw('COUNT(*) as closing')
                ->selectRaw('COALESCE(SUM(gross_revenue), 0) as total_revenue')
                ->first();

            $stats = [
                'total' => (int) ($leadStats->total ?? 0),
                'followed_up' => (int) ($leadStats->followed_up ?? 0),
                'not_followed_up' => (int) ($leadStats->not_followed_up ?? 0),
                'closing' => (int) ($closeStats->closing ?? 0),
                'total_revenue' => (int) ($closeStats->total_revenue ?? 0),
                'conversion_rate' => 0,
            ];

            $total = $stats['total'];
            $stats['conversion_rate'] = $total > 0 ? round(($stats['closing'] / $total) * 100, 2) : 0;

            // Rata-rata response time (semua handler) — dihitung di SQL agar DB-compatible.
            // Basis: timestamp (lead masuk) → first_replied_at, atau last_update_at
            // (proxy waktu respon) untuk data migrasi yang tidak punya first_replied_at.
            $stats['avg_response_time_minutes'] = $this->avgResponseTimeMinutes(
                (clone $query)->where('status_fu', '!=', 'new')->whereNotNull('last_update_at')
            );

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

        // Distribusi funnel / status follow-up / traffic — satu pass: hasil union
        // dikelompokkan per kategori dan kolom asal, dibaca di PHP menjadi 3 collection.
        $breakdownData = (clone $query)
            ->select(
                DB::raw("'funnel' as chart"),
                'funnel_stage',
                DB::raw("NULL as status_fu"),
                DB::raw("NULL as traffic_type"),
                DB::raw("COUNT(*) as count")
            )
            ->groupBy(DB::raw("'funnel'"), 'funnel_stage')
            ->union(
                (clone $query)
                    ->select(
                        DB::raw("'status' as chart"),
                        DB::raw("NULL as funnel_stage"),
                        'status_fu',
                        DB::raw("NULL as traffic_type"),
                        DB::raw("COUNT(*) as count")
                    )
                    ->groupBy(DB::raw("'status'"), 'status_fu')
            )
            ->union(
                (clone $query)
                    ->select(
                        DB::raw("'traffic' as chart"),
                        DB::raw("NULL as funnel_stage"),
                        DB::raw("NULL as status_fu"),
                        'traffic_type',
                        DB::raw("COUNT(*) as count")
                    )
                    ->groupBy(DB::raw("'traffic'"), 'traffic_type')
            )
            ->get()
            ->groupBy('chart');

        $funnelData = $breakdownData->get('funnel', collect())
            ->map(fn ($row) => ['funnel_stage' => $row->funnel_stage, 'count' => (int) $row->count])
            ->values();
        $statusData = $breakdownData->get('status', collect())
            ->map(fn ($row) => ['status_fu' => $row->status_fu, 'count' => (int) $row->count])
            ->values();
        $trafficData = $breakdownData->get('traffic', collect())
            ->map(fn ($row) => ['traffic_type' => $row->traffic_type, 'count' => (int) $row->count])
            ->values();

        return view('dashboard', compact('stats', 'startDate', 'endDate', 'dailyData', 'funnelData', 'statusData', 'trafficData', 'handlerStats'));
    }

    /**
     * Rata-rata response time dalam menit (dibulatkan), dihitung via SQL:
     * menit = JULIANDAY(end) - JULIANDAY(start) → 24 jam * 60 menit.
     * Berlaku konsisten di SQLite (julianday) dan PostgreSQL (julianday di-extract).
     * Basis: timestamp (lead masuk) → first_replied_at, atau last_update_at
     * (proxy waktu respon) untuk data migrasi yang tidak punya first_replied_at.
     */
    private function avgResponseTimeMinutes($baseQuery): ?int
    {
        $end = DB::raw("COALESCE(first_replied_at, last_update_at)");

        $row = (clone $baseQuery)
            ->selectRaw("COUNT(*) as count")
            ->selectRaw("SUM((JULIANDAY(COALESCE(first_replied_at, last_update_at)) - JULIANDAY(timestamp)) * 24 * 60) as total_minutes")
            ->first();

        $count = (int) ($row->count ?? 0);

        if ($count === 0) {
            return null;
        }

        return (int) round((float) ($row->total_minutes ?? 0) / $count);
    }

    private function buildHandlerStats(string $startDate, string $endDate, ?int $onlyHandlerId = null): \Illuminate\Support\Collection
    {
        $endOfDay = Carbon::parse($endDate)->endOfDay();

        // Leads masuk per handler (by timestamp): total, followed_up, not_followed_up,
        // jumlah responden & total menit response (SUM diff) dalam SATU pass.
        $leadAgg = Lead::whereBetween('timestamp', [$startDate, $endOfDay])
            ->when($onlyHandlerId, fn ($q) => $q->where('handler_id', $onlyHandlerId))
            ->select('handler_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status_fu <> 'new' THEN 1 ELSE 0 END) as followed")
            ->selectRaw("SUM(CASE WHEN status_fu = 'new' THEN 1 ELSE 0 END) as not_followed")
            ->selectRaw("SUM(CASE WHEN status_fu <> 'new' AND last_update_at IS NOT NULL THEN 1 ELSE 0 END) as resp_count")
            ->selectRaw("SUM(CASE WHEN status_fu <> 'new' AND last_update_at IS NOT NULL THEN (JULIANDAY(COALESCE(first_replied_at, last_update_at)) - JULIANDAY(timestamp)) * 24 * 60 ELSE 0 END) as resp_sum_minutes")
            ->groupBy('handler_id')
            ->get()
            ->keyBy('handler_id');

        // Closing & revenue per handler berdasarkan orders.paid_time (Scalev).
        // Orders tanpa handler (NULL) tidak dipakai di sini — masuk baris "Tanpa CS".
        $closeAgg = Order::whereBetween('paid_time', [$startDate, $endOfDay])
            ->when($onlyHandlerId, fn ($q) => $q->where('handler_id', $onlyHandlerId))
            ->whereNotNull('handler_id')
            ->select('handler_id')
            ->selectRaw('COUNT(*) as closing')
            ->selectRaw('COALESCE(SUM(gross_revenue), 0) as revenue')
            ->groupBy('handler_id')
            ->get()
            ->keyBy('handler_id');

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
            ->selectRaw('COUNT(*) as closing, COALESCE(SUM(gross_revenue), 0) as revenue')
            ->first();

        $rows = collect();

        if ($onlyHandlerId) {
            $handlerIds = collect([$onlyHandlerId]);
        } else {
            $handlerIds = $leadAgg->keys()->merge($closeAgg->keys())->unique();
        }

        $handlers = Handler::whereIn('id', $handlerIds)->orderBy('name')->get();

        foreach ($handlers as $handler) {
            $lead = $leadAgg[$handler->id] ?? null;
            $close = $closeAgg[$handler->id] ?? null;
            $total = (int) ($lead->total ?? 0);
            $closing = (int) ($close->closing ?? 0);
            $respCount = (int) ($lead->resp_count ?? 0);

            $rows->push([
                'handler_id' => $handler->id,
                'name' => $handler->name,
                'initial' => strtoupper(substr($handler->name, 0, 1)),
                'total' => $total,
                'followed_up' => (int) ($lead->followed ?? 0),
                'not_followed_up' => (int) ($lead->not_followed ?? 0),
                'closing' => $closing,
                'revenue' => (int) ($close->revenue ?? 0),
                'conversion_rate' => $total > 0 ? round(($closing / $total) * 100, 2) : 0,
                'avg_response_time_minutes' => $respCount > 0 ? (int) round((float) ($lead->resp_sum_minutes ?? 0) / $respCount) : null,
            ]);
        }

        $unassignedTotal = (int) $unassignedLead->total;
        $unassignedClosing = (int) $unassignedClose->closing;

        if ($unassignedTotal > 0 || $unassignedClosing > 0) {
            $rows->push([
                'handler_id' => null,
                'name' => 'Tanpa CS (unassigned)',
                'initial' => '?',
                'total' => $unassignedTotal,
                'followed_up' => (int) $unassignedLead->followed,
                'not_followed_up' => (int) $unassignedLead->not_followed,
                'closing' => $unassignedClosing,
                'revenue' => (int) $unassignedClose->revenue,
                'conversion_rate' => $unassignedTotal > 0 ? round(($unassignedClosing / $unassignedTotal) * 100, 2) : 0,
                'avg_response_time_minutes' => null,
            ]);
        }

        return $rows
            ->sortByDesc('closing')
            ->values();
    }
}
