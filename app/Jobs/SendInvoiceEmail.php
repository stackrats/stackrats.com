<?php

namespace App\Jobs;

use App\Actions\Invoice\GenerateInvoicePdfBinaryAction;
use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Events\InvoiceEmailSent;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Invoice $invoice,
        public string $emailBody = 'Please find attached your invoice.'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GenerateInvoicePdfBinaryAction $generatePdf): void
    {
        try {
            // Generate PDF binary
            $pdfBinary = $generatePdf->handle($this->invoice);

            // Send email with PDF attachment
            Mail::to($this->invoice->recipient_email)
                ->send(new InvoiceMail($this->invoice, $this->emailBody, $pdfBinary));

            $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

            $this->invoice->update([
                'invoice_status_id' => $sentStatus->id,
                'last_sent_at' => now(),
            ]);

            // Broadcast the event
            InvoiceEmailSent::dispatch($this->invoice->fresh());

            // If recurring, schedule the next invoice
            if ($this->invoice->is_recurring && $this->invoice->recurring_frequency_id) {
                $this->scheduleNextInvoice();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Schedule the next recurring invoice
     */
    protected function scheduleNextInvoice(): void
    {
        $nextDate = $this->calculateNextDate(
            $this->invoice->issue_date,
            $this->invoice->recurringFrequency
        );

        $draftStatus = InvoiceStatus::where('name', InvoiceStatuses::DRAFT->value)->first();

        // Create a new invoice based on this one
        $newInvoice = $this->invoice->replicate();
        $newInvoice->invoice_status_id = $draftStatus->id;
        $newInvoice->invoice_number = $this->invoice->generateInvoiceNumber();
        $newInvoice->issue_date = $nextDate;
        $newInvoice->due_date = $nextDate->copy()->addDays(30);
        $newInvoice->last_sent_at = null;
        $newInvoice->save();

        // Update current invoice's next recurring date
        $this->invoice->update([
            'next_recurring_date' => $nextDate,
        ]);
    }

    /**
     * Calculate the next date based on frequency
     */
    protected function calculateNextDate($currentDate, ?RecurringFrequency $frequency)
    {
        if (! $frequency) {
            return \Carbon\Carbon::parse($currentDate)->addMonth();
        }

        $date = \Carbon\Carbon::parse($currentDate);

        return match ($frequency->name) {
            RecurringFrequencies::WEEKLY->value => $date->addWeek(),
            RecurringFrequencies::MONTHLY->value => $date->addMonth(),
            RecurringFrequencies::QUARTERLY->value => $date->addMonths(3),
            RecurringFrequencies::YEARLY->value => $date->addYear(),
            default => $date->addMonth(),
        };
    }
}
