<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            line-height: 1.4;
            padding: 20px 30px;
            background: #ffffff;
        }

        .invoice-container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo-section {
            flex: 1;
        }

        .logo-section img {
            height: 35px;
            width: auto;
        }

        .invoice-title-section {
            text-align: right;
        }

        .invoice-type {
            font-size: 26px;
            font-weight: 300;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .invoice-date {
            font-size: 10px;
            color: #9ca3af;
        }

        /* Info Sections */
        .info-sections {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .info-section {
            flex: 1;
        }

        .info-section h3 {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-section p {
            font-size: 11px;
            color: #1f2937;
            margin-bottom: 3px;
            line-height: 1.5;
        }

        .info-section .company-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .info-section .email {
            color: #3b82f6;
        }

        /* Line Items Table */
        .line-items {
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f3f4f6;
        }

        thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }

        thead th:last-child,
        tbody td:last-child {
            text-align: right;
        }

        tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }

        tbody td {
            padding: 10px 12px;
            font-size: 11px;
            color: #1f2937;
        }

        tbody td.description {
            font-weight: 500;
        }

        tbody td.amount {
            font-weight: 600;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
        }

        .totals {
            width: 280px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 11px;
        }

        .total-row.subtotal {
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-row.gst {
            color: #6b7280;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .total-row.final-total {
            padding-top: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .total-row .label {
            font-weight: 500;
        }

        .total-row .value {
            font-weight: 600;
        }

        /* Footer Notes */
        .footer-notes {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-notes h4 {
            font-size: 10px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .footer-notes p {
            font-size: 9px;
            color: #6b7280;
            line-height: 1.4;
        }

        /* Payment Terms Box */
        .payment-terms {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin-top: 15px;
            border-radius: 4px;
        }

        .payment-terms h4 {
            font-size: 10px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 5px;
        }

        .payment-terms p {
            font-size: 9px;
            color: #78350f;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="logo-section">
                <img src="{{ $logoUrl }}" alt="Stackrats Logo">
            </div>
            <div class="invoice-title-section">
                <div class="invoice-type">TAX INVOICE</div>
                <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
                <div class="invoice-date">
                    Date issued: {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y') }}
                </div>
            </div>
        </div>

        <!-- Info Sections -->
        <div class="info-sections">
            <div class="info-section">
                <h3>Billing to</h3>
                <p class="company-name">{{ $invoice->recipient_name }}</p>
                <p class="email">{{ $invoice->recipient_email }}</p>
                @if($invoice->recipient_address)
                    @foreach(explode("\n", $invoice->recipient_address) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                @endif
            </div>

            <div class="info-section">
                <h3>Payment to</h3>
                <p class="company-name">{{  $paymentDetails['name'] }}</p>
                <p>{{ $paymentDetails['account'] ?? 'Account details available upon request' }}</p>
                @if(isset($paymentDetails['address']))
                    @foreach(explode("\n", $paymentDetails['address']) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                @endif
            </div>

            <div class="info-section">
                <h3>Payment terms</h3>
                <p>Payment due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') }}</p>
                @if(isset($paymentDetails['surcharge']))
                <p>{{ $paymentDetails['surcharge'] }}</p>
                @endif
                @if($invoice->is_recurring)
                <p style="margin-top: 8px; color: #3b82f6; font-weight: 500;">
                    Recurring: {{ ucfirst($invoice->recurring_frequency) }}
                </p>
                @endif
            </div>
        </div>

        <!-- Description (if exists) -->
        @if($invoice->description)
        <div style="margin-bottom: 30px; padding: 16px; background: #f9fafb; border-radius: 8px;">
            <p style="font-size: 13px; color: #374151; line-height: 1.6;">
                <strong>Description:</strong> {{ $invoice->description }}
            </p>
        </div>
        @endif

        <!-- Line Items -->
        <div class="line-items">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">Months</th>
                        <th style="text-align: right;">Rate</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @if($invoice->line_items && count($invoice->line_items) > 0)
                        @foreach($invoice->line_items as $item)
                        <tr>
                            <td class="description">{{ $item['description'] }}</td>
                            <td style="text-align: center;">{{ $item['quantity'] }}</td>
                            <td style="text-align: right;">{{ $invoice->currency }} ${{ number_format($item['unit_price'], 2) }}</td>
                            <td class="amount" style="text-align: right;">
                                {{ number_format($item['quantity'] * $item['unit_price'], 2) }} {{ $invoice->currency }}
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="description">{{ $invoice->description ?: 'Service' }}</td>
                            <td style="text-align: center;">1</td>
                            <td style="text-align: right;">{{ $invoice->currency }} ${{ number_format($invoice->amount, 2) }}</td>
                            <td class="amount" style="text-align: right;">
                                {{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals">
                @php
                    $subtotal = 0;
                    if($invoice->line_items && count($invoice->line_items) > 0) {
                        foreach($invoice->line_items as $item) {
                            $subtotal += $item['quantity'] * $item['unit_price'];
                        }
                    }
                    $gstRate = $invoice->gst ?? 0;
                    $gstAmount = $subtotal * ($gstRate / 100);
                    $total = $subtotal + $gstAmount;
                @endphp

                @if($gstRate > 0)
                <div class="total-row subtotal">
                    <span class="label">Subtotal</span>
                    <span class="value">{{ number_format($subtotal, 2) }} {{ $invoice->currency }}</span>
                </div>
                <div class="total-row gst">
                    <span class="label">GST ({{ $gstRate }}%)</span>
                    <span class="value">{{ number_format($gstAmount, 2) }} {{ $invoice->currency }}</span>
                </div>
                <div class="total-row final-total">
                    <span class="label">Total</span>
                    <span class="value">{{ number_format($total, 2) }} {{ $invoice->currency }}</span>
                </div>
                @else
                <div class="total-row gst">
                    <span class="label">GST</span>
                    <span class="value">0.00 {{ $invoice->currency }}</span>
                </div>
                <div class="total-row final-total">
                    <span class="label">Total</span>
                    <span class="value">{{ number_format($total, 2) }} {{ $invoice->currency }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Terms Notice -->
        @if($invoice->invoiceStatus->name !== \App\Enums\InvoiceStatuses::PAID->value)
        <div class="payment-terms">
            <h4>Payment instructions</h4>
            <p>
                Payment is due by {{ \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') }}. 
                Please reference invoice number {{ $invoice->invoice_number }} in your payment.
            </p>
        </div>
        @endif

        <!-- Footer Notes -->
        <div class="footer-notes">
            <h4>Notes</h4>
            <p>
                Thank you! If you have any questions about this invoice, 
                please contact {{ config('mail.from.address') }}.
            </p>
        </div>
    </div>
</body>
</html>
