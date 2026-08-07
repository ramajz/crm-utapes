<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Lumajang', 'nocobase_id' => '358537632219136'],
            ['name' => 'Kediri', 'nocobase_id' => '358537655287808'],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['nocobase_id' => $branch['nocobase_id']],
                ['name' => $branch['name'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Branches seeded (Lumajang, Kediri)');
    }
}
