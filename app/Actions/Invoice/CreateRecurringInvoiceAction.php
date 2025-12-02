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
        $timezone = $parentInvoice->user->userSetting->timezone->value;

        // Calculate terms (diff between original issue and due)
        $termsDays = 0;
        if ($parentInvoice->issue_date && $parentInvoice->due_date) {
            $termsDays = $parentInvoice->issue_date->diffInDays($parentInvoice->due_date);
        }

        // Calculate the next recurring date first - this will be when the NEW invoice is due to be sent
        $nextRecurringAt = $this->calculateNextRecurringDate($parentInvoice);

        // The new invoice's issue date is based on its next_recurring_at (when it will be sent)
        $issueDate = $nextRecurringAt->copy()->setTimezone($timezone);
        $dueDate = $issueDate->copy()->addDays($termsDays);

        // Create new invoice
        $newInvoice = $parentInvoice->replicate([
            'invoice_number',
            'issue_date',
            'due_date',
            'created_at',
            'updated_at',
            'last_sent_at',
            'recurring_completed_at',
            'parent_invoice_id',
        ]);

        $newInvoice->issue_date = $issueDate;
        $newInvoice->due_date = $dueDate;
        $newInvoice->invoice_number = $newInvoice->generateInvoiceNumber();

        // Set new invoice to PENDING - it will be sent when its next_recurring_at date comes
        $pendingStatus = InvoiceStatus::where('name', InvoiceStatuses::PENDING->value)->first();
        if ($pendingStatus) {
            $newInvoice->invoice_status_id = $pendingStatus->id;
        }

        $newInvoice->next_recurring_at = $nextRecurringAt;
        $newInvoice->is_recurring = true;
        $newInvoice->parent_invoice_id = $parentInvoice->id;

        $newInvoice->save();

        // 3. Mark parent as completed (status will be updated by SendInvoiceEmail job on success)
        $parentInvoice->update([
            'recurring_completed_at' => now(),
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
