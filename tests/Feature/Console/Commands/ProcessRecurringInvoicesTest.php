<?php

use App\Console\Commands\ProcessRecurringInvoices;
use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Jobs\SendInvoiceEmail;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // Ensure statuses and frequencies exist
    if (InvoiceStatus::count() === 0) {
        InvoiceStatus::factory()->create(['name' => InvoiceStatuses::DRAFT->value]);
        InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);
        InvoiceStatus::factory()->create(['name' => InvoiceStatuses::PAID->value]);
    }

    if (RecurringFrequency::count() === 0) {
        RecurringFrequency::factory()->create(['name' => RecurringFrequencies::WEEKLY->value]);
        RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
        RecurringFrequency::factory()->create(['name' => RecurringFrequencies::QUARTERLY->value]);
        RecurringFrequency::factory()->create(['name' => RecurringFrequencies::YEARLY->value]);
    }
});

it('processes recurring invoices and pushes email job to queue', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

    // Create a recurring invoice due today
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now(),
        'invoice_number' => 'INV-TEST-001',
        'invoice_status_id' => $sentStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    // Find the newly created invoice (it should be the one that is NOT the original)
    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();
    expect($newInvoice)->not->toBeNull();

    // Assert job was pushed with the new invoice
    Queue::assertPushed(SendInvoiceEmail::class, function ($job) use ($newInvoice) {
        return $job->invoice->id === $newInvoice->id;
    });

    // Assert parent invoice was updated
    $invoice->refresh();
    expect($invoice->is_recurring)->toBeFalse();
    expect($invoice->next_recurring_at)->toBeNull();

    // Assert new invoice has recurring data
    $newInvoice->refresh();
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->next_recurring_at->toDateString())->toBe(now()->addMonth()->toDateString());
});

it('processes recurring invoices with custom date option', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

    // Create a recurring invoice due next month
    $futureDate = now()->addMonth()->startOfDay();
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => $futureDate,
        'invoice_number' => 'INV-TEST-FUTURE',
        'invoice_status_id' => $sentStatus->id,
    ]);

    // Run command with date option matching the due date
    artisan(ProcessRecurringInvoices::class, ['--date' => $futureDate->toDateString()])
        ->assertExitCode(0);

    Queue::assertPushed(SendInvoiceEmail::class);

    $invoice->refresh();
    // Parent should stop recurring
    expect($invoice->is_recurring)->toBeFalse();
    expect($invoice->next_recurring_at)->toBeNull();

    // New invoice should be recurring
    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->next_recurring_at->toDateString())->toBe($futureDate->copy()->addMonth()->toDateString());
});

it('does not process invoices not yet due', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

    // Create a recurring invoice due tomorrow
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->addDay(),
        'invoice_number' => 'INV-TEST-NOT-DUE',
        'invoice_status_id' => $sentStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    Queue::assertNotPushed(SendInvoiceEmail::class);
});

it('does not process draft invoices', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $draftStatus = InvoiceStatus::where('name', InvoiceStatuses::DRAFT->value)->first();

    // Create a recurring invoice due today but in DRAFT status
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now(),
        'invoice_number' => 'INV-TEST-DRAFT',
        'invoice_status_id' => $draftStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    Queue::assertNotPushed(SendInvoiceEmail::class);
});
