<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatuses;
use App\Enums\RecurringFrequencies;
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

        // Average Invoice Value
        $avgInvoiceValue = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::PAID->value);
            })
            ->avg('amount');

        // Overdue Rate
        $sentCount = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::SENT->value);
            })
            ->count();

        $overdueCount = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::OVERDUE->value);
            })
            ->count();

        $totalActive = $sentCount + $overdueCount;
        $overdueRate = $totalActive > 0 ? ($overdueCount / $totalActive) : 0;

        // Draft Potential
        $draftPotential = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::DRAFT->value);
            })
            ->sum('amount');

        // MRR
        $recurringInvoicesAll = Invoice::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', '!=', InvoiceStatuses::CANCELLED->value);
            })
            ->with(['recurringFrequency'])
            ->get();

        $mrr = $recurringInvoicesAll->reduce(function ($carry, $invoice) {
            $amount = (int) $invoice->amount; // In cents, cast to native int

            return $carry + match ($invoice->recurringFrequency?->name) {
                RecurringFrequencies::WEEKLY->value => ($amount * 52) / 12,
                RecurringFrequencies::MONTHLY->value => $amount,
                RecurringFrequencies::QUARTERLY->value => ($amount * 4) / 12,
                RecurringFrequencies::YEARLY->value => $amount / 12,
                default => 0,
            };
        }, 0);

        // Status Distribution for Chart
        $statusDistribution = Invoice::where('user_id', $user->id)
            ->join('invoice_statuses', 'invoices.invoice_status_id', '=', 'invoice_statuses.id')
            ->selectRaw('invoice_statuses.name as status, count(*) as count')
            ->groupBy('invoice_statuses.name')
            ->pluck('count', 'status');

        // Revenue by Year
        $revenueByYear = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::PAID->value);
            })
            ->selectRaw('YEAR(issue_date) as year, sum(amount) as total')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'total' => $item->total / 100,
                ];
            });

        // Revenue by Contact (Top 5)
        $revenueByContact = Invoice::where('user_id', $user->id)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', InvoiceStatuses::PAID->value);
            })
            ->selectRaw('recipient_name, sum(amount) as total')
            ->groupBy('recipient_name')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->recipient_name,
                    'total' => $item->total / 100,
                ];
            });

        // Recurring Invoices
        $userTimezone = $user->userSetting->timezone->value;

        $recurringInvoices = Invoice::where('user_id', $user->id)
            ->where('is_recurring', true)
            ->whereHas('invoiceStatus', function ($query) {
                $query->where('name', '!=', InvoiceStatuses::CANCELLED->value);
            })
            ->whereNotNull('next_recurring_at')
            ->with(['recurringFrequency'])
            ->orderBy('next_recurring_at')
            ->take(5)
            ->get()
            ->map(function ($invoice) use ($userTimezone) {
                return [
                    'id' => $invoice->id,
                    'recipient_name' => $invoice->recipient_name,
                    'amount' => $invoice->amount, // Model casts this to float (dollars)
                    'next_recurring_date' => $invoice->next_recurring_at->setTimezone($userTimezone)->format('d-m-Y H:i'),
                    'frequency' => $invoice->recurringFrequency?->label,
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_revenue' => $paidInvoicesSum / 100,
                'outstanding_amount' => $outstandingInvoicesSum / 100,
                'total_sent_count' => $totalSentCount,
                'revenue_by_year' => $revenueByYear,
                'revenue_by_contact' => $revenueByContact,
                'avg_invoice_value' => $avgInvoiceValue / 100,
                'overdue_rate' => $overdueRate,
                'draft_potential' => $draftPotential / 100,
                'mrr' => $mrr,
                'status_distribution' => $statusDistribution,
            ],
            'recurring_invoices' => $recurringInvoices,
        ]);
    }
}
