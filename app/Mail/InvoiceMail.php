<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public string $emailBody = 'Please find your invoice attached.',
        public ?string $pdfBinary = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        /** @var \Illuminate\Filesystem\FilesystemManager $storage */
        $storage = Storage::disk('public');

        return new Content(
            html: 'emails.invoice',
            with: [
                'logoUrl' => $storage->url('images/logos/stackrats-logo-light-600.png'),
                'invoice' => $this->invoice,
                'emailBody' => $this->emailBody,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfBinary) {
            return [
                Attachment::fromData(fn () => $this->pdfBinary, "invoice-{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
