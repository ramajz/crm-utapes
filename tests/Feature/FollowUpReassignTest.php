<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Handler;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpReassignTest extends TestCase
{
    use RefreshDatabase;

    private function createBranch(string $name, string $nocobaseId): Branch
    {
        return Branch::create(['name' => $name, 'nocobase_id' => $nocobaseId, 'is_active' => true]);
    }

    private function createHandler(string $name, ?Branch $branch = null): Handler
    {
        return Handler::create([
            'name' => $name,
            'is_active' => true,
            'branch_id' => $branch?->id,
        ]);
    }

    private function createLead(Handler $handler, ?Branch $branch = null): Lead
    {
        return Lead::create([
            'order_id' => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'handler_id' => $handler->id,
            'branch_id' => $branch?->id,
            'financial_status' => 'unpaid',
            'total_value' => 250000,
            'funnel_stage' => 'cold',
            'status_fu' => 'new',
            'lead_type' => 'new',
            'timestamp' => now(),
        ]);
    }

    public function test_manager_marks_lead_as_follow_up_required(): void
    {
        $manager = User::factory()->manager()->create();
        $handler = $this->createHandler('Siti');
        $lead = $this->createLead($handler);

        $response = $this->actingAs($manager)->post(route('leads.toggle-follow-up', $lead), [
            'follow_up_required' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'follow_up_required' => true,
            'follow_up_status' => 'pending',
        ]);
        $this->assertDatabaseHas('lead_histories', [
            'lead_id' => $lead->id,
            'field_changed' => 'follow_up_required',
            'old_value' => '0',
            'new_value' => '1',
        ]);
    }

    public function test_manager_unmarks_lead_follow_up(): void
    {
        $manager = User::factory()->manager()->create();
        $handler = $this->createHandler('Siti');
        $lead = $this->createLead($handler);
        $lead->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $response = $this->actingAs($manager)->post(route('leads.toggle-follow-up', $lead), [
            'follow_up_required' => 0,
        ]);

        $response->assertRedirect();
        $lead->refresh();
        $this->assertFalse($lead->follow_up_required);
        $this->assertNull($lead->follow_up_status);
    }

    public function test_cs_sees_only_their_own_follow_up_leads(): void
    {
        $branchA = $this->createBranch('Lumajang', '358537632219136');
        $branchB = $this->createBranch('Kediri', '358537655287808');

        $handlerA = $this->createHandler('Peler', $branchA);
        $handlerB = $this->createHandler('Kiki', $branchB);

        $leadA = $this->createLead($handlerA, $branchA);
        $leadB = $this->createLead($handlerB, $branchB);
        $leadA->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);
        $leadB->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $cs = User::factory()->create(['role' => 'cs']);
        $handlerA->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->get(route('leads.follow-up'));

        $response->assertOk();
        $response->assertSee($leadA->order_id);
        $response->assertDontSee($leadB->order_id);
    }

    public function test_cs_can_complete_follow_up(): void
    {
        $handler = $this->createHandler('Siti');
        $lead = $this->createLead($handler);
        $lead->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $cs = User::factory()->create(['role' => 'cs']);
        $handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->post(route('leads.complete-follow-up', $lead));

        $response->assertRedirect();
        $lead->refresh();
        $this->assertSame('done', $lead->follow_up_status);
        $this->assertNotNull($lead->follow_up_completed_at);
        $this->assertDatabaseHas('lead_histories', [
            'lead_id' => $lead->id,
            'field_changed' => 'follow_up_status',
            'old_value' => 'pending',
            'new_value' => 'done',
        ]);
    }

    public function test_manager_bulk_reassign_changes_handler_and_keeps_follow_up(): void
    {
        $manager = User::factory()->manager()->create();
        $handlerOld = $this->createHandler('Siti');
        $handlerNew = $this->createHandler('Rina');
        $lead = $this->createLead($handlerOld);
        $lead->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $response = $this->actingAs($manager)->post(route('leads.bulk-reassign'), [
            'lead_ids' => [$lead->id],
            'handler_id' => $handlerNew->id,
        ]);

        $response->assertRedirect();
        $lead->refresh();
        $this->assertSame($handlerNew->id, $lead->handler_id);
        $this->assertTrue($lead->follow_up_required);
        $this->assertSame('pending', $lead->follow_up_status);
        $this->assertDatabaseHas('lead_histories', [
            'lead_id' => $lead->id,
            'field_changed' => 'handler_id',
            'new_value' => 'Rina',
        ]);
    }

    public function test_cs_cannot_toggle_follow_up(): void
    {
        $handler = $this->createHandler('Siti');
        $lead = $this->createLead($handler);
        $cs = User::factory()->create(['role' => 'cs']);
        $handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->post(route('leads.toggle-follow-up', $lead), [
            'follow_up_required' => 1,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('lead_histories', ['field_changed' => 'follow_up_required']);
    }

    public function test_cs_cannot_bulk_reassign(): void
    {
        $handlerA = $this->createHandler('Siti');
        $handlerB = $this->createHandler('Rina');
        $lead = $this->createLead($handlerA);
        $cs = User::factory()->create(['role' => 'cs']);
        $handlerA->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->post(route('leads.bulk-reassign'), [
            'lead_ids' => [$lead->id],
            'handler_id' => $handlerB->id,
        ]);

        $response->assertForbidden();
        $lead->refresh();
        $this->assertSame($handlerA->id, $lead->handler_id);
    }

    public function test_follow_up_index_filters_by_branch(): void
    {
        $branchA = $this->createBranch('Lumajang', '358537632219136');
        $branchB = $this->createBranch('Kediri', '358537655287808');

        $handlerA = $this->createHandler('Peler', $branchA);
        $handlerB = $this->createHandler('Kiki', $branchB);

        $leadA = $this->createLead($handlerA, $branchA);
        $leadB = $this->createLead($handlerB, $branchB);
        $leadA->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);
        $leadB->update(['follow_up_required' => true, 'follow_up_status' => 'pending']);

        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get(route('leads.follow-up', ['branch_id' => $branchA->id]));

        $response->assertOk();
        $response->assertSee($leadA->order_id);
        $response->assertDontSee($leadB->order_id);
    }
}
