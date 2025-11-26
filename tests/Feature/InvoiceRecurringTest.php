<?php

use App\Enums\Currencies;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(\Database\Seeders\InvoiceStatusSeeder::class);
    seed(\Database\Seeders\RecurringFrequencySeeder::class);
});

test('can create invoice with next recurring date equal to issue date with time component', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);
    $status = InvoiceStatus::where('name', 'draft')->first();
    $issueDate = '2025-11-02';
    $nextRecurringDate = '2025-11-02T10:00';

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'invoice_status_id' => $status->id,
            'issue_date' => $issueDate,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'is_recurring' => true,
            'next_recurring_date' => $nextRecurringDate,
            'line_items' => [],
        ])
        ->assertRedirect('/invoices')
        ->assertSessionHasNoErrors();
});

test('can update invoice with next recurring date equal to issue date with time component', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'issue_date' => now(),
    ]);
    $issueDate = '2025-11-02';
    $nextRecurringDate = '2025-11-02T10:00';

    actingAs($user)
        ->put("/invoices/{$invoice->id}", [
            'contact_id' => $invoice->contact_id,
            'recipient_name' => $invoice->recipient_name,
            'recipient_email' => $invoice->recipient_email,
            'amount' => 100,
            'invoice_status_id' => $invoice->invoice_status_id,
            'issue_date' => $issueDate,
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => $invoice->currency,
            'is_recurring' => true,
            'next_recurring_date' => $nextRecurringDate,
            'line_items' => [],
        ])
        ->assertRedirect('/invoices')
        ->assertSessionHasNoErrors();
});

test('cannot create invoice with next recurring date before issue date', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);
    $status = InvoiceStatus::where('name', 'draft')->first();
    $issueDate = '2025-11-02';
    $pastDate = '2025-11-01T23:59';

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'invoice_status_id' => $status->id,
            'issue_date' => $issueDate,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'is_recurring' => true,
            'next_recurring_date' => $pastDate,
            'line_items' => [],
        ])
        ->assertSessionHasErrors(['next_recurring_date']);
});
