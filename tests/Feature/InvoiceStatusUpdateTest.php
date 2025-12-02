<?php

use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(\Database\Seeders\InvoiceStatusSeeder::class);
    seed(\Database\Seeders\RecurringFrequencySeeder::class);
});

test('authenticated user can update invoice status', function () {
    $user = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $draftStatus = $statuses->firstWhere('name', 'draft');
    $paidStatus = $statuses->firstWhere('name', 'paid');

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_status_id' => $draftStatus->id,
    ]);

    actingAs($user)
        ->patch("/invoices/{$invoice->id}/status", [
            'invoice_status_id' => $paidStatus->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Invoice status updated successfully.');

    expect($invoice->fresh()->invoice_status_id)->toBe($paidStatus->id);
});

test('user cannot update another users invoice status', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $draftStatus = $statuses->firstWhere('name', 'draft');
    $paidStatus = $statuses->firstWhere('name', 'paid');

    $invoice = Invoice::factory()->create([
        'user_id' => $anotherUser->id,
        'invoice_status_id' => $draftStatus->id,
    ]);

    actingAs($user)
        ->patch("/invoices/{$invoice->id}/status", [
            'invoice_status_id' => $paidStatus->id,
        ])
        ->assertForbidden();

    expect($invoice->fresh()->invoice_status_id)->toBe($draftStatus->id);
});

test('guest cannot update invoice status', function () {
    $user = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $draftStatus = $statuses->firstWhere('name', 'draft');
    $paidStatus = $statuses->firstWhere('name', 'paid');

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_status_id' => $draftStatus->id,
    ]);

    $this->patch("/invoices/{$invoice->id}/status", [
        'invoice_status_id' => $paidStatus->id,
    ])
        ->assertRedirect('/login');

    expect($invoice->fresh()->invoice_status_id)->toBe($draftStatus->id);
});

test('status update requires valid status id', function () {
    $user = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $draftStatus = $statuses->firstWhere('name', 'draft');

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_status_id' => $draftStatus->id,
    ]);

    actingAs($user)
        ->patch("/invoices/{$invoice->id}/status", [
            'invoice_status_id' => 'invalid-id',
        ])
        ->assertSessionHasErrors('invoice_status_id');

    expect($invoice->fresh()->invoice_status_id)->toBe($draftStatus->id);
});

test('paid_at is set when status changes to paid', function () {
    $user = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $draftStatus = $statuses->firstWhere('name', 'draft');
    $paidStatus = $statuses->firstWhere('name', 'paid');

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_status_id' => $draftStatus->id,
        'paid_at' => null,
    ]);

    actingAs($user)
        ->patch("/invoices/{$invoice->id}/status", [
            'invoice_status_id' => $paidStatus->id,
        ])
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->invoice_status_id)->toBe($paidStatus->id);
    expect($invoice->paid_at)->not->toBeNull();
});

test('paid_at is cleared when status changes from paid to another status', function () {
    $user = User::factory()->create();
    $statuses = InvoiceStatus::orderBy('sort_order')->get();
    $paidStatus = $statuses->firstWhere('name', 'paid');
    $draftStatus = $statuses->firstWhere('name', 'draft');

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_status_id' => $paidStatus->id,
        'paid_at' => now(),
    ]);

    actingAs($user)
        ->patch("/invoices/{$invoice->id}/status", [
            'invoice_status_id' => $draftStatus->id,
        ])
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->invoice_status_id)->toBe($draftStatus->id);
    expect($invoice->paid_at)->toBeNull();
});
