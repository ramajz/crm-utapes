<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class LeadExportController extends Controller
{
    public function csv(Request $request)
    {
        $leads = $this->filteredLeads($request)->get();

        $fileName = 'leads-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($leads) {
            $handle = fopen('php://output', 'w+');

            fputcsv($handle, [
                'Order ID', 'Tanggal', 'Nama', 'No. HP', 'Handler',
                'Status', 'Funnel', 'Payment', 'Total', 'Size', 'Notes',
                'UTM Source', 'UTM Medium', 'UTM Campaign', 'Traffic Type', 'Lead Type',
            ]);

            foreach ($leads as $lead) {
                fputcsv($handle, [
                    $lead->order_id,
                    $lead->timestamp?->format('Y-m-d H:i'),
                    $lead->customer?->name,
                    $lead->customer?->phone,
                    $lead->handler?->name,
                    $lead->status_fu,
                    $lead->funnel_stage,
                    $lead->financial_status,
                    $lead->total_value,
                    $lead->size,
                    $lead->notes,
                    $lead->utm_source,
                    $lead->utm_medium,
                    $lead->utm_campaign,
                    $lead->traffic_type,
                    $lead->lead_type,
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request)
    {
        $leads = $this->filteredLeads($request)->get();

        $html = view('leads.export-pdf', [
            'leads' => $leads,
            'dateRange' => $this->getDateRangeLabel($request),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="leads-' . now()->format('Y-m-d-His') . '.pdf"',
        ]);
    }

    private function filteredLeads(Request $request)
    {
        $query = Lead::with(['customer', 'handler']);

        $user = $request->user();
        if ($user->isCs() && $user->handler) {
            $query->byHandler($user->handler->id);
        } elseif ($request->filled('handler_id')) {
            $query->byHandler($request->handler_id);
        }

        if ($request->filled('start_date')) {
            $query->byDateRange(
                $request->start_date,
                $request->end_date ?? now()->toDateString()
            );
        }

        if ($request->filled('status_fu')) {
            $query->where('status_fu', $request->status_fu);
        }

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

        return $query->orderBy('timestamp', 'desc');
    }

    private function getDateRangeLabel(Request $request): string
    {
        if ($request->filled('start_date')) {
            $start = $request->start_date;
            $end = $request->end_date ?? now()->toDateString();
            return "$start s/d $end";
        }
        return 'Semua';
    }
}
