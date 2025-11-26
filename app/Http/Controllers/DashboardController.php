<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatuses;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Metrics
        $paidInvoicesSum = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::PAID->value);
            })
            ->sum('amount');

        $outstandingInvoicesSum = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->whereIn('name', [InvoiceStatuses::SENT->value, InvoiceStatuses::OVERDUE->value]);
            })
            ->sum('amount');

        $totalSentCount = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->whereIn('name', [InvoiceStatuses::SENT->value, InvoiceStatuses::PAID->value, InvoiceStatuses::OVERDUE->value]);
            })
            ->count();

        // Recurring Invoices
        $recurringInvoices = Invoice::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', '!=', InvoiceStatuses::CANCELLED->value);
            })
            ->whereNotNull('next_recurring_date')
            ->with(['recurringFrequency'])
            ->orderBy('next_recurring_date')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'recipient_name' => $invoice->recipient_name,
                    'amount' => $invoice->amount, // Model casts this to float (dollars)
                    'next_recurring_date' => $invoice->next_recurring_date->format('d-m-Y'),
                    'frequency' => $invoice->recurringFrequency?->label,
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_revenue' => $paidInvoicesSum / 100,
                'outstanding_amount' => $outstandingInvoicesSum / 100,
                'total_sent_count' => $totalSentCount,
            ],
            'recurring_invoices' => $recurringInvoices,
        ]);
    }
}
