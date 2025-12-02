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
        InvoiceStatus::factory()->create(['name' => InvoiceStatuses::PENDING->value]);
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

it('processes recurring invoices and sends email for the parent invoice', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();

    // Create a recurring invoice due today in PENDING status
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->startOfDay(),
        'invoice_number' => 'INV-TEST-001',
        'invoice_status_id' => $pendingStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    // Find the newly created invoice (it should be the one that is NOT the original)
    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();
    expect($newInvoice)->not->toBeNull();

    // Assert job was pushed with the PARENT invoice (not the new one)
    Queue::assertPushed(SendInvoiceEmail::class, function ($job) use ($invoice) {
        return $job->invoice->id === $invoice->id;
    });

    // Assert parent invoice was marked as completed but status stays PENDING until job runs
    $invoice->refresh();
    expect($invoice->is_recurring)->toBeTrue();
    expect($invoice->next_recurring_at)->not->toBeNull(); // Keeps the date for history
    expect($invoice->recurring_completed_at)->not->toBeNull();
    expect($invoice->invoice_status_id)->toBe($pendingStatus->id); // Still PENDING until job runs

    // Assert new invoice is PENDING and has recurring data
    $newInvoice->refresh();
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->recurring_completed_at)->toBeNull(); // Not completed yet
    expect($newInvoice->invoice_status_id)->toBe($pendingStatus->id); // PENDING, waiting for its turn
    expect($newInvoice->parent_invoice_id)->toBe($invoice->id);
    expect($newInvoice->next_recurring_at->toDateString())->toBe(now()->startOfDay()->addMonth()->toDateString());
});

it('processes recurring invoices with custom date option', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();

    // Create a recurring invoice due next month
    $futureDate = now()->addMonth()->startOfDay();
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => $futureDate,
        'invoice_number' => 'INV-TEST-FUTURE',
        'invoice_status_id' => $pendingStatus->id,
    ]);

    // Run command with date option matching the due date
    artisan(ProcessRecurringInvoices::class, ['--date' => $futureDate->toDateString()])
        ->assertExitCode(0);

    // Email should be sent for the parent invoice
    Queue::assertPushed(SendInvoiceEmail::class, function ($job) use ($invoice) {
        return $job->invoice->id === $invoice->id;
    });

    $invoice->refresh();
    // Parent should be marked as completed but status stays PENDING until job runs
    expect($invoice->is_recurring)->toBeTrue();
    expect($invoice->recurring_completed_at)->not->toBeNull();
    expect($invoice->invoice_status_id)->toBe($pendingStatus->id);

    // New invoice should be PENDING and recurring
    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->invoice_status_id)->toBe($pendingStatus->id);
    expect($newInvoice->next_recurring_at->toDateString())->toBe($futureDate->copy()->addMonth()->toDateString());
});

it('does not process invoices not yet due', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();

    // Create a recurring invoice due tomorrow
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->addDay(),
        'invoice_number' => 'INV-TEST-NOT-DUE',
        'invoice_status_id' => $pendingStatus->id,
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

it('does not process sent invoices since new invoices are created as pending', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

    // Create a recurring invoice due today but already in SENT status
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now(),
        'invoice_number' => 'INV-TEST-SENT',
        'invoice_status_id' => $sentStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    Queue::assertNotPushed(SendInvoiceEmail::class);
});

it('sends email for the parent pending invoice and creates new pending invoice', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();

    // Create a recurring invoice due today in PENDING status
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->startOfDay(),
        'invoice_number' => 'INV-TEST-PENDING',
        'invoice_status_id' => $pendingStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    // Find the newly created invoice
    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();
    expect($newInvoice)->not->toBeNull();

    // Assert job was pushed with the PARENT invoice (the one being sent)
    Queue::assertPushed(SendInvoiceEmail::class, function ($job) use ($invoice) {
        return $job->invoice->id === $invoice->id;
    });

    // Assert parent invoice was marked as completed but status stays PENDING until job runs
    $invoice->refresh();
    expect($invoice->is_recurring)->toBeTrue();
    expect($invoice->recurring_completed_at)->not->toBeNull();
    expect($invoice->invoice_status_id)->toBe($pendingStatus->id);

    // Assert new invoice is PENDING (waiting for its turn)
    $newInvoice->refresh();
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->recurring_completed_at)->toBeNull();
    expect($newInvoice->invoice_status_id)->toBe($pendingStatus->id);
    expect($newInvoice->parent_invoice_id)->toBe($invoice->id);
    expect($newInvoice->next_recurring_at->toDateString())->toBe(now()->startOfDay()->addMonth()->toDateString());
});

it('does not process already completed recurring invoices', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();

    // Create a recurring invoice that was already processed (has recurring_completed_at)
    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->startOfDay(),
        'recurring_completed_at' => now()->subDay(), // Already completed
        'invoice_number' => 'INV-TEST-COMPLETED',
        'invoice_status_id' => $sentStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    // No email should be sent for already completed invoices
    Queue::assertNotPushed(SendInvoiceEmail::class);
});

it('only sends email for the parent invoice not the new child invoice', function () {
    Queue::fake();

    $monthly = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
    $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();

    $invoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $monthly->id,
        'next_recurring_at' => now()->startOfDay(),
        'invoice_number' => 'INV-PARENT-001',
        'invoice_status_id' => $pendingStatus->id,
    ]);

    artisan(ProcessRecurringInvoices::class)
        ->assertExitCode(0);

    $newInvoice = Invoice::where('id', '!=', $invoice->id)->first();

    // Assert only ONE email job was pushed
    Queue::assertPushed(SendInvoiceEmail::class, 1);

    // Assert the email was for the PARENT invoice
    Queue::assertPushed(SendInvoiceEmail::class, function ($job) use ($invoice) {
        return $job->invoice->id === $invoice->id;
    });

    // Assert the email was NOT for the new child invoice
    Queue::assertNotPushed(SendInvoiceEmail::class, function ($job) use ($newInvoice) {
        return $job->invoice->id === $newInvoice->id;
    });
});
