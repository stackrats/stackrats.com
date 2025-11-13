<?php

namespace Database\Seeders;

use App\Enums\RecurringFrequencies;
use App\Models\RecurringFrequency;
use Illuminate\Database\Seeder;

class RecurringFrequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $frequencies = [
            ['name' => RecurringFrequencies::WEEKLY->value, 'sort_order' => 1],
            ['name' => RecurringFrequencies::MONTHLY->value, 'sort_order' => 2],
            ['name' => RecurringFrequencies::QUARTERLY->value, 'sort_order' => 3],
            ['name' => RecurringFrequencies::YEARLY->value, 'sort_order' => 4],
        ];

        foreach ($frequencies as $frequency) {
            RecurringFrequency::firstOrCreate(
                ['name' => $frequency['name']],
                ['sort_order' => $frequency['sort_order']]
            );
        }

        $this->command->info('Recurring frequencies seeded successfully!');
    }
}
