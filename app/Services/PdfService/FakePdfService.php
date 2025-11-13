<?php

declare(strict_types=1);

namespace App\Services\PdfService;

class FakePdfService extends PdfService
{
    /**
     * Generate a simple fake PDF file
     */
    public function generatePdfFromHtml($html = ''): string
    {
        info(['FakePdfService is being used. You are likely in a testing environment, otherwise change the configuration.']);

        return <<<'EOF'
            %PDF-1.7

            1 0 obj
            << /Type /Catalog /Pages 2 0 R >>
            endobj

            2 0 obj
            << /Type /Pages /Kids [3 0 R] /Count 1 >>
            endobj

            3 0 obj
            << /Type /Page /Parent 2 0 R /Resources << >> /MediaBox [0 0 595.28 841.89] >>
            endobj

            xref
            0 4
            0000000000 65535 f
            0000000010 00000 n
            0000000067 00000 n
            0000000123 00000 n

            trailer
            << /Size 4 /Root 1 0 R >>
            startxref
            [byte offset to xref]
            %%EOF
        EOF;
    }
}
