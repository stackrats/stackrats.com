<?php

namespace App\Console\Commands;

use App\Actions\Invoice\CreateRecurringInvoiceAction;
use App\Enums\InvoiceStatuses;
use App\Events\InvoiceCreated;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            ->where('next_recurring_at', '<=', $date)
            ->whereNotNull('next_recurring_at')
            ->whereNull('recurring_completed_at')
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::PENDING->value);
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
                DB::transaction(function () use ($invoice, $createRecurringInvoice, &$count) {
                    // Create the next recurring invoice first (this marks parent as completed)
                    $newInvoice = $createRecurringInvoice->execute($invoice);
                    $this->info("Created next recurring invoice {$newInvoice->invoice_number}.");

                    // Then dispatch email for the current invoice
                    SendInvoiceEmail::dispatch($invoice);
                    $this->info("Dispatched email for {$invoice->invoice_number}.");

                    InvoiceCreated::dispatch($newInvoice);
                    $this->info("Dispatched InvoiceCreated event for {$newInvoice->invoice_number}.");

                    $count++;
                });
            } catch (\Exception $e) {
                $this->error("Failed to process invoice {$invoice->invoice_number}: ".$e->getMessage());
            }
        }

        $this->info("Processed {$count} recurring invoice(s).");

        return 0;
    }
}
