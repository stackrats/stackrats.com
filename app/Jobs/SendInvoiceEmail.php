<?php

namespace App\Jobs;

use App\Actions\Invoice\GenerateInvoicePdfBinaryAction;
use App\Enums\InvoiceStatuses;
use App\Events\InvoiceEmailSent;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
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
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
