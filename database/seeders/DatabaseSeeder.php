<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Handler;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private array $csNames = ['Siti', 'Rina', 'Budi', 'Dewi', 'Ahmad'];

    public function run(): void
    {
        $this->call(BranchSeeder::class);
        $this->createUsers();
        $this->createHandlers();
        $this->createCustomers();
        $this->createLeads();
    }

    private function createUsers(): void
    {
        $users = [
            ['name' => 'Admin Utapes', 'email' => 'admin@crm.com', 'role' => 'admin', 'phone' => '628123456700'],
            ['name' => 'Manager Utapes', 'email' => 'manager@crm.com', 'role' => 'manager', 'phone' => '628123456701'],
        ];

        foreach ($this->csNames as $i => $name) {
            $users[] = [
                'name' => $name,
                'email' => strtolower($name) . '@crm.com',
                'role' => 'cs',
                'phone' => '62812345670' . ($i + 2),
            ];
        }

        $now = now();
        foreach ($users as $u) {
            User::create([
                ...$u,
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => $now,
            ]);
        }

        $this->command->info('✅ Users created (admin, manager, 5 CS)');
    }

    private function createHandlers(): void
    {
        $csUsers = User::where('role', 'cs')->get();
        foreach ($csUsers as $i => $user) {
            Handler::create([
                'user_id' => $user->id,
                'name' => $this->csNames[$i],
                'phone' => $user->phone,
                'is_active' => true,
            ]);
        }
        $this->command->info('✅ Handlers created');
    }

    private function createCustomers(): void
    {
        $names = [
            'Budi Santoso', 'Siti Rahayu', 'Ahmad Hidayat', 'Dewi Lestari', 'Rudi Hartono',
            'Mega Wijaya', 'Agus Pratama', 'Rina Marlina', 'Hendra Gunawan', 'Fitri Handayani',
            'Doni Kusuma', 'Yanti Purnama', 'Bambang Susilo', 'Rini Anggraini', 'Adi Nugroho',
            'Tuti Alawiyah', 'Joko Susanto', 'Maya Sari', 'Eko Prasetyo', 'Nina Novita',
        ];

        $customers = [];
        $now = now();
        foreach ($names as $i => $name) {
            $customers[] = [
                'phone' => '628' . fake()->numerify('###########'),
                'name' => $name,
                'total_orders' => fake()->numberBetween(1, 10),
                'total_spend' => fake()->numberBetween(50000, 5000000),
                'first_purchase_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
                'last_purchase_at' => fake()->dateTimeBetween('-1 month', 'now'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Customer::insert($customers);
        $this->command->info('✅ Customers created (20 customers)');
    }

    private function createLeads(): void
    {
        $handlerIds = Handler::pluck('id', 'name')->toArray();
        $handlerUserIds = Handler::with('user')->get()->pluck('user.id', 'name')->toArray();
        $customerIds = Customer::pluck('id')->toArray();
        $sizes = ['39', '40', '41', '42', '43', '44', null, null, null];
        $statuses = ['new', 'chatted', 'replied', 'interested', 'nunggu_gajian', 'promise_transfer', 'closing', 'rejected'];

        $now = now();
        $leads = [];
        $leadCounter = 0;
        $handlerNames = array_keys($handlerIds);

        for ($day = 90; $day >= 0; $day--) {
            $leadsToday = fake()->numberBetween(3, 10);
            for ($j = 0; $j < $leadsToday; $j++) {
                $leadCounter++;
                $customerId = $customerIds[array_rand($customerIds)];
                $handlerName = $handlerNames[array_rand($handlerNames)];
                $handlerId = $handlerIds[$handlerName];
                $statusFu = $statuses[array_rand($statuses)];

                $funnelMap = [
                    'new' => 'cold', 'chatted' => 'cold', 'rejected' => 'cold',
                    'replied' => 'warm', 'interested' => 'warm',
                    'nunggu_gajian' => 'hot', 'promise_transfer' => 'hot', 'closing' => 'hot',
                ];

                $timestamp = (clone $now)->subDays($day)->addHours(fake()->numberBetween(8, 21));
                $isPaid = in_array($statusFu, ['closing']) || fake()->boolean(30);
                $firstReplied = null;

                if ($statusFu !== 'new') {
                    $firstReplied = (clone $timestamp)->addMinutes(fake()->numberBetween(5, 180));
                }

                $leads[] = [
                    'order_id' => 'ORD-' . str_pad((string)(10000 + $leadCounter), 6, '0', STR_PAD_LEFT),
                    'customer_id' => $customerId,
                    'handler_id' => $handlerId,
                    'financial_status' => $isPaid ? 'paid' : 'unpaid',
                    'total_value' => fake()->numberBetween(50000, 1500000),
                    'funnel_stage' => $funnelMap[$statusFu],
                    'status_fu' => $statusFu,
                    'notes' => fake()->boolean(40) ? fake()->sentence(4) : null,
                    'size' => $sizes[array_rand($sizes)],
                    'utm_source' => fake()->optional(0.5)->randomElement(['ig', 'facebook', 'google', 'tiktok']),
                    'utm_medium' => fake()->optional(0.4)->randomElement(['social', 'cpc', 'organic']),
                    'utm_campaign' => fake()->optional(0.4)->randomElement(['promo_juli', 'flash_sale', 'new_collection']),
                    'traffic_type' => fake()->randomElement(['ads', 'organik', 'direct']),
                    'lead_type' => 'new',
                    'first_replied_at' => $firstReplied,
                    'timestamp' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                // History skipped in batch mode; individual histories created on status update via app
            }

            // Batch insert every 3 days to keep memory low
            if ($day % 3 === 0 && count($leads) > 0) {
                Lead::insert($leads);
                $leads = [];
                $this->command->info("  ... day {$day}: {$leadCounter} leads generated");
            }
        }

        // Insert remaining leads
        if (count($leads) > 0) {
            Lead::insert($leads);
        }

        $this->command->info('✅ Leads created (~' . $leadCounter . ' leads)');
    }
}
