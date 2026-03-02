<?php

use App\Enums\Currencies;
use App\Mail\InvoiceMail;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(\Database\Seeders\InvoiceStatusSeeder::class);
    seed(\Database\Seeders\RecurringFrequencySeeder::class);
});

test('user can create invoice with file attachments', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $file1 = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');
    $file2 = UploadedFile::fake()->image('screenshot.jpg', 200, 200);

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'line_items' => [
                ['description' => 'Test Item', 'quantity' => 1, 'unit_price' => 100, 'unit_type' => 'hours'],
            ],
            'attachments' => [$file1, $file2],
        ])
        ->assertRedirect('/invoices');

    $invoice = Invoice::where('user_id', $user->id)->first();

    expect($invoice->attachments)->toHaveCount(2);

    $attachment = $invoice->attachments->firstWhere('original_name', 'contract.pdf');
    expect($attachment)
        ->original_name->toBe('contract.pdf')
        ->mime_type->toBe('application/pdf');

    Storage::disk('local')->assertExists($attachment->stored_path);
});

test('user can create invoice without attachments', function () {
    $user = \App\Models\User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'line_items' => [
                ['description' => 'Test Item', 'quantity' => 1, 'unit_price' => 100, 'unit_type' => 'hours'],
            ],
        ])
        ->assertRedirect('/invoices');

    $invoice = Invoice::where('user_id', $user->id)->first();
    expect($invoice->attachments)->toHaveCount(0);
});

test('attachments are limited to 10 files', function () {
    $user = \App\Models\User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $files = [];
    for ($i = 0; $i < 11; $i++) {
        $files[] = UploadedFile::fake()->create("file{$i}.pdf", 100, 'application/pdf');
    }

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'line_items' => [
                ['description' => 'Test Item', 'quantity' => 1, 'unit_price' => 100, 'unit_type' => 'hours'],
            ],
            'attachments' => $files,
        ])
        ->assertSessionHasErrors(['attachments']);
});

test('attachments reject invalid file types', function () {
    $user = \App\Models\User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

    actingAs($user)
        ->post('/invoices', [
            'contact_id' => $contact->id,
            'recipient_name' => $contact->name,
            'recipient_email' => $contact->email,
            'amount' => 100,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'currency' => Currencies::NZD->value,
            'line_items' => [
                ['description' => 'Test Item', 'quantity' => 1, 'unit_price' => 100, 'unit_type' => 'hours'],
            ],
            'attachments' => [$file],
        ])
        ->assertSessionHasErrors(['attachments.0']);
});

test('invoice mail includes file attachments', function () {
    Storage::fake('local');

    $invoice = Invoice::factory()->create();

    $path = 'invoices/'.$invoice->id.'/attachments/contract.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');

    InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'contract.pdf',
        'stored_path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    $invoice->load('attachments');

    $mailable = new InvoiceMail($invoice, 'Please find your invoice.', 'pdf-binary');

    // Verify the attachments method returns both the PDF and the file attachment
    $attachments = $mailable->attachments();
    expect($attachments)->toHaveCount(2);
});

test('attachments are deleted when invoice is deleted', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $path = 'invoices/'.$invoice->id.'/attachments/contract.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');

    InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'contract.pdf',
        'stored_path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    $invoice->delete();

    expect(InvoiceAttachment::where('invoice_id', $invoice->id)->count())->toBe(0);
});

test('edit view returns existing attachments', function () {
    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'contract.pdf',
        'stored_path' => 'invoices/'.$invoice->id.'/attachments/contract.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    actingAs($user)
        ->get("/invoices/{$invoice->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Edit')
            ->has('invoice.attachments', 1)
            ->where('invoice.attachments.0.original_name', 'contract.pdf')
        );
});

test('show view returns existing attachments', function () {
    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'report.xlsx',
        'stored_path' => 'invoices/'.$invoice->id.'/attachments/report.xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'size' => 2048,
    ]);

    actingAs($user)
        ->get("/invoices/{$invoice->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('invoices/Show')
            ->has('invoice.attachments', 1)
            ->where('invoice.attachments.0.original_name', 'report.xlsx')
        );
});

test('user can add attachments when updating invoice', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $file = UploadedFile::fake()->create('new-doc.pdf', 500, 'application/pdf');

    actingAs($user)
        ->put("/invoices/{$invoice->id}", [
            'recipient_name' => $invoice->recipient_name,
            'recipient_email' => $invoice->recipient_email,
            'amount' => 100,
            'invoice_status_id' => $invoice->invoice_status_id,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => $invoice->currency,
            'attachments' => [$file],
        ])
        ->assertRedirect('/invoices');

    expect($invoice->fresh()->attachments)->toHaveCount(1);
    expect($invoice->fresh()->attachments->first()->original_name)->toBe('new-doc.pdf');
});

test('user can remove attachments when updating invoice', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $path = 'invoices/'.$invoice->id.'/attachments/old-doc.pdf';
    Storage::disk('local')->put($path, 'fake content');

    $attachment = InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'old-doc.pdf',
        'stored_path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    actingAs($user)
        ->put("/invoices/{$invoice->id}", [
            'recipient_name' => $invoice->recipient_name,
            'recipient_email' => $invoice->recipient_email,
            'amount' => 100,
            'invoice_status_id' => $invoice->invoice_status_id,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => $invoice->currency,
            'remove_attachments' => [$attachment->id],
        ])
        ->assertRedirect('/invoices');

    expect($invoice->fresh()->attachments)->toHaveCount(0);
    Storage::disk('local')->assertMissing($path);
});

test('user can download an invoice attachment', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $invoice = Invoice::factory()->create(['user_id' => $user->id]);

    $path = 'invoices/'.$invoice->id.'/attachments/contract.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');

    $attachment = InvoiceAttachment::create([
        'invoice_id' => $invoice->id,
        'original_name' => 'contract.pdf',
        'stored_path' => $path,
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    actingAs($user)
        ->get("/invoices/{$invoice->id}/attachments/{$attachment->id}/download")
        ->assertSuccessful()
        ->assertDownload('contract.pdf');
});
