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

it('creates a new invoice from a recurring parent and marks parent as completed', function () {
    // Arrange
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
    // Ensure statuses exist
    $pendingStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::PENDING->value]);
    InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    // Parent invoice: issue Jan 1, due Jan 8 (7 days terms), next recurring Jan 1
    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => Carbon::parse('2023-01-01'),
        'invoice_status_id' => $pendingStatus->id,
        'amount' => 100.00,
        'issue_date' => Carbon::parse('2023-01-01'),
        'due_date' => Carbon::parse('2023-01-08'), // 7 days terms
    ]);

    // Act
    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    // Assert new invoice - dates should be based on the NEW next_recurring_at (Feb 1)
    expect($newInvoice)->toBeInstanceOf(Invoice::class)
        ->id->not->toBe($parentInvoice->id)
        ->amount->toEqual($parentInvoice->amount)
        ->is_recurring->toBeTrue()
        ->invoice_number->not->toBe($parentInvoice->invoice_number)
        ->next_recurring_at->format('Y-m-d')->toBe('2023-02-01') // Next month
        ->issue_date->format('Y-m-d')->toBe('2023-02-01') // Issue date = next_recurring_at
        ->due_date->format('Y-m-d')->toBe('2023-02-08') // Due date = issue + 7 days terms
        ->parent_invoice_id->toBe($parentInvoice->id);

    // New invoice should be PENDING (waiting for its turn)
    expect($newInvoice->invoice_status_id)->toBe($pendingStatus->id);
    expect($newInvoice->recurring_completed_at)->toBeNull();

    // Check parent updated - keeps is_recurring, gets completed_at
    // Note: Status is updated to SENT by SendInvoiceEmail job, not by this action
    $parentInvoice->refresh();
    expect($parentInvoice->is_recurring)->toBeTrue();
    expect($parentInvoice->next_recurring_at)->not->toBeNull(); // Keeps for history
    expect($parentInvoice->recurring_completed_at)->not->toBeNull();
    expect($parentInvoice->invoice_status_id)->toBe($pendingStatus->id); // Still PENDING until job runs
});

it('handles weekly frequency', function () {
    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::WEEKLY->value]);
    $pendingStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::PENDING->value]);
    InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    // Parent: issue Jan 1, due Jan 15 (14 days terms), next recurring Jan 1
    $parentInvoice = Invoice::factory()->create([
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => Carbon::parse('2023-01-01'),
        'invoice_status_id' => $pendingStatus->id,
        'issue_date' => Carbon::parse('2023-01-01'),
        'due_date' => Carbon::parse('2023-01-15'), // 14 days terms
    ]);

    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    // Parent marked as completed but status stays PENDING until SendInvoiceEmail job runs
    $parentInvoice->refresh();
    expect($parentInvoice->is_recurring)->toBeTrue();
    expect($parentInvoice->recurring_completed_at)->not->toBeNull();
    expect($parentInvoice->invoice_status_id)->toBe($pendingStatus->id);

    // New invoice: next_recurring_at = Jan 8 (weekly), issue = Jan 8, due = Jan 22 (14 days terms)
    expect($newInvoice->is_recurring)->toBeTrue();
    expect($newInvoice->invoice_status_id)->toBe($pendingStatus->id);
    expect($newInvoice->next_recurring_at->format('Y-m-d'))->toBe('2023-01-08');
    expect($newInvoice->issue_date->format('Y-m-d'))->toBe('2023-01-08');
    expect($newInvoice->due_date->format('Y-m-d'))->toBe('2023-01-22'); // Jan 8 + 14 days
});

it('creates invoice with issue date respecting user timezone', function () {
    // Arrange
    // Create user with Pacific/Auckland timezone (UTC+13 in DST Jan)
    $user = User::factory()->create();
    $user->userSetting()->update([
        'timezone' => Timezones::PACIFIC_AUCKLAND,
    ]);

    $frequency = RecurringFrequency::factory()->create(['name' => RecurringFrequencies::MONTHLY->value]);
    $pendingStatus = InvoiceStatus::factory()->create(['name' => InvoiceStatuses::PENDING->value]);
    InvoiceStatus::factory()->create(['name' => InvoiceStatuses::SENT->value]);

    // 2023-01-01 12:00:00 UTC
    // In Pacific/Auckland (UTC+13), this is 2023-01-02 01:00:00
    $utcDate = Carbon::parse('2023-01-01 12:00:00', 'UTC');

    $parentInvoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'is_recurring' => true,
        'recurring_frequency_id' => $frequency->id,
        'next_recurring_at' => $utcDate,
        'invoice_status_id' => $pendingStatus->id,
        'issue_date' => Carbon::parse('2023-01-02'), // Parent issued Jan 2 (Auckland time)
        'due_date' => Carbon::parse('2023-01-09'), // 7 days terms
    ]);

    // Act
    $action = app(CreateRecurringInvoiceAction::class);
    $newInvoice = $action->execute($parentInvoice);

    // Assert
    // The NEW invoice's next_recurring_at is parent's + 1 month
    // Parent's next_recurring_at in Auckland: 2023-01-02 01:00:00
    // New invoice's next_recurring_at in Auckland: 2023-02-02 01:00:00
    $nextDateAuckland = $newInvoice->next_recurring_at->setTimezone(Timezones::PACIFIC_AUCKLAND->value);
    expect($nextDateAuckland->format('Y-m-d'))->toBe('2023-02-02');

    // The NEW invoice's issue_date should be based on its next_recurring_at (Feb 2 Auckland time)
    expect($newInvoice->issue_date->format('Y-m-d'))->toBe('2023-02-02');

    // Due date should preserve the 7 day terms: Feb 2 + 7 = Feb 9
    expect($newInvoice->due_date->format('Y-m-d'))->toBe('2023-02-09');
});
