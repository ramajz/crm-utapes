<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Leads</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .subtitle { color: #64748b; margin-bottom: 20px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; border-bottom: 2px solid #cbd5e1; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .paid { color: #059669; font-weight: bold; }
        .unpaid { color: #dc2626; }
        .footer { margin-top: 20px; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <h1>Export Leads</h1>
    <div class="subtitle">Periode: {{ $dateRange }} | {{ $leads->count() }} leads | {{ now()->format('d M Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>No. HP</th>
                <th>Handler</th>
                <th>Status</th>
                <th>Funnel</th>
                <th>Payment</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr>
                <td style="font-family: monospace;">{{ $lead->order_id }}</td>
                <td>{{ $lead->timestamp?->format('d/m/Y H:i') }}</td>
                <td>{{ $lead->customer?->name ?? '-' }}</td>
                <td>{{ $lead->customer?->phone ?? '-' }}</td>
                <td>{{ $lead->handler?->name ?? '-' }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($lead->status_fu)) }}</td>
                <td>{{ ucfirst($lead->funnel_stage) }}</td>
                <td class="{{ in_array($lead->financial_status, ['paid', 'lunas']) ? 'paid' : 'unpaid' }}">{{ ucfirst($lead->financial_status) }}</td>
                <td class="text-right">Rp {{ number_format($lead->total_value, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($leads->count() > 0)
    <div class="footer">
        Total: Rp {{ number_format($leads->sum('total_value'), 0, ',', '.') }} |
        Closing: {{ $leads->where('status_fu', 'closing')->count() }} |
        Unpaid: {{ $leads->whereNotIn('financial_status', ['paid', 'lunas'])->count() }}
    </div>
    @endif
</body>
</html>
