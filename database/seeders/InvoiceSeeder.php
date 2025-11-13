<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        // Get status and frequency IDs
        $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();
        $paidStatus = InvoiceStatus::where('name', InvoiceStatuses::PAID->value)->first();
        $draftStatus = InvoiceStatus::where('name', InvoiceStatuses::DRAFT->value)->first();
        $overdueStatus = InvoiceStatus::where('name', InvoiceStatuses::OVERDUE->value)->first();

        $monthlyFrequency = RecurringFrequency::where('name', RecurringFrequencies::MONTHLY->value)->first();
        $quarterlyFrequency = RecurringFrequency::where('name', RecurringFrequencies::QUARTERLY->value)->first();

        $this->command->info('Creating sample invoices for user: '.$user->email);

        $invoices = [
            [
                'recipient_name' => 'Acme Corporation',
                'recipient_email' => 'billing@acme.com',
                'recipient_address' => '123 Business St, Suite 100, New York, NY 10001',
                'amount' => 2500.00,
                'currency' => 'NZD',
                'description' => 'Web Development Services - October 2025',
                'line_items' => [
                    ['description' => 'Frontend Development', 'quantity' => 40, 'unit_price' => 50.00],
                    ['description' => 'Backend API Integration', 'quantity' => 20, 'unit_price' => 25.00],
                ],
                'invoice_status_id' => $sentStatus->id,
                'issue_date' => now()->subDays(15),
                'due_date' => now()->addDays(15),
                'is_recurring' => true,
                'recurring_frequency_id' => $monthlyFrequency->id,
                'next_recurring_date' => now()->addMonth(),
                'last_sent_at' => now()->subDays(15),
            ],
            [
                'recipient_name' => 'Tech Startup Inc.',
                'recipient_email' => 'accounts@techstartup.io',
                'recipient_address' => '456 Innovation Drive, San Francisco, CA 94102',
                'amount' => 1500.00,
                'currency' => 'NZD',
                'description' => 'Monthly Consulting Services',
                'line_items' => [
                    ['description' => 'Technical Consulting', 'quantity' => 20, 'unit_price' => 75.00],
                ],
                'invoice_status_id' => $paidStatus->id,
                'issue_date' => now()->subDays(30),
                'due_date' => now()->subDays(15),
                'is_recurring' => false,
                'last_sent_at' => now()->subDays(30),
            ],
            [
                'recipient_name' => 'Design Studio LLC',
                'recipient_email' => 'hello@designstudio.com',
                'recipient_address' => '789 Creative Ave, Austin, TX 78701',
                'amount' => 3200.00,
                'currency' => 'NZD',
                'description' => 'UI/UX Design Package',
                'line_items' => [
                    ['description' => 'UI Design', 'quantity' => 30, 'unit_price' => 80.00],
                    ['description' => 'UX Research', 'quantity' => 10, 'unit_price' => 80.00],
                ],
                'invoice_status_id' => $draftStatus->id,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'is_recurring' => false,
            ],
            [
                'recipient_name' => 'Global Enterprises',
                'recipient_email' => 'finance@globalent.com',
                'recipient_address' => '321 Corporate Plaza, Chicago, IL 60601',
                'amount' => 5000.00,
                'currency' => 'NZD',
                'description' => 'Enterprise Software License - Q4 2025',
                'line_items' => [
                    ['description' => 'Software License (10 users)', 'quantity' => 10, 'unit_price' => 400.00],
                    ['description' => 'Premium Support', 'quantity' => 1, 'unit_price' => 1000.00],
                ],
                'invoice_status_id' => $overdueStatus->id,
                'issue_date' => now()->subDays(45),
                'due_date' => now()->subDays(15),
                'is_recurring' => true,
                'recurring_frequency_id' => $quarterlyFrequency->id,
                'next_recurring_date' => now()->addMonths(3),
                'last_sent_at' => now()->subDays(45),
            ],
            [
                'recipient_name' => 'Small Business Co.',
                'recipient_email' => 'owner@smallbiz.com',
                'recipient_address' => '555 Main Street, Portland, OR 97201',
                'amount' => 750.00,
                'currency' => 'NZD',
                'description' => 'Website Maintenance - November 2025',
                'line_items' => [
                    ['description' => 'Monthly Maintenance', 'quantity' => 1, 'unit_price' => 500.00],
                    ['description' => 'Security Updates', 'quantity' => 1, 'unit_price' => 250.00],
                ],
                'invoice_status_id' => $sentStatus->id,
                'issue_date' => now()->subDays(5),
                'due_date' => now()->addDays(25),
                'is_recurring' => true,
                'recurring_frequency_id' => $monthlyFrequency->id,
                'next_recurring_date' => now()->addMonth(),
                'last_sent_at' => now()->subDays(5),
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $invoice = new Invoice($invoiceData);
            $invoice->user_id = $user->id;
            $invoice->invoice_number = $invoice->generateInvoiceNumber();
            $invoice->save();

            $this->command->info("Created invoice: {$invoice->invoice_number} - {$invoice->recipient_name}");
        }

        $this->command->info('Sample invoices created successfully!');
    }
}
