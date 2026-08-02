<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WebhookLog;
use App\Services\LeadService;
use App\Services\ScalevOrderSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScalevWebhookController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected ScalevOrderSync $orderSync,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $eventType = $request->input('event');
        $data = $request->input('data');

        // Event non-order (mis. test event) diabaikan tanpa mencatat, balas 200
        if (!in_array($eventType, ['order.created', 'order.payment_status_changed'], true)) {
            return response()->json(['message' => 'Event type ignored']);
        }

        if (!is_array($data) || empty($data['order_id'])) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $orderId = (string) $data['order_id'];

        // Skip duplikat (Scalev retry) — payload sama untuk order + event yang sama
        $payloadJson = json_encode($request->all());
        if (WebhookLog::where('order_id', $orderId)
            ->where('event_type', $eventType)
            ->where('payload', $payloadJson)
            ->exists()) {
            return response()->json(['message' => 'Duplicate webhook ignored']);
        }

        $log = WebhookLog::create([
            'order_id' => $orderId,
            'event_type' => $eventType,
            'payload' => $request->all(),
            'status' => 'pending',
        ]);

        try {
            // Sumber kebenaran order (Scalev)
            $this->orderSync->processEvent($eventType, $data);

            // Lapisan CRM (leads)
            if ($eventType === 'order.created') {
                $this->syncLeadFromOrderCreated($data);
            } elseif ($eventType === 'order.payment_status_changed') {
                $this->syncLeadPayment($data);
            }

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Webhook processed',
                'order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Scalev webhook failed', [
                'order_id' => $orderId,
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    private function syncLeadFromOrderCreated(array $data): void
    {
        $sourceUrl = $data['metadata']['event_source_url'] ?? $data['message_variables']['event_source_url'] ?? null;
        $utms = $this->parseUtm($sourceUrl);

        $handlerName = $data['message_variables']['handler'] ?? null;
        $handlerId = $handlerName ? \App\Models\Handler::firstOrCreate(['name' => $handlerName])->id : null;

        $lead = Lead::where('order_id', $data['order_id'])->first();
        if ($lead) {
            $lead->update([
                'handler_id' => $handlerId,
                'financial_status' => $data['payment_status'] ?? 'unpaid',
                'total_value' => (int) round((float) ($data['net_revenue'] ?? 0)),
                'notes' => $data['notes'] ?? null,
                'utm_source' => $utms['utm_source'],
                'utm_medium' => $utms['utm_medium'],
                'utm_campaign' => $utms['utm_campaign'],
                'utm_content' => $utms['utm_content'],
                'last_update_at' => now(),
            ]);
            return;
        }

        $this->leadService->createFromData([
            'phone' => $data['customer']['phone'] ?? '',
            'customer_name' => $data['customer']['name'] ?? ('Customer ' . substr(preg_replace('/[^0-9]/', '', $data['customer']['phone'] ?? ''), -4)),
            'order_id' => $data['order_id'],
            'total_value' => $data['net_revenue'] ?? 0,
            'handler_id' => $handlerId,
            'financial_status' => $data['payment_status'] ?? 'unpaid',
            'notes' => $data['notes'] ?? null,
            'size' => null,
            'utm_source' => $utms['utm_source'],
            'utm_medium' => $utms['utm_medium'],
            'utm_campaign' => $utms['utm_campaign'],
            'utm_content' => $utms['utm_content'],
            'timestamp' => $data['created_at'] ?? $data['draft_time'] ?? now(),
        ]);
    }

    private function syncLeadPayment(array $data): void
    {
        $lead = Lead::where('order_id', $data['order_id'])->first();
        if (!$lead) {
            return;
        }

        $lead->update([
            'financial_status' => $data['payment_status'] ?? $lead->financial_status,
            'last_update_at' => now(),
        ]);
    }

    protected function parseUtm(?string $url): array
    {
        $default = [
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_content' => null,
        ];

        if (!$url) {
            return $default;
        }

        $parts = parse_url($url);
        if (!$parts || !isset($parts['query'])) {
            return $default;
        }

        parse_str($parts['query'], $query);

        return [
            'utm_source' => $query['utm_source'] ?? null,
            'utm_medium' => $query['utm_medium'] ?? null,
            'utm_campaign' => $query['utm_campaign'] ?? null,
            'utm_content' => $query['utm_content'] ?? null,
        ];
    }
}
