<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use Illuminate\Console\Command;

class ProcessRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send recurring invoices that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing recurring invoices...');

        $invoices = Invoice::where('is_recurring', true)
            ->where('next_recurring_date', '<=', now())
            ->whereNotNull('next_recurring_date')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No recurring invoices due at this time.');

            return 0;
        }

        $count = 0;
        foreach ($invoices as $invoice) {
            $this->line("Processing invoice {$invoice->invoice_number}...");

            SendInvoiceEmail::dispatch($invoice);
            $count++;
        }

        $this->info("Processed {$count} recurring invoice(s).");

        return 0;
    }
}
