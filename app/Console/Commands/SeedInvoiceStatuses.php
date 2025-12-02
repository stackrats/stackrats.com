<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatuses;
use App\Models\InvoiceStatus;
use Illuminate\Console\Command;

class SeedInvoiceStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-invoice-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed invoice statuses with proper sort order';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding invoice statuses...');

        $statuses = [
            ['name' => InvoiceStatuses::DRAFT->value, 'sort_order' => 1],
            ['name' => InvoiceStatuses::PENDING->value, 'sort_order' => 2],
            ['name' => InvoiceStatuses::SENT->value, 'sort_order' => 3],
            ['name' => InvoiceStatuses::PAID->value, 'sort_order' => 4],
            ['name' => InvoiceStatuses::OVERDUE->value, 'sort_order' => 5],
            ['name' => InvoiceStatuses::CANCELLED->value, 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            InvoiceStatus::updateOrCreate(
                ['name' => $status['name']],
                ['sort_order' => $status['sort_order']]
            );

            $this->line("  ✓ {$status['name']} (sort_order: {$status['sort_order']})");
        }

        $this->info('Invoice statuses seeded successfully.');

        return 0;
    }
}
