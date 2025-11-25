<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f9fafb;
        }
        .email-container {
            background: #ffffff;
            margin: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: #ffffff;
            padding: 30px 30px 20px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            height: 35px;
            width: auto;
        }
        .invoice-number {
            font-size: 24px;
            font-weight: 300;
            color: #6b7280;
            text-align: right;
        }
        .content {
            background: #fff;
            padding: 30px;
        }
        .greeting {
            font-size: 15px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .invoice-details {
            background: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 8px 0;
            font-size: 14px;
        }
        .invoice-details td:first-child {
            color: #6b7280;
            font-weight: 500;
        }
        .invoice-details td:last-child {
            text-align: right;
            color: #1f2937;
        }
        .amount {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .recurring-notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .recurring-notice strong {
            color: #92400e;
            font-size: 14px;
        }
        .recurring-notice p {
            color: #78350f;
            font-size: 13px;
            margin: 5px 0 0;
        }
        .message {
            font-size: 14px;
            color: #1f2937;
            line-height: 1.6;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 30px;
            padding: 20px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" class="logo">
            <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
        </div>
        
        <div class="content">
            <p class="greeting">Dear {{ $invoice->recipient_name }},</p>
            
            <p class="message">{{ $emailBody }}</p>

            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Invoice number:</td>
                        <td><strong>#{{ $invoice->invoice_number }}</strong></td>
                    </tr>
                    <tr>
                        <td>Issue date:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td>Due date:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('F j, Y') }}</td>
                    </tr>
                    @if($invoice->description)
                    <tr>
                        <td>Description:</td>
                        <td>{{ $invoice->description }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <div class="amount">
                {{ $invoice->currency }} ${{ number_format($invoice->amount, 2) }}
            </div>

            <p class="message">If you have any questions about this invoice, please contact {{ config('mail.from.address') }}.</p>
            
            <p class="message">Thank you!</p>
        </div>
    </div>
</body>
</html>
