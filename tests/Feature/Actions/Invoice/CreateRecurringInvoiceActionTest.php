<?php

use App\Actions\Invoice\CreateRecurringInvoiceAction;
use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Enums\Timezones;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use App\Models\User;
use Illuminate\Support\Carbon;

it('creates a new invoice from a recurring parent and updates the next date', function () {
    // Arrange
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
    // Ensure statuses exist
    $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => Carbon::parse('2023-01-01'),
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
        ->is_recurring->toBeTrue()
        ->invoice_number->not->toBe($parentInvoice->invoice_number)
        ->issue_date->format('Y-m-d')->toBe('2023-01-01') // Should be the scheduled date
        ->due_date->format('Y-m-d')->toBe('2023-01-08') // Should preserve the term gap (7 days)
        ->next_recurring_at->format('Y-m-d')->toBe('2023-02-01');

    // Check parent updated
    $parentInvoice->refresh();
    expect($parentInvoice->is_recurring)->toBeFalse();
    expect($parentInvoice->next_recurring_at)->toBeNull();
});

it('handles weekly frequency', function () {
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::WEEKLY->value]);
    $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => Carbon::parse('2023-01-01'),
        'invoice_status_id' => $sentStatus->id,
    ]);

    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    $parentInvoice->refresh();
    expect($parentInvoice->is_recurring)->toBeFalse();
    expect($parentInvoice->next_recurring_at)->toBeNull();

    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->next_recurring_at->format('Y-m-d'))->toBe('2023-01-08');
});

it('creates invoice with issue date respecting user timezone', function () {
    // Arrange
    // Create user with Pacific/Auckland timezone (UTC+13 in DST Jan)
    $user = User::factory()->create();
    $user->userSetting()->update([
        'timezone' => Timezones::PACIFIC_AUCKLAND,
    ]);

    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
    $sentStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    // 2023-01-01 12:00:00 UTC
    // In Pacific/Auckland (UTC+13), this is 2023-01-02 01:00:00
    $utcDate = Carbon::parse('2023-01-01 12:00:00', 'UTC');

    $parentInvoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => $utcDate,
        'invoice_status_id' => $sentStatus->id,
        'issue_date' => Carbon::parse('2022-12-01'),
        'due_date' => Carbon::parse('2022-12-08'),
    ]);

    // Act
    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    // Assert
    // The issue date should be 2023-01-02 because of the timezone conversion
    expect($newInvoice->issue_date->format('Y-m-d'))->toBe('2023-01-02');

    // Verify the next recurring date is also calculated correctly
    // The parent's next_recurring_at should be updated to next month
    // 2023-01-02 01:00:00 + 1 month = 2023-02-02 01:00:00 Auckland time
    // Converted back to UTC, it depends on DST, but let's check the date part in Auckland time
    $nextDateAuckland = $newInvoice->next_recurring_at->setTimezone(Timezones::PACIFIC_AUCKLAND->value);
    expect($nextDateAuckland->format('Y-m-d'))->toBe('2023-02-02');
});
