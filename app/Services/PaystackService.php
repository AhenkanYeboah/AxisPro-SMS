<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Paystack's REST API. Deliberately dependency-free
 * (uses Laravel's built-in Http facade) rather than pulling in a package.
 */
class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    private string $secretKey;

    public function __construct()
    {
        $key = config('services.paystack.secret_key');

        if (empty($key)) {
            // Fail loudly and immediately rather than letting a request
            // silently hit Paystack with no auth and get a confusing 401
            // three layers deep in a controller.
            throw new RuntimeException(
                'PAYSTACK_SECRET_KEY is not set. Add it to your .env file '
                . '(PAYSTACK_SECRET_KEY=sk_test_...) - never commit it or '
                . 'hardcode it in config/services.php.'
            );
        }

        $this->secretKey = $key;
    }

    /**
     * Start a transaction. Returns Paystack's response array, which includes
     * `data.authorization_url` - redirect the school admin there to pay.
     */
    public function initializeTransaction(array $payload): array
    {
        $response = Http::withToken($this->secretKey)
            ->post(self::BASE_URL . '/transaction/initialize', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Paystack initialize failed: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Verify a transaction by its reference. Used both when the user is
     * redirected back from Paystack AND as a fallback if the webhook is
     * ever delayed or missed - never trust client-side redirect alone.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get(self::BASE_URL . "/transaction/verify/{$reference}");

        if (! $response->successful()) {
            throw new RuntimeException(
                'Paystack verify failed: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Verify that an incoming webhook payload actually came from Paystack.
     * Paystack signs the raw request body with your secret key using
     * HMAC-SHA512 and sends it in the x-paystack-signature header.
     * Without this check, anyone could POST a fake "payment successful"
     * webhook and get free access.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey);

        return hash_equals($expected, $signatureHeader);
    }
}
