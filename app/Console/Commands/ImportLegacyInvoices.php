<?php

namespace App\Console\Commands;

use App\Enums\Currencies;
use App\Enums\InvoiceStatuses;
use App\Enums\InvoiceUnitTypes;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;
use Smalot\PdfParser\Parser;

class ImportLegacyInvoices extends Command
{
    protected $signature = 'invoices:import-legacy {path : The path to the directory containing CSV files or a single CSV file} {--user_id= : The ID of the user to assign these invoices to}';

    protected $description = 'Import legacy invoice numbers from CSV to prevent reuse';

    public function handle()
    {
        $path = $this->argument('path');
        $userId = $this->option('user_id');

        $user = $userId ? User::find($userId) : User::first();

        if (! $user) {
            $this->error('No user found to assign invoices to.');

            return 1;
        }

        if (File::isDirectory($path)) {
            $files = File::files($path);
            $csvFiles = array_filter($files, fn ($file) => str_ends_with(strtolower($file->getFilename()), '.csv'));

            if (empty($csvFiles)) {
                $this->error("No CSV files found in directory: $path");

                return 1;
            }

            $total = 0;
            foreach ($csvFiles as $file) {
                $this->info('Processing '.$file->getFilename().'...');
                $this->importFromCsv($file->getPathname(), $user);
            }

            return 0;
        } elseif (File::isFile($path) && str_ends_with(strtolower($path), '.csv')) {
            return $this->importFromCsv($path, $user);
        }

        $this->error("Path must be a directory containing CSVs or a .csv file: $path");

        return 1;
    }

    protected function importFromCsv(string $path, User $user): int
    {
        try {
            $csv = Reader::createFromPath($path, 'r');
            // Do not set header offset yet, we need to inspect the file
        } catch (\Exception $e) {
            $this->error('Could not read CSV file: '.$e->getMessage());

            return 1;
        }

        // If the file was converted from ODS/Excel, it might not have a clean header row at line 0.
        // It seems the ODS conversion results in a layout-preserved CSV.
        // We should try to extract data by position or regex if it's unstructured.

        // Check if filename contains invoice number
        $filename = basename($path);
        $invoiceNumber = null;
        if (preg_match('/Invoice - #(\d+)/i', $filename, $matches)) {
            $invoiceNumber = $matches[1];
        }

        // If we have an invoice number from filename, we can try to scrape the rest from the CSV content
        if ($invoiceNumber) {
            return $this->scrapeUnstructuredCsv($csv, $invoiceNumber, $user, $filename, $path);
        }

        // Fallback to standard column-based CSV import
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        $normalizedHeader = array_map('strtolower', $header);
        $headerMap = array_combine($normalizedHeader, $header);

        $numberKey = $this->findKey($headerMap, ['invoice_number', 'number', 'invoice']);

        if (! $numberKey) {
            $this->error("Could not find 'invoice_number', 'number', or 'invoice' column in CSV.");

            return 1;
        }

        // ... (rest of standard CSV logic)
        $emailKey = $this->findKey($headerMap, ['email', 'recipient_email']);
        $amountKey = $this->findKey($headerMap, ['amount', 'total']);
        $dateKey = $this->findKey($headerMap, ['date', 'issue_date']);
        $nameKey = $this->findKey($headerMap, ['name', 'recipient_name', 'client']);

        $count = 0;
        foreach ($csv->getRecords() as $record) {
            $number = $record[$numberKey] ?? null;
            if (! $number) {
                continue;
            }

            $email = $emailKey && isset($record[$emailKey]) ? $record[$emailKey] : 'legacy@example.com';
            $amount = $amountKey && isset($record[$amountKey]) ? $this->parseAmount($record[$amountKey]) : 0.0;
            $date = $dateKey && isset($record[$dateKey]) ? $record[$dateKey] : now();
            $name = $nameKey && isset($record[$nameKey]) ? $record[$nameKey] : 'Legacy Import';

            if ($this->createInvoice($number, $user, 'Imported from CSV', $email, $amount, $date, $name, [])) {
                $count++;
            }
        }

        $this->info("Imported $count legacy invoices from CSV.");

        return 0;
    }

