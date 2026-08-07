<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Handler;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateWaTest extends TestCase
{
    use RefreshDatabase;

    private function createLead(): Lead
    {
        $customer = Customer::create([
            'phone' => '6281234567890',
            'name' => 'Budi Santoso',
        ]);
        $handler = Handler::create(['name' => 'Peler', 'is_active' => true]);

        return Lead::create([
            'order_id' => 'ORD-260801ABC',
            'customer_id' => $customer->id,
            'handler_id' => $handler->id,
            'financial_status' => 'unpaid',
            'total_value' => 575000,
            'funnel_stage' => 'cold',
            'status_fu' => 'new',
            'size' => '42',
            'lead_type' => 'new',
            'timestamp' => now(),
        ]);
    }

    public function test_render_template_replaces_placeholders(): void
    {
        $lead = $this->createLead();

        $rendered = $lead->renderTemplate(
            'Halo {nama}, order {order_id} size {size} total {total} dari {handler}'
        );

        $this->assertSame(
            'Halo Budi Santoso, order ORD-260801ABC size 42 total Rp 575.000 dari Peler',
            $rendered
        );
    }

    public function test_placeholder_falls_back_when_data_missing(): void
    {
        $customer = Customer::create(['phone' => '6281234567890', 'name' => 'Siti']);
        $lead = Lead::create([
            'order_id' => 'ORD-1',
            'customer_id' => $customer->id,
            'financial_status' => 'unpaid',
            'total_value' => 0,
            'funnel_stage' => 'cold',
            'status_fu' => 'new',
            'lead_type' => 'new',
            'timestamp' => now(),
        ]);

        $rendered = $lead->renderTemplate('Halo {nama} ({size}) - {handler}');

        $this->assertSame('Halo Siti () - CS', $rendered);
    }

    public function test_detail_page_shows_template_panel(): void
    {
        $lead = $this->createLead();
        $cs = User::factory()->create(['role' => 'cs']);
        $lead->handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Template Pesan WhatsApp');
        $response->assertSee('Sapaan Awal');
        $response->assertSee('Kirim WA');
    }

    public function test_wa_link_contains_rendered_message(): void
    {
        $lead = $this->createLead();
        $cs = User::factory()->create(['role' => 'cs']);
        $lead->handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertSee('https://wa.me/6281234567890?text=', false);
        // Pesan template 'Sapaan Awal' ter-render dengan nama customer
        $response->assertSee(rawurlencode('Budi Santoso'), false);
    }
}
