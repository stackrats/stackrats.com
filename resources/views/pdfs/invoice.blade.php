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
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #374151;
            line-height: 1.5;
            padding: 30px;
            background: #ffffff;
            font-size: 12px;
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
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }

        .logo-section img {
            height: 45px;
            width: auto;
        }

        .invoice-title-section {
            text-align: right;
        }

        .invoice-type {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
            line-height: 1;
        }

        .invoice-number {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .invoice-date {
            font-size: 13px;
            color: #6b7280;
        }

        /* Info Sections */
        .info-sections {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 30px;
        }

        .info-section {
            flex: 1;
        }

        .info-section h3 {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section p {
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .info-section .company-name {
            font-weight: 700;
            color: #111827;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .info-section .email {
            color: #4b5563;
        }

        /* Description Box */
        .description-box {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #f3f4f6;
        }

        .description-box p {
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
            margin: 0;
        }

        /* Line Items Table */
        .line-items {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        thead th {
            background: #333333;
            color: #ffffff;
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        thead th:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            text-align: right;
        }

        tbody td {
            padding: 16px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody td:last-child {
            text-align: right;
            font-weight: 600;
            color: #111827;
        }

        tbody td.description {
            font-weight: 500;
            color: #111827;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .totals {
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 13px;
        }

        .total-row.subtotal {
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
        }

        .total-row.gst {
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
        }

        .total-row.final-total {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #111827;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        /* Payment Terms Box */
        .payment-terms {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8fafc;
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
        }

        .payment-terms h4 {
            font-size: 11px;
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .payment-terms p {
            font-size: 13px;
            color: #374151;
            line-height: 1.6;
        }

        /* Footer Notes */
        .footer-notes {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
            text-align: center;
        }

        .footer-notes p {
            font-size: 12px;
            color: #9ca3af;
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
        </div>

        <!-- Description (if exists) -->
        @if($invoice->description)
        <div class="description-box">
            <p>
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
                        <th style="text-align: center;">
                            @php
                                $unitType = $invoice->line_items[0]['unit_type'] ?? 'quantity';
                                $unitTypeName = \App\Enums\InvoiceUnitTypes::tryFrom($unitType)?->label() ?? 'Quantity';
                            @endphp
                            {{ $unitTypeName }}
                        </th>
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
            <h4>Payment Instructions</h4>
            <p>
                <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') }}<br>
                <strong>Reference:</strong> {{ $invoice->invoice_number }}
            </p>
            @if(isset($paymentDetails['surcharge']))
            <p style="margin-top: 8px; font-size: 12px; color: #6b7280;">
                {{ $paymentDetails['surcharge'] }}
            </p>
            @endif
        </div>
        @endif

        <!-- Footer Notes -->
        <div class="footer-notes">
            <p>
                Thank you! If you have any questions about this invoice, 
                please contact {{ config('mail.from.address') }}.
            </p>
        </div>
    </div>
</body>
</html>