    protected function scrapeUnstructuredCsv(Reader $csv, string $number, User $user, string $filename, string $csvPath): int
    {
        $records = iterator_to_array($csv->getRecords());
        $text = '';
        foreach ($records as $record) {
            $text .= implode(' ', $record)."\n";
        }

        $amount = 0.0;
        $date = now();
        $email = 'legacy@example.com';
        $name = 'Legacy Import';
        $description = 'Imported from legacy ODS/CSV: '.$filename;
        $lineItems = [];

        // Try to find Total
        // In the CSV provided: "Total,,,,,,,7,500.00 NZD" or similar
        // Or just sum up amounts? No, look for "Total" row.
        // The CSV dump shows: "Total,,,,,,,7,500.00 NZD" at the bottom usually.
        // Let's look for "Total" in the text dump.
        if (preg_match('/Total.*?([\d,]+\.?\d*)/i', $text, $amountMatches)) {
            $amount = $this->parseAmount($amountMatches[1]);
        }

        // Date: "Date issued,04/28/2024"
        if (preg_match('/Date issued\s*,?\s*(\d{1,2}\/\d{1,2}\/\d{2,4})/i', $text, $dateMatches)) {
            $dateString = $dateMatches[1];
            $formats = ['m/d/Y', 'd/m/Y', 'm/d/y', 'd/m/y'];

            foreach ($formats as $format) {
                try {
                    $parsedDate = \Carbon\Carbon::createFromFormat($format, $dateString);
                    $lastErrors = \DateTime::getLastErrors();

                    if (is_array($lastErrors) && ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0)) {
                        continue;
                    }

                    // If we used 'Y' (4 digits) but got a year < 1000, it was likely a 2-digit year
                    if (str_contains($format, 'Y') && $parsedDate->year < 1000) {
                        continue;
                    }

                    $date = $parsedDate;
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Check for poisoned date (2025-11-26) - likely export date
        $poisonedDate = \Carbon\Carbon::create(2025, 11, 26)->startOfDay();
        if ($date->startOfDay()->eq($poisonedDate)) {

            // Try to find PDF
            $pdfPath = str_replace('.csv', '.pdf', $csvPath);
            if (File::exists($pdfPath)) {
                $pdfDate = $this->parseDateFromPdf($pdfPath);
                if ($pdfDate) {
                    $this->info("Date {$date->format('Y-m-d')} looks like export date. Found correct date in PDF: ".$pdfDate->format('Y-m-d'));
                    $date = $pdfDate;
                } else {
                    // Fallback to invoice number parsing if PDF parsing failed
                    $invoiceDate = $this->parseDateFromInvoiceNumber($number);
                    if ($invoiceDate) {
                        $this->info("Date {$date->format('Y-m-d')} looks like export date. PDF parsing failed. Using date from invoice number: ".$invoiceDate->format('Y-m-d'));
                        $date = $invoiceDate;
                    }
                }
            } else {
                // Fallback to ODS parsing if no PDF
                $odsPath = str_replace('.csv', '.ods', $csvPath);
                if (File::exists($odsPath)) {
                    $odsDate = $this->parseDateFromOds($odsPath);
                    if ($odsDate) {
                        $this->info("Date {$date->format('Y-m-d')} looks like export date. Found correct date in ODS: ".$odsDate->format('Y-m-d'));
                        $date = $odsDate;
                    } else {
                        // Fallback to invoice number parsing if ODS parsing failed
                        $invoiceDate = $this->parseDateFromInvoiceNumber($number);
                        if ($invoiceDate) {
                            $this->info("Date {$date->format('Y-m-d')} looks like export date. ODS parsing failed. Using date from invoice number: ".$invoiceDate->format('Y-m-d'));
                            $date = $invoiceDate;
                        }
                    }
                } else {
                    // Fallback to invoice number parsing if no PDF and no ODS
                    $invoiceDate = $this->parseDateFromInvoiceNumber($number);
                    if ($invoiceDate) {
                        $this->info("Date {$date->format('Y-m-d')} looks like export date. No PDF or ODS found. Using date from invoice number: ".$invoiceDate->format('Y-m-d'));
                        $date = $invoiceDate;
                    }
                }
            }
        }

        // Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $emailMatches)) {
            $email = $emailMatches[0];
        }

        // Name: "Billing to,,,..." next line "Think Solar"
        // This is hard in flattened text.
        // Let's iterate rows to find "Billing to"
        $billingToIndex = -1;
        foreach ($records as $index => $record) {
            if (str_contains(implode(' ', $record), 'Billing to')) {
                $billingToIndex = $index;
                break;
            }
        }

        if ($billingToIndex !== -1 && isset($records[$billingToIndex + 1])) {
            // The name should be in the first non-empty column of the next row
            foreach ($records[$billingToIndex + 1] as $col) {
                if (! empty(trim($col))) {
                    $name = trim($col);
                    break;
                }
            }
        }

        // Description: Look for "Description" header
        $descriptionIndex = -1;
        foreach ($records as $index => $record) {
            if (str_contains(implode(' ', $record), 'Description')) {
                $descriptionIndex = $index;
                break;
            }
        }

        if ($descriptionIndex !== -1) {
            $headerRow = $records[$descriptionIndex];
            $descCol = -1;
            $qtyCol = -1;
            $rateCol = -1;
            $amountCol = -1;
            $unitTypeName = InvoiceUnitTypes::QUANTITY->value;

            // Identify columns
            foreach ($headerRow as $i => $col) {
                $colLower = strtolower(trim($col));
                if (str_contains($colLower, 'description')) {
                    $descCol = $i;
                } elseif (str_contains($colLower, 'months')) {
                    $qtyCol = $i;
                    $unitTypeName = InvoiceUnitTypes::MONTHS->value;
                } elseif (str_contains($colLower, 'days')) {
                    $qtyCol = $i;
                    $unitTypeName = InvoiceUnitTypes::DAYS->value;
                } elseif (str_contains($colLower, 'quantity') || str_contains($colLower, 'qty')) {
                    $qtyCol = $i;
                    $unitTypeName = InvoiceUnitTypes::QUANTITY->value;
                } elseif (str_contains($colLower, 'rate') || str_contains($colLower, 'price')) {
                    $rateCol = $i;
                } elseif (str_contains($colLower, 'amount')) {
                    $amountCol = $i;
                }
            }

            // Extract Description from the first row
            if ($descCol !== -1 && isset($records[$descriptionIndex + 1][$descCol]) && ! empty(trim($records[$descriptionIndex + 1][$descCol]))) {
                $description = trim($records[$descriptionIndex + 1][$descCol]);
            }

            // Extract Line Items
            if ($descCol !== -1) {
                for ($i = $descriptionIndex + 1; $i < count($records); $i++) {
                    $row = $records[$i];
                    $firstCol = trim($row[$descCol] ?? '');

                    // Stop if we hit totals or empty description
                    if (empty($firstCol) ||
                        str_contains(strtolower($firstCol), 'total') ||
                        str_contains(strtolower($firstCol), 'subtotal') ||
                        str_contains(strtolower($firstCol), 'gst')) {
                        break;
                    }

                    $itemDesc = $row[$descCol];
                    $itemQty = ($qtyCol !== -1 && isset($row[$qtyCol])) ? (float) $this->parseAmount($row[$qtyCol]) : 1;
                    $itemRate = ($rateCol !== -1 && isset($row[$rateCol])) ? $this->parseAmount($row[$rateCol]) : 0;
                    $itemAmount = ($amountCol !== -1 && isset($row[$amountCol])) ? $this->parseAmount($row[$amountCol]) : 0;

                    if ($itemRate == 0 && $itemAmount != 0 && $itemQty != 0) {
                        $itemRate = $itemAmount / $itemQty;
                    }

                    $lineItems[] = [
                        'description' => $itemDesc,
                        'quantity' => $itemQty,
                        'unit_price' => $itemRate,
                        'amount' => $itemAmount,
                        'currency' => Currencies::NZD->value,
                        'unit_type' => $unitTypeName,
                    ];
                }
            }
        }

        if ($this->createInvoice($number, $user, $description, $email, $amount, $date, $name, $lineItems)) {
            return 0;
        }

        return 1;
    }

    protected function findKey(array $map, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $map)) {
                return $map[$candidate];
            }
        }

        return null;
    }

    protected function parseDateFromOds(string $odsPath): ?\Carbon\Carbon
    {
        try {
            $zip = new \ZipArchive;
            if ($zip->open($odsPath) === true) {
                $content = $zip->getFromName('content.xml');
                $zip->close();

                if ($content) {
                    // Look for office:date-value="2025-11-06"
                    if (preg_match('/office:date-value="(\d{4}-\d{2}-\d{2})"/', $content, $matches)) {
                        return \Carbon\Carbon::parse($matches[1]);
                    }

                    // Fallback to text content
                    if (preg_match('/>(\d{2}\/\d{2}\/\d{2})</', $content, $matches)) {
                        try {
                            return \Carbon\Carbon::createFromFormat('d/m/y', $matches[1]);
                        } catch (\Exception $e) {
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    protected function parseDateFromPdf(string $pdfPath): ?\Carbon\Carbon
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();

            // Look for date patterns in the PDF text
            if (preg_match('/Date issued\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches) ||
                preg_match('/Date\s*:?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {

                $dateString = $matches[1];
                $formats = ['d/m/Y', 'm/d/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'm/d/y'];

                foreach ($formats as $format) {
                    try {
                        $parsedDate = \Carbon\Carbon::createFromFormat($format, $dateString);
                        $lastErrors = \DateTime::getLastErrors();

                        if (is_array($lastErrors) && ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0)) {
                            continue;
                        }

                        // Sanity check year
                        if ($parsedDate->year < 2000 || $parsedDate->year > 2030) {
                            if ($parsedDate->year < 1000 && str_contains($format, 'Y')) {
                                continue;
                            }
                        }

                        return $parsedDate;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            // $this->error("Error parsing PDF $pdfPath: " . $e->getMessage());
            return null;
        }
    }

    protected function parseDateFromInvoiceNumber(string $number): ?\Carbon\Carbon
    {
        // Expecting format XXXYYMMDD or similar where last 6 are YYMMDD
        if (preg_match('/(\d{2})(\d{2})(\d{2})$/', $number, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            // Adjust year to 20YY
            $year += 2000;

            try {
                return \Carbon\Carbon::createFromDate($year, $month, $day);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    protected function parseAmount(string $amount): float
    {
        // Remove non-numeric characters except dot
        $amount = preg_replace('/[^\d.]/', '', $amount);

        return (float) $amount;
    }

    protected function createInvoice(string $number, User $user, string $description, string $email = 'legacy@example.com', float $amount = 0.0, $date = null, string $name = 'Legacy Import', array $lineItems = []): bool
    {
        $invoice = Invoice::where('invoice_number', $number)->first();
        $issueDate = $date ?? now();

        if ($invoice) {
            $invoice->fill([
                'issue_date' => $issueDate,
                'due_date' => $issueDate,
                'amount' => $amount,
                'description' => $description,
                'line_items' => $lineItems,
            ]);
            $invoice->created_at = $issueDate;
            $invoice->updated_at = $issueDate;
            $invoice->save();

            return true;
        }

        // Find or create contact
        $contact = Contact::firstOrCreate(
            ['email' => $email, 'user_id' => $user->id],
            ['name' => $name]
        );

        $this->info("Importing legacy invoice $number with amount $amount for contact {$contact->name}...");

        $paidStatus = InvoiceStatus::where('name', InvoiceStatuses::PAID->value)->first();

        $invoice = new Invoice([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'invoice_number' => $number,
            'recipient_name' => $name,
            'recipient_email' => $email,
            'amount' => $amount,
            'currency' => Currencies::NZD->value,
            'issue_date' => $issueDate,
            'due_date' => $issueDate,
            'description' => $description,
            'line_items' => $lineItems,
            'invoice_status_id' => $paidStatus?->id,
        ]);
        $invoice->created_at = $issueDate;
        $invoice->updated_at = $issueDate;
        $invoice->save();

        return true;
    }
}
