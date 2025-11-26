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
            'is_recurring',
            'recurring_frequency_id',
            'next_recurring_at',
            'invoice_number',
            'issue_date',
            'due_date',
            'created_at',
            'updated_at',
            'last_sent_at',
        ]);

        $newInvoice->is_recurring = false;
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

        $newInvoice->save();

        // 3. Update parent next recurring date
        $this->updateParentNextDate($parentInvoice);

        return $newInvoice;
    }

    protected function updateParentNextDate(Invoice $invoice): void
    {
        $frequency = $invoice->recurringFrequency;
        if (! $frequency) {
            return;
        }

        $timezone = $invoice->user->userSetting->timezone->value;

        // We use copy() to avoid modifying the original instance in place before update if it was passed by ref (objects are)
        // But here we want to update the DB.
        $currentDate = $invoice->next_recurring_at->copy()->setTimezone($timezone);

        $nextDate = match ($frequency->name) {
            RecurringFrequencies::WEEKLY->value => $currentDate->addWeek(),
            RecurringFrequencies::MONTHLY->value => $currentDate->addMonth(),
            RecurringFrequencies::QUARTERLY->value => $currentDate->addMonths(3),
            RecurringFrequencies::YEARLY->value => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };

        $invoice->update(['next_recurring_at' => $nextDate->setTimezone(Timezones::UTC->value)]);
    }
}
