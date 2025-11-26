<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup {--invoice-path= : Path to legacy invoices to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the application by seeding admin user and importing legacy invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding initial data...');
        $this->call('db:seed', ['--class' => 'InvoiceStatusSeeder']);
        $this->call('db:seed', ['--class' => 'RecurringFrequencySeeder']);

        $this->info('Seeding admin user...');
        $this->call('app:seed-admin-user-command');

        $invoicePath = $this->option('invoice-path') ?: storage_path('app/legacy_import_ods');

        if (File::isDirectory($invoicePath)) {
            $this->info("Importing legacy invoices from $invoicePath...");
            $this->call('invoices:import-legacy', [
                'path' => $invoicePath,
            ]);
        } else {
            $this->info("No legacy invoices found at $invoicePath. Skipping import.");
        }

        $this->info('Application setup complete.');
    }
}
