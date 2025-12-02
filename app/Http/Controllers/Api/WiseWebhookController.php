<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WiseWebhookController extends Controller
{
    /**
     * Wise Production Public Key for signature verification
     */
    private const WISE_PUBLIC_KEY = <<<'KEY'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvO8vXV+JksBzZAY6GhSO
XdoTCfhXaaiZ+qAbtaDBiu2AGkGVpmEygFmWP4Li9m5+Ni85BhVvZOodM9epgW3F
bA5Q1SexvAF1PPjX4JpMstak/QhAgl1qMSqEevL8cmUeTgcMuVWCJmlge9h7B1CS
D4rtlimGZozG39rUBDg6Qt2K+P4wBfLblL0k4C4YUdLnpGYEDIth+i8XsRpFlogx
CAFyH9+knYsDbR43UJ9shtc42Ybd40Afihj8KnYKXzchyQ42aC8aZ/h5hyZ28yVy
Oj3Vos0VdBIs/gAyJ/4yyQFCXYte64I7ssrlbGRaco4nKF3HmaNhxwyKyJafz19e
HwIDAQAB
-----END PUBLIC KEY-----
KEY;

    /**
     * Handle incoming Wise webhook
     */
    public function handle(Request $request): JsonResponse
    {
        info(['Wise Webhook Received', $request->all()]);
        // Verify signature (skip in local environment when header is present)
        $skipSignature = app()->environment('local') && $request->header('X-Skip-Signature') === 'true';

        if (! $skipSignature && ! $this->verifySignature($request)) {
            Log::warning('Wise webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Check if this is a test notification
        // if ($request->header('X-Test-Notification') === 'true') {
        //     Log::info('Wise test webhook received');

        //     return response()->json(['status' => 'ok']);
        // }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;

        // Log the full payload for debugging (remove in production)
        Log::info('Wise webhook received', [
            'event_type' => $eventType,
            'delivery_id' => $request->header('X-Delivery-Id'),
            'schema_version' => $payload['schema_version'] ?? null,
            'full_payload' => $payload,
        ]);

        return match ($eventType) {
            'balances#credit' => $this->handleBalanceCredit($payload),
            'balances#update' => $this->handleBalanceUpdate($payload),
            'account-details-payment#state-change' => $this->handleAccountDetailsPayment($payload),
            default => response()->json(['status' => 'logged', 'event_type' => $eventType]),
        };
    }

    /**
     * Handle balance credit event (money deposited)
     * Note: Personal Wise accounts only provide amount/currency - no sender info
     */
    protected function handleBalanceCredit(array $payload): JsonResponse
    {
        // Just log for now - no auto-matching
        return response()->json(['status' => 'logged', 'event_type' => 'balances#credit']);
    }

    /**
     * Handle balance update event (includes more details in v3.0.0)
     */
    protected function handleBalanceUpdate(array $payload): JsonResponse
    {
        // Just log for now - no auto-matching
        return response()->json(['status' => 'logged', 'event_type' => 'balances#update']);
    }

    /**
     * Handle account details payment event (includes sender info)
     */
    protected function handleAccountDetailsPayment(array $payload): JsonResponse
    {
        // Just log for now - no auto-matching
        return response()->json(['status' => 'logged', 'event_type' => 'account-details-payment#state-change']);
    }

    /**
     * Verify the webhook signature from Wise
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Signature-SHA256');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $decodedSignature = base64_decode($signature);

        $publicKey = openssl_pkey_get_public(self::WISE_PUBLIC_KEY);

        if (! $publicKey) {
            Log::error('Wise webhook: Failed to load public key');

            return false;
        }

        $result = openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}
