<?php

namespace Tests\Feature;

use App\Models\Handler;
use App\Models\Lead;
use App\Services\LeadAssignmentService;
use App\Services\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeadAutoAssignTest extends TestCase
{
    use RefreshDatabase;

    private function createLead(?int $handlerId = null): Lead
    {
        return app(LeadService::class)->createFromData([
            'phone' => '6281234567890',
            'customer_name' => 'Customer Auto Assign',
            'order_id' => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'total_value' => 250000,
            'handler_id' => $handlerId,
            'financial_status' => 'unpaid',
            'timestamp' => now(),
        ]);
    }

    public function test_lead_without_handler_is_auto_assigned(): void
    {
        $handler = Handler::create(['name' => 'Siti', 'is_active' => true]);

        $lead = $this->createLead();

        $this->assertSame($handler->id, $lead->handler_id);
    }

    public function test_existing_handler_is_respected(): void
    {
        $handlerA = Handler::create(['name' => 'Rina', 'is_active' => true]);
        Handler::create(['name' => 'Budi', 'is_active' => true]);

        $lead = $this->createLead($handlerA->id);

        $this->assertSame($handlerA->id, $lead->handler_id);
    }

    public function test_least_loaded_balances_across_handlers(): void
    {
        $handlerA = Handler::create(['name' => 'Siti', 'is_active' => true]);
        $handlerB = Handler::create(['name' => 'Rina', 'is_active' => true]);

        $lead1 = $this->createLead();
        $lead2 = $this->createLead();
        $lead3 = $this->createLead();

        $distribution = [
            $lead1->handler_id, $lead2->handler_id, $lead3->handler_id,
        ];

        $this->assertContains($handlerA->id, $distribution);
        $this->assertContains($handlerB->id, $distribution);

        $countA = collect($distribution)->filter(fn ($id) => $id === $handlerA->id)->count();
        $countB = collect($distribution)->filter(fn ($id) => $id === $handlerB->id)->count();

        $this->assertLessThanOrEqual(2, $countA);
        $this->assertLessThanOrEqual(2, $countB);
    }

    public function test_round_robin_rotates_through_handlers(): void
    {
        config()->set('leadassignment.strategy', 'round_robin');
        Cache::flush();

        $handlerA = Handler::create(['name' => 'Siti', 'is_active' => true]);
        $handlerB = Handler::create(['name' => 'Rina', 'is_active' => true]);

        $lead1 = $this->createLead();
        $lead2 = $this->createLead();
        $lead3 = $this->createLead();

        $this->assertSame($handlerA->id, $lead1->handler_id);
        $this->assertSame($handlerB->id, $lead2->handler_id);
        $this->assertSame($handlerA->id, $lead3->handler_id);
    }

    public function test_auto_assign_disabled_leaves_lead_unassigned(): void
    {
        config()->set('leadassignment.auto_assign', false);

        Handler::create(['name' => 'Siti', 'is_active' => true]);

        $lead = $this->createLead();

        $this->assertNull($lead->handler_id);
    }

    public function test_no_active_handler_returns_null(): void
    {
        Handler::create(['name' => 'Siti', 'is_active' => false]);

        $lead = $this->createLead();

        $this->assertNull($lead->handler_id);
    }

    public function test_preview_does_not_change_round_robin_cache(): void
    {
        config()->set('leadassignment.strategy', 'round_robin');
        Cache::put('lead_assignment.rr_index', 0);
        Handler::create(['name' => 'Siti', 'is_active' => true]);
        Handler::create(['name' => 'Rina', 'is_active' => true]);

        $preview = app(LeadAssignmentService::class)->preview(3);

        $this->assertSame([2, 1, 2], $preview);
        $this->assertSame(0, Cache::get('lead_assignment.rr_index'));
    }
}
