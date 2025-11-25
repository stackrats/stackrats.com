<?php

use App\Actions\Invoice\CreateRecurringInvoiceAction;
use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use Illuminate\Support\Carbon;

it('creates a new invoice from a recurring parent and updates the next date', function () {
    // Arrange
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
    // Ensure statuses exist
    $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_date' => Carbon::parse('2023-01-01'),
        'invoice_status_id' => $sentStatus->id,
        'amount' => 100.00,
        'issue_date' => Carbon::parse('2022-12-01'),
        'due_date' => Carbon::parse('2022-12-08'), // 7 days terms
    ]);

    // Act
    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    // Assert
    expect($newInvoice)->toBeInstanceOf(Invoice::class)
        ->id->not->toBe($parentInvoice->id)
        ->amount->toEqual($parentInvoice->amount)
        ->is_recurring->toBeFalse()
        ->invoice_number->not->toBe($parentInvoice->invoice_number)
        ->issue_date->format('Y-m-d')->toBe('2023-01-01') // Should be the scheduled date
        ->due_date->format('Y-m-d')->toBe('2023-01-08'); // Should preserve the term gap (7 days)

    // Check parent updated
    $parentInvoice->refresh();
    expect($parentInvoice->next_recurring_date->format('Y-m-d'))->toBe('2023-02-01');
});

it('handles weekly frequency', function () {
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::WEEKLY->value]);
    $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_date' => Carbon::parse('2023-01-01'),
        'invoice_status_id' => $sentStatus->id,
    ]);

    $action = app(CreateRecurringInvoiceAction::class);
    $action->execute($parentInvoice);

    $parentInvoice->refresh();
    expect($parentInvoice->next_recurring_date->format('Y-m-d'))->toBe('2023-01-08');
});
