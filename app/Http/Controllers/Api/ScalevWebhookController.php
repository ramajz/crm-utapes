<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Handler;
use App\Models\WebhookLog;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScalevWebhookController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if ($request->missing('event') || $request->missing('data')) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $eventType = $request->input('event');
        $data = $request->input('data');

        $log = WebhookLog::create([
            'order_id' => $data['order_id'] ?? null,
            'event_type' => $eventType,
            'payload' => $request->all(),
            'status' => 'pending',
        ]);

        try {
            if ($eventType !== 'order.created') {
                $log->update(['status' => 'ignored']);
                return response()->json(['message' => 'Event type ignored']);
            }

            $sourceUrl = $data['metadata']['event_source_url'] ?? $data['message_variables']['event_source_url'] ?? null;
            $utms = $this->parseUtm($sourceUrl);

            $handlerName = $data['message_variables']['handler'] ?? null;
            $handlerId = null;
            if ($handlerName) {
                $handler = Handler::where('name', $handlerName)->first();
                $handlerId = $handler?->id;
            }

            $lead = $this->leadService->createFromData([
                'phone' => $data['customer']['phone'],
                'customer_name' => $data['customer']['name'],
                'order_id' => $data['order_id'],
                'total_value' => $data['net_revenue'] ?? 0,
                'handler_id' => $handlerId,
                'financial_status' => $data['payment_status'] ?? 'unpaid',
                'notes' => $data['notes'] ?? null,
                'size' => $data['notes'] ?? null,
                'utm_source' => $utms['utm_source'],
                'utm_medium' => $utms['utm_medium'],
                'utm_campaign' => $utms['utm_campaign'],
                'utm_content' => $utms['utm_content'],
                'timestamp' => $data['created_at'],
            ]);

            $log->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Webhook processed',
                'lead_id' => $lead->id,
                'order_id' => $lead->order_id,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Scalev webhook failed', [
                'order_id' => $data['order_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
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
