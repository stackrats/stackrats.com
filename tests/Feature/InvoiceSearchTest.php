<?php

use App\Models\Invoice;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(\Database\Seeders\InvoiceStatusSeeder::class);
    seed(\Database\Seeders\RecurringFrequencySeeder::class);
});

test('user can search invoices by description', function () {
    $user = User::factory()->create();

    Invoice::factory()->create([
        'user_id' => $user->id,
        'description' => 'Website Development Project',
        'invoice_number' => 'INV-001',
        'recipient_name' => 'John Doe',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'description' => 'Mobile App Design',
        'invoice_number' => 'INV-002',
        'recipient_name' => 'Jane Smith',
    ]);

    actingAs($user)
        ->get('/invoices?search=Website')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.description', 'Website Development Project')
        );

    actingAs($user)
        ->get('/invoices?search=Design')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.description', 'Mobile App Design')
        );
});

test('user can search invoices by invoice number', function () {
    $user = User::factory()->create();

    Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_number' => 'INV-12345',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'invoice_number' => 'INV-67890',
    ]);

    actingAs($user)
        ->get('/invoices?search=12345')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-12345')
        );
});

test('user can search invoices by recipient name', function () {
    $user = User::factory()->create();

    Invoice::factory()->create([
        'user_id' => $user->id,
        'recipient_name' => 'Acme Corp',
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'recipient_name' => 'Globex Corporation',
    ]);

    actingAs($user)
        ->get('/invoices?search=Acme')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.recipient_name', 'Acme Corp')
        );
});
