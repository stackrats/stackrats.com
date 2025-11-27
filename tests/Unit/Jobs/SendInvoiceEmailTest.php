<?php

use App\Actions\Invoice\GenerateInvoicePdfBinaryAction;
use App\Enums\InvoiceStatuses;
use App\Jobs\SendInvoiceEmail;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use Illuminate\Support\Facades\Mail;

it('sends invoice email with pdf attachment', function () {
    Mail::fake();

    // Ensure status exists
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();
    if (! $sentStatus) {
        $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);
    }

    $invoice = Invoice::factory()->create([
        'recipient_email' => 'test@example.com',
    ]);

    $mockAction = Mockery::mock(GenerateInvoicePdfBinaryAction::class);
    $mockAction->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(function ($arg) use ($invoice) {
            return $arg->id === $invoice->id;
        }))
        ->andReturn('pdf-binary-content');

    $job = new SendInvoiceEmail($invoice);
    $job->handle($mockAction);

    Mail::assertSent(InvoiceMail::class, function ($mail) use ($invoice) {
        return $mail->invoice->id === $invoice->id &&
               $mail->pdfBinary === 'pdf-binary-content' &&
               $mail->hasTo('test@example.com');
    });

    $invoice->refresh();
    expect($invoice->invoice_status_id)->toBe($sentStatus->id)
        ->and($invoice->last_sent_at)->not->toBeNull();
});
