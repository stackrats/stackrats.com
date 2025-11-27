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

test('authenticated user can create invoice with valid currency', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);
    $status = InvoiceStatus::where('name', 'draft')->first();

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'invoice_status_id' => $status->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'line_items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'hours',
                ],
            ],
        ])
        ->assertRedirect('/invoices');

    $this->assertDatabaseHas('invoices', [
        'user_id' => $user->id,
        'currency' => Currencies::NZD->value,
    ]);
});

test('authenticated user cannot create invoice with invalid currency', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);
    $status = InvoiceStatus::where('name', 'draft')->first();

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'invoice_status_id' => $status->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => 'INVALID',
            'line_items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'hours',
                ],
            ],
        ])
        ->assertSessionHasErrors(['currency']);
});

test('authenticated user can update invoice currency', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'currency' => Currencies::NZD->value,
    ]);

    actingAs($user)
        ->put("/invoices/{$invoice->id}", [
            'contact_id' => $invoice->contact_id,
            'recipient_name' => $invoice->recipient_name,
            'recipient_email' => $invoice->recipient_email,
            'amount' => 100,
            'invoice_status_id' => $invoice->invoice_status_id,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => Currencies::USD->value,
            'line_items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'hours',
                ],
            ],
        ])
        ->assertRedirect('/invoices');

    expect($invoice->fresh()->currency)->toBe(Currencies::USD->value);
});
