<?php

declare(strict_types=1);

namespace App\Services\PdfService;

use Exception;
use Illuminate\Support\Facades\Http;

class PdfService
{
    protected string $pdfServiceToken;

    protected string $pdfServiceUrl;

    public function __construct()
    {
        $this->pdfServiceToken = config('services.pdf_service.token');
        $this->pdfServiceUrl = config('services.pdf_service.url');
    }

    /** @throws Exception */
    public function generatePdfFromHtml(string $html): string
    {
        $response = Http::post($this->pdfServiceUrl, [
            'token' => $this->pdfServiceToken,
            'html' => $html,
        ]);

        if ($response->successful()) {
            return $response->body();
        } else {
            // Capture error message from the PDF service
            $errorMessage = $response->json('error', 'Unknown error');
            throw new Exception($errorMessage, $response->status());
        }
    }
}
