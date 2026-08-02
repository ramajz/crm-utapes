<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Order;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScalevWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createdPayload(string $orderId, array $extra = []): array
    {
        return array_merge_recursive([
            'event' => 'order.created',
            'data' => [
                'order_id' => $orderId,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'created_at' => '2026-07-01T01:00:00Z',
                'gross_revenue' => '575000.00',
                'net_revenue' => '575000.00',
                'customer' => ['phone' => '6281234567890', 'name' => 'Test Customer'],
                'message_variables' => ['handler' => 'CS Test'],
                'store' => ['name' => 'Utapesseken.co'],
                'orderlines' => [
                    ['product_name' => 'Lead', 'quantity' => 1, 'weight' => 1000, 'product_price' => '575000.00', 'variant_price' => '575000.00', 'variant_cogs' => '0.00', 'discount' => '0.00'],
                ],
            ],
        ], $extra);
    }

    public function test_order_created_payload_is_logged_and_synced(): void
    {
        $response = $this->postJson('/api/webhook/scalev', $this->createdPayload('260701TESTABC'));

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'order_id' => '260701TESTABC',
            'payment_status' => 'unpaid',
            'gross_revenue' => 575000,
        ]);
        $this->assertSame(1, Order::where('order_id', '260701TESTABC')->first()->items()->count());
        $this->assertDatabaseHas('webhook_logs', ['order_id' => '260701TESTABC', 'status' => 'processed']);
        $this->assertDatabaseHas('handlers', ['name' => 'CS Test']);
        $this->assertDatabaseHas('leads', ['order_id' => '260701TESTABC', 'financial_status' => 'unpaid']);
    }

    public function test_payment_event_marks_order_and_lead_paid(): void
    {
        $this->postJson('/api/webhook/scalev', $this->createdPayload('260701PAYXYZ'));

        $this->postJson('/api/webhook/scalev', [
            'event' => 'order.payment_status_changed',
            'data' => [
                'order_id' => '260701PAYXYZ',
                'payment_status' => 'paid',
                'paid_time' => '2026-07-15T10:00:00Z',
                'customer' => ['phone' => '6281234567890', 'name' => 'Test Customer'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('orders', ['order_id' => '260701PAYXYZ', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('leads', ['order_id' => '260701PAYXYZ', 'financial_status' => 'paid']);
    }

    public function test_duplicate_payload_is_ignored(): void
    {
        $payload = $this->createdPayload('260701DUP123');

        $this->postJson('/api/webhook/scalev', $payload)->assertOk();
        $this->postJson('/api/webhook/scalev', $payload)->assertOk();

        $this->assertSame(1, WebhookLog::where('order_id', '260701DUP123')->count());
        $this->assertSame(1, Order::where('order_id', '260701DUP123')->count());
        $this->assertSame(1, Lead::where('order_id', '260701DUP123')->count());
    }

    public function test_non_order_event_is_ignored(): void
    {
        $response = $this->postJson('/api/webhook/scalev', [
            'event' => 'business.test_event',
            'data' => [],
        ]);

        $response->assertOk();
        $this->assertSame(0, WebhookLog::count());
    }
}
