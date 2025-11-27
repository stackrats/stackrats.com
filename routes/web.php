<?php

use App\Enums\InvoiceStatuses;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        // 'canRegister' => Features::enabled(Features::registration()),
        'canRegister' => false,
    ]);
})->name('home');

// Temporarily disable fortify registration routes while keeping the feature enabled
Route::get('register', function () {
    return Inertia::render('Welcome', [
        'canRegister' => false,
    ]);
})->name('register');
Route::post('register', function () {
    return redirect()->route('home');
})->name('register.store');

use App\Http\Controllers\DashboardController;

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Test route to preview invoice PDF template
Route::get('test-invoice-pdf', function () {
    $invoice = new \App\Models\Invoice([
        'invoice_number' => '2025112501',
        'recipient_name' => 'Think Solar',
        'recipient_email' => 'accounts@thinksolar.co.nz',
        'recipient_address' => '',
        'amount' => 7500.00,
        'currency' => \App\Enums\Currencies::NZD->value,
        'description' => 'Suite development',
        'line_items' => [
            ['description' => 'Suite development', 'quantity' => 1, 'unit_price' => 7500.00, 'unit_type' => \App\Enums\InvoiceUnitTypes::QUANTITY->value],
        ],
        'status' => InvoiceStatuses::DRAFT->value,
        'issue_date' => '2025-11-06',
        'due_date' => '2025-11-30',
        'is_recurring' => false,
    ]);

    return view('pdfs.invoice', [
        'invoice' => $invoice,
        'paymentDetails' => [
            'account' => '02-1290-0570435-000',
            'address' => "TransferWise\n55 Shoreditch High Street\nLondon E1 6JJ\nUnited Kingdom",
            'surcharge' => '3% credit card surcharge',
        ],
        'taxRate' => 15.0, // NZD GST
    ]);
});

// Test route to preview invoice email template
Route::get('test-invoice-email', function () {
    $invoice = new \App\Models\Invoice([
        'invoice_number' => '2025112501',
        'recipient_name' => 'Think Solar',
        'recipient_email' => 'accounts@thinksolar.co.nz',
        'recipient_address' => '',
        'amount' => 7500.00,
        'currency' => \App\Enums\Currencies::NZD->value,
        'description' => 'Suite development',
        'line_items' => [
            ['description' => 'Suite development', 'quantity' => 1, 'unit_price' => 7500.00, 'unit_type' => \App\Enums\InvoiceUnitTypes::QUANTITY->value],
        ],
        'status' => InvoiceStatuses::SENT->value,
        'issue_date' => '2025-11-06',
        'due_date' => '2025-12-06',
        'is_recurring' => false,
        'recurring_frequency' => null,
        'next_recurring_date' => null,
    ]);

    $logoPath = storage_path('app/public/images/logos/stackrats-logo-light-600.png');
    $logoUrl = file_exists($logoPath)
        ? asset('storage/images/logos/stackrats-logo-light-600.png')
        : asset('favicon/favicon.svg');

    return view('emails.invoice', [
        'invoice' => $invoice,
        'emailBody' => 'Please find attached your invoice.',
        'logoUrl' => $logoUrl,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::patch('invoices/{invoice}/status', [\App\Http\Controllers\InvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('invoices/{invoice}/send', [\App\Http\Controllers\InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/preview', [\App\Http\Controllers\InvoiceController::class, 'previewPdf'])->name('invoices.preview');
    Route::resource('contacts', \App\Http\Controllers\ContactController::class);
});

require __DIR__.'/settings.php';
