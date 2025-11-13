<?php

use App\Mail\InvoiceMail;
use App\Models\Invoice;

test('invoice mail renders without errors and includes logo url', function () {
    $invoice = Invoice::factory()->create();

    $mailable = new InvoiceMail($invoice);

    $mailable->assertSeeInHtml($invoice->invoice_number);
    $mailable->assertSeeInHtml('stackrats-logo-light-600.png');
});
