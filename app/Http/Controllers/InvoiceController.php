<?php

namespace App\Http\Controllers;

use App\Enums\Currencies;
use App\Enums\InvoiceStatuses;
use App\Enums\InvoiceUnitTypes;
use App\Enums\Timezones;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\RecurringFrequency;
use App\Shared\Traits\FormatDateTime;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesRequests, FormatDateTime;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $authId = Auth::id();
        $search = $request->input('search');

        $invoices = Invoice::where('user_id', $authId)
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->with(['invoiceStatus', 'recurringFrequency'])
            ->orderBy('created_at', 'desc')
            ->paginate(9)
            ->through(function ($invoice) {
                if ($invoice->next_recurring_at) {
                    $invoice->next_recurring_date = $this->parseUtcDateTimeAsLocal($invoice->next_recurring_at, $invoice->user, 'Y-m-d H:i:s');
                }
                if ($invoice->last_sent_at) {
                    $invoice->last_sent_at_formatted = $this->parseUtcDateTimeAsLocal($invoice->last_sent_at, $invoice->user, 'Y-m-d H:i:s');
                }

                return $invoice;
            });

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('invoices/Create', [
            'statuses' => InvoiceStatus::orderBy('sort_order')->get(),
            'frequencies' => RecurringFrequency::orderBy('sort_order')->get(),
            'unitTypes' => $this->getUnitTypes(),
            'currencies' => $this->getCurrencies(),
            'contacts' => Contact::where('user_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|exists:contacts,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_email' => 'required|email|max:255',
            'recipient_address' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'gst' => 'numeric|min:0',
            'currency' => ['required', 'string', 'size:3', Rule::enum(Currencies::class)],
            'description' => 'nullable|string',
            'line_items' => 'nullable|array',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'is_recurring' => 'boolean',
            'recurring_frequency_id' => 'nullable|exists:recurring_frequencies,id',
            'next_recurring_date' => 'nullable|date|after_or_equal:issue_date',
        ]);

        $draftStatus = InvoiceStatus::where('name', InvoiceStatuses::DRAFT->value)->first();

        $authId = Auth::id();

        if (isset($validated['next_recurring_date'])) {
            $validated['next_recurring_at'] = $this->parseLocalDateTimeAsUtc($validated['next_recurring_date']);
            unset($validated['next_recurring_date']);
        }

        $invoice = new Invoice($validated);
        $invoice->user_id = $authId;
        $invoice->invoice_number = $invoice->generateInvoiceNumber();
        $invoice->invoice_status_id = $draftStatus->id;

        if ($invoice->is_recurring && $invoice->recurring_frequency_id && empty($invoice->next_recurring_at)) {
            $frequency = RecurringFrequency::find($invoice->recurring_frequency_id);
            $invoice->next_recurring_at = $this->calculateNextRecurringDate(
                $invoice->issue_date,
                $frequency
            );
        }

        $invoice->save();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['invoiceStatus', 'recurringFrequency']);

        if ($invoice->next_recurring_at) {
            $invoice->next_recurring_date = $this->parseUtcDateTimeAsLocal($invoice->next_recurring_at, $invoice->user, 'Y-m-d H:i:s');
        }

        if ($invoice->last_sent_at) {
            $invoice->last_sent_at_formatted = $this->parseUtcDateTimeAsLocal($invoice->last_sent_at, $invoice->user, 'Y-m-d H:i:s');
        }

        return Inertia::render('invoices/Show', [
            'invoice' => $invoice,
            'statuses' => InvoiceStatus::orderBy('sort_order')->get(),
            'unitTypes' => $this->getUnitTypes(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load(['invoiceStatus', 'recurringFrequency']);

        if ($invoice->next_recurring_at) {
            $invoice->next_recurring_date = $this->parseUtcDateTimeAsLocal($invoice->next_recurring_at, $invoice->user, 'Y-m-d H:i:s');
        }

        if ($invoice->last_sent_at) {
            $invoice->last_sent_at_formatted = $this->parseUtcDateTimeAsLocal($invoice->last_sent_at, $invoice->user, 'Y-m-d H:i:s');
        }

        return Inertia::render('invoices/Edit', [
            'invoice' => $invoice,
            'statuses' => InvoiceStatus::orderBy('sort_order')->get(),
            'frequencies' => RecurringFrequency::orderBy('sort_order')->get(),
            'unitTypes' => $this->getUnitTypes(),
            'currencies' => $this->getCurrencies(),
            'contacts' => Contact::where('user_id', Auth::id())->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'contact_id' => 'nullable|exists:contacts,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_email' => 'required|email|max:255',
            'recipient_address' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'gst' => 'nullable|numeric|min:0',
            'currency' => ['required', 'string', 'size:3', Rule::enum(Currencies::class)],
            'description' => 'nullable|string',
            'line_items' => 'nullable|array',
            'invoice_status_id' => 'required|exists:invoice_statuses,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'is_recurring' => 'boolean',
            'recurring_frequency_id' => 'nullable|exists:recurring_frequencies,id',
            'next_recurring_date' => 'nullable|date|after_or_equal:issue_date',
        ]);

        if (isset($validated['next_recurring_date'])) {
            $validated['next_recurring_at'] = $this->parseLocalDateTimeAsUtc($validated['next_recurring_date']);
            unset($validated['next_recurring_date']);
        }

        $invoice->update($validated);

        if ($invoice->is_recurring && $invoice->recurring_frequency_id && empty($invoice->next_recurring_at)) {
            $frequency = RecurringFrequency::find($invoice->recurring_frequency_id);
            $invoice->next_recurring_at = $this->calculateNextRecurringDate(
                $invoice->issue_date,
                $frequency
            );
            $invoice->save();
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Update the status of the invoice
     */
    public function updateStatus(\App\Http\Requests\UpdateInvoiceStatusRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $newStatusId = $request->validated()['invoice_status_id'];
        $newStatus = InvoiceStatus::find($newStatusId);

        $updateData = [
            'invoice_status_id' => $newStatusId,
        ];

        // Set paid_at when status changes to paid, clear it otherwise
        if ($newStatus && $newStatus->name === InvoiceStatuses::PAID->value) {
            $updateData['paid_at'] = now();
        } else {
            $updateData['paid_at'] = null;
        }

        $invoice->update($updateData);

        return back()->with('success', 'Invoice status updated successfully.');
    }

    /**
     * Send invoice via email
     */
    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        \App\Jobs\SendInvoiceEmail::dispatch($invoice);

        return back()->with('success', 'Invoice is being sent...');
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $this->authorize('view', $invoice);

        $generatePdf = app(\App\Actions\Invoice\GenerateInvoicePdfBinaryAction::class);
        $pdfBinary = $generatePdf->handle($invoice);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->invoice_number.'.pdf"',
        ]);
    }

    /**
     * Preview invoice HTML template (for testing)
     */
    public function previewPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return view('pdfs.invoice', [
            'invoice' => $invoice,
            'paymentDetails' => [
                'name' => config('invoice.payment_full_name', config('app.name')),
                'account' => config('invoice.payment_account', 'Payment details available upon request'),
                'address' => config('invoice.payment_address', ''),
                'surcharge' => config('invoice.payment_surcharge', ''),
            ],
            'taxRate' => $this->getTaxRate($invoice),
        ]);
    }

    /**
     * Get tax rate for the invoice
     */
    private function getTaxRate(Invoice $invoice): float
    {
        $taxRates = [
            Currencies::NZD->value => 15.0,
            Currencies::AUD->value => 10.0,
            Currencies::GBP->value => 20.0,
            Currencies::EUR->value => 0.0,
            Currencies::USD->value => 0.0,
            Currencies::CAD->value => 0.0,
        ];

        return $taxRates[$invoice->currency] ?? 0.0;
    }

    /**
     * Calculate the next recurring date based on frequency
     */
    private function calculateNextRecurringDate($currentDate, ?RecurringFrequency $frequency)
    {
        if (! $frequency) {
            return null;
        }

        // Parse issue_date in user timezone
        $dateString = is_string($currentDate) ? $currentDate : $currentDate->format('Y-m-d');

        // Use trait to parse local date to UTC, but we need to do the calculation first?
        // The trait parses string to UTC.
        // Here we want to add time in local timezone then convert back.

        $timezone = Auth::user()->userSetting->timezone->value;
        $date = \Carbon\Carbon::parse($dateString, $timezone)->startOfDay();

        $nextDate = match ($frequency->name) {
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'yearly' => $date->addYear(),
            default => null,
        };

        return $nextDate ? $nextDate->setTimezone(Timezones::UTC->value) : null;
    }

    /**
     * Get available unit types from Enum
     */
    private function getUnitTypes()
    {
        return collect(InvoiceUnitTypes::cases())->map(fn ($type, $index) => [
            'id' => $type->value,
            'name' => $type->value,
            'label' => $type->label(),
            'sort_order' => $index + 1,
        ])->values();
    }

    /**
     * Get available currencies from Enum
     */
    private function getCurrencies()
    {
        return collect(Currencies::cases())->map(fn ($currency) => [
            'value' => $currency->value,
            'label' => $currency->label(),
            'symbol' => $currency->symbol(),
        ])->values();
    }
}
