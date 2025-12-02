<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\WiseWebhookController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestWiseWebhook extends Command
{
    protected $signature = 'app:test-wise-webhook 
        {--amount=100 : Payment amount}
        {--currency=GBP : Currency code}
        {--reference= : Transfer reference (invoice number)}
        {--sender= : Sender name (for account-details-payment event)}
        {--event=balances#update : Event type (balances#update or account-details-payment#state-change)}
        {--skip-signature : Skip signature verification for local testing}';

    protected $description = 'Simulate a Wise webhook for local testing';

    public function handle(): int
    {
        $amount = (float) $this->option('amount');
        $currency = $this->option('currency');
        $reference = $this->option('reference');
        $senderName = $this->option('sender');
        $eventType = $this->option('event');

        // Build payload based on event type
        if ($eventType === 'account-details-payment#state-change') {
            $payload = $this->buildAccountDetailsPaymentPayload($amount, $currency, $senderName);
        } else {
            $payload = $this->buildBalanceUpdatePayload($amount, $currency, $reference);
        }

        $this->info('Simulating Wise webhook...');
        $this->table(['Field', 'Value'], [
            ['Amount', "{$currency} {$amount}"],
            ['Reference', $reference ?: '(none)'],
            ['Sender', $senderName ?: '(none)'],
            ['Event Type', $eventType],
        ]);

        // Create a fake request
        $request = Request::create(
            uri: '/webhooks/wise',
            method: 'POST',
            content: json_encode($payload)
        );
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Delivery-Id', 'test-'.uniqid());

        if ($this->option('skip-signature')) {
            $request->headers->set('X-Skip-Signature', 'true');
            $this->warn('Skipping signature verification (test mode)');
        }

        // Call the controller directly
        $controller = app(WiseWebhookController::class);
        $response = $controller->handle($request);

        $this->newLine();
        $this->info('Response:');
        $this->line($response->getContent());

        return 0;
    }

    protected function buildBalanceUpdatePayload(float $amount, string $currency, ?string $reference): array
    {
        return [
            'data' => [
                'resource' => [
                    'type' => 'balance-account',
                    'id' => 12345,
                    'profile_id' => 67890,
                ],
                'transaction_type' => 'credit',
                'amount' => $amount,
                'currency' => $currency,
                'balance_id' => 111,
                'channel_name' => 'TRANSFER',
                'transfer_reference' => $reference,
                'post_transaction_balance_amount' => $amount + 500,
                'step_id' => 9999999,
                'occurred_at' => now()->toIso8601String(),
            ],
            'subscription_id' => 'test-'.uniqid(),
            'event_type' => 'balances#update',
            'schema_version' => '3.0.0',
            'sent_at' => now()->toIso8601String(),
        ];
    }

    protected function buildAccountDetailsPaymentPayload(float $amount, string $currency, ?string $senderName): array
    {
        return [
            'data' => [
                'account_details_id' => '1',
                'target_account_id' => '12345',
                'resource' => [
                    'id' => 12345,
                    'profile_id' => 1,
                ],
                'transfer' => [
                    'id' => 'test-transfer-'.uniqid(),
                    'type' => 'credit',
                    'amount' => $amount,
                    'currency' => $currency,
                ],
                'sender' => [
                    'name' => $senderName ?? 'Test Sender',
                    'account_number' => '12345678',
                    'bank_code' => 'TESTBANK',
                    'address' => '123 Test Street, Test City',
                ],
                'current_status' => 'COMPLETED',
                'previous_status' => 'PROCESSING',
                'occurred_at' => now()->toIso8601String(),
            ],
            'subscription_id' => 'test-'.uniqid(),
            'event_type' => 'account-details-payment#state-change',
            'schema_version' => '2.0.0',
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
