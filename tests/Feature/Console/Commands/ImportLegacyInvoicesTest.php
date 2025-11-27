<?php

use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\InvoiceStatusSeeder;
use Illuminate\Support\Facades\File;

it('imports legacy invoice with two digit year', function () {
    $this->seed(InvoiceStatusSeeder::class);
    $user = User::factory()->create();
    $path = storage_path('app/testing/legacy_invoices');
    File::ensureDirectoryExists($path);

    $csvContent = "Invoice - #12345\nDate issued,05/07/25\nTotal,100.00\n";
    $filePath = $path.'/Invoice - #12345.csv';
    File::put($filePath, $csvContent);

    $this->artisan('invoices:import-legacy', ['path' => $path, '--user_id' => $user->id])
        ->assertExitCode(0);

    $invoice = Invoice::where('invoice_number', '12345')->first();
    expect($invoice)->not->toBeNull();

    // 05/07/25 -> May 7th, 2025 (m/d/y)
    expect($invoice->issue_date->format('Y-m-d'))->toBe('2025-05-07');
    expect($invoice->created_at->format('Y-m-d'))->toBe('2025-05-07');
    expect($invoice->updated_at->format('Y-m-d'))->toBe('2025-05-07');

    File::deleteDirectory($path);
});

it('imports legacy invoice with four digit year', function () {
    $this->seed(InvoiceStatusSeeder::class);
    $user = User::factory()->create();
    $path = storage_path('app/testing/legacy_invoices_4digit');
    File::ensureDirectoryExists($path);

    $csvContent = "Invoice - #12346\nDate issued,05/07/2025\nTotal,100.00\n";
    $filePath = $path.'/Invoice - #12346.csv';
    File::put($filePath, $csvContent);

    $this->artisan('invoices:import-legacy', ['path' => $path, '--user_id' => $user->id])
        ->assertExitCode(0);

    $invoice = Invoice::where('invoice_number', '12346')->first();
    expect($invoice)->not->toBeNull();

    expect($invoice->issue_date->format('Y-m-d'))->toBe('2025-05-07');
    expect($invoice->created_at->format('Y-m-d'))->toBe('2025-05-07');
    expect($invoice->updated_at->format('Y-m-d'))->toBe('2025-05-07');

    File::deleteDirectory($path);
});
