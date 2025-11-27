<?php

declare(strict_types=1);

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Services\PdfService\PdfService;

class GenerateInvoicePdfBinaryAction
{
    public function __construct(
        private PdfService $pdfService,
        private GetPdfImageUrlAction $getPdfImageUrlAction,
    ) {}

    /**
     * Generate a PDF binary for the given invoice
     *
     * @throws \Exception
     */
    public function handle(Invoice $invoice): string
    {
        $html = view('pdfs.invoice', [
            'invoice' => $invoice,
            'paymentDetails' => $this->getPaymentDetails(),
            'taxRate' => $this->getTaxRate($invoice),
            'logoUrl' => $this->getPdfImageUrlAction->handle('public', 'images/logos/stackrats-logo-light-600.png'),
        ])->render();

        return $this->pdfService->generatePdfFromHtml($html);
    }

    /**
     * Get payment details for the invoice
     */
    private function getPaymentDetails(): array
    {
        return [
            'name' => config('invoice.payment_full_name', config('app.name')),
            'account' => config('invoice.payment_account', 'Payment details available upon request'),
            'address' => config('invoice.payment_address', ''),
            'surcharge' => config('invoice.payment_surcharge', ''),
        ];
    }

    /**
     * Get tax rate for the invoice (GST, VAT, etc.)
     */
    private function getTaxRate(Invoice $invoice): float
    {
        // You can customize this based on currency or other factors
        $taxRates = [
            'NZD' => 15.0, // GST in New Zealand
            'AUD' => 10.0, // GST in Australia
            'GBP' => 20.0, // VAT in UK
            'EUR' => 0.0,  // Varies by country
            'USD' => 0.0,  // Sales tax varies by state
            'CAD' => 0.0,  // Varies by province
        ];

        return $taxRates[$invoice->currency] ?? 0.0;
    }
}
