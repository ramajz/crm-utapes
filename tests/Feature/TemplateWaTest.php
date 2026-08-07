<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Handler;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppTemplate;
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
        WhatsAppTemplate::create([
            'name' => 'Sapaan Awal',
            'category' => 'cold',
            'message' => 'Halo {nama}, terima kasih sudah order di Utapes!',
            'is_active' => true,
        ]);

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
        WhatsAppTemplate::create([
            'name' => 'Sapaan Awal',
            'category' => 'cold',
            'message' => 'Halo {nama}, terima kasih sudah order di Utapes!',
            'is_active' => true,
        ]);

        $lead = $this->createLead();
        $cs = User::factory()->create(['role' => 'cs']);
        $lead->handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->get(route('leads.show', $lead));

        $response->assertOk();
        // Link wa.me dinamis via Alpine (:href) — base URL tetap terlihat
        $response->assertSee('https://wa.me/6281234567890?text=', false);
        // Pesan template ter-render dengan nama customer ada di textarea (Alpine state)
        $response->assertSee("Halo Budi Santoso, terima kasih sudah order di Utapes!", false);
    }

    public function test_inactive_template_not_shown_on_detail_page(): void
    {
        WhatsAppTemplate::create([
            'name' => 'Template Rahasia',
            'category' => 'cold',
            'message' => 'Halo {nama}',
            'is_active' => false,
        ]);

        $lead = $this->createLead();
        $cs = User::factory()->create(['role' => 'cs']);
        $lead->handler->update(['user_id' => $cs->id]);

        $response = $this->actingAs($cs)->get(route('leads.show', $lead));

        $response->assertOk();
        $response->assertDontSee('Template Rahasia');
    }

    public function test_manager_can_create_template(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->post(route('templates.store'), [
            'name' => 'Template Baru',
            'category' => 'warm',
            'message' => 'Halo {nama}, gimana kabarnya?',
        ]);

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'Template Baru',
            'category' => 'warm',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_update_template(): void
    {
        $manager = User::factory()->manager()->create();
        $template = WhatsAppTemplate::create([
            'name' => 'Lama',
            'category' => 'cold',
            'message' => 'Pesan lama',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->put(route('templates.update', $template), [
            'name' => 'Baru',
            'category' => 'hot',
            'message' => 'Pesan baru {nama}',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('whatsapp_templates', [
            'id' => $template->id,
            'name' => 'Baru',
            'category' => 'hot',
            'message' => 'Pesan baru {nama}',
        ]);
    }

    public function test_manager_can_delete_template(): void
    {
        $manager = User::factory()->manager()->create();
        $template = WhatsAppTemplate::create([
            'name' => 'Hapus Saya',
            'category' => 'cold',
            'message' => 'Pesan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseMissing('whatsapp_templates', ['id' => $template->id]);
    }

    public function test_cs_cannot_manage_templates(): void
    {
        $cs = User::factory()->create(['role' => 'cs']);

        $response = $this->actingAs($cs)->get(route('templates.index'));

        $response->assertForbidden();
    }
}
