<?php

namespace App\Actions\Invoice;

use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
use App\Enums\Timezones;
use App\Models\Invoice;
use App\Models\InvoiceStatus;

class CreateRecurringInvoiceAction
{
    public function execute(Invoice $parentInvoice): Invoice
    {
        // 1. Calculate new dates
        $timezone = $parentInvoice->user->userSetting->timezone->value;
        $issueDate = $parentInvoice->next_recurring_at->setTimezone($timezone);

        // Calculate terms (diff between original issue and due)
        // If dates are null, default to 0 days or some standard
        $termsDays = 0;
        if ($parentInvoice->issue_date && $parentInvoice->due_date) {
            $termsDays = $parentInvoice->issue_date->diffInDays($parentInvoice->due_date);
        }

        $dueDate = $issueDate->copy()->addDays($termsDays);

        // 2. Create new invoice
        $newInvoice = $parentInvoice->replicate([
            'invoice_number',
            'issue_date',
            'due_date',
            'created_at',
            'updated_at',
            'last_sent_at',
        ]);

        $newInvoice->issue_date = $issueDate;
        $newInvoice->due_date = $dueDate;

        // We need to save it to get an ID? No, generateInvoiceNumber doesn't need ID.
        // But generateInvoiceNumber queries DB.
        $newInvoice->invoice_number = $newInvoice->generateInvoiceNumber();

        // Set status to SENT
        $sentStatus = InvoiceStatus::where('name', InvoiceStatuses::SENT->value)->first();
        if ($sentStatus) {
            $newInvoice->invoice_status_id = $sentStatus->id;
        }

        // Calculate next recurring date for the new invoice
        $newInvoice->next_recurring_at = $this->calculateNextRecurringDate($parentInvoice);
        $newInvoice->is_recurring = true;

        $newInvoice->save();

        // 3. Update parent to stop recurring
        $parentInvoice->update([
            'is_recurring' => false,
            'next_recurring_at' => null,
        ]);

        return $newInvoice;
    }

    protected function calculateNextRecurringDate(Invoice $invoice): ?\Illuminate\Support\Carbon
    {
        $frequency = $invoice->recurringFrequency;
        if (! $frequency) {
            return null;
        }

        $timezone = $invoice->user->userSetting->timezone->value;

        // We use copy() to avoid modifying the original instance in place
        $currentDate = $invoice->next_recurring_at->copy()->setTimezone($timezone);

        $nextDate = match ($frequency->name) {
            RecurringFrequencies::WEEKLY->value => $currentDate->addWeek(),
            RecurringFrequencies::MONTHLY->value => $currentDate->addMonth(),
            RecurringFrequencies::QUARTERLY->value => $currentDate->addMonths(3),
            RecurringFrequencies::YEARLY->value => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };

        return $nextDate->setTimezone(Timezones::UTC->value);
    }
}
