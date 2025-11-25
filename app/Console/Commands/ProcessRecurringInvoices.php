<?php

namespace App\Console\Commands;

use App\Actions\Invoice\CreateRecurringInvoiceAction;
use App\Enums\InvoiceStatuses;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-recurring {--date= : The date to simulate processing for (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send recurring invoices that are due';

    /**
     * Execute the console command.
     */
    public function handle(CreateRecurringInvoiceAction $createRecurringInvoice)
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $this->info("Processing recurring invoices for {$date->toDateString()}...");

        $invoices = Invoice::where('is_recurring', true)
            ->where('next_recurring_date', '<=', $date)
            ->whereNotNull('next_recurring_date')
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', '!=', InvoiceStatuses::DRAFT->value);
            })
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No recurring invoices due at this time.');

            return 0;
        }

        $count = 0;
        foreach ($invoices as $invoice) {
            $this->line("Processing recurring invoice for {$invoice->invoice_number}...");

            try {
                $newInvoice = $createRecurringInvoice->execute($invoice);

                $this->info("Created new invoice {$newInvoice->invoice_number}.");

                SendInvoiceEmail::dispatch($newInvoice);
                $this->info("Dispatched email for {$newInvoice->invoice_number}.");

                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to process invoice {$invoice->invoice_number}: ".$e->getMessage());
            }
        }

        $this->info("Processed {$count} recurring invoice(s).");

        return 0;
    }
}
