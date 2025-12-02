<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatuses;
use App\Models\InvoiceStatus;
use Illuminate\Database\Seeder;

class InvoiceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => InvoiceStatuses::DRAFT->value, 'sort_order' => 1],
            ['name' => InvoiceStatuses::PENDING->value, 'sort_order' => 2],
            ['name' => InvoiceStatuses::SENT->value, 'sort_order' => 3],
            ['name' => InvoiceStatuses::PAID->value, 'sort_order' => 4],
            ['name' => InvoiceStatuses::OVERDUE->value, 'sort_order' => 5],
            ['name' => InvoiceStatuses::CANCELLED->value, 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            InvoiceStatus::firstOrCreate(
                ['name' => $status['name']],
                ['sort_order' => $status['sort_order']]
            );
        }

        $this->command->info('Invoice statuses seeded successfully!');
    }
}
