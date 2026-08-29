<?php

namespace App\Services;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    protected ?string $secret;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        // FIX: Don't throw in constructor - allow page to load even if key missing
        // Only throw when actually trying to use Paystack
        $this->secret = config('services.paystack.secret') ?: env('PAYSTACK_SECRET_KEY');
    }

    protected function ensureConfigured(): void
    {
        if (empty($this->secret)) {
            throw new RuntimeException(
                'PAYSTACK_SECRET_KEY is not set. Add it to Render Env Vars: PAYSTACK_SECRET_KEY=sk_test_... or sk_live_...'
            );
        }
    }

    protected function headers(): array
    {
        $this->ensureConfigured();
        return [
            'Authorization' => 'Bearer ' . $this->secret,
            'Content-Type' => 'application/json',
        ];
    }

    public function initializeTransaction(array $data)
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/transaction/initialize', $data);

        return $response->json();
    }

    public function verifyTransaction(string $reference)
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/transaction/verify/' . $reference);

        return $response->json();
    }

    // Add other methods you have, they will use headers() which checks config lazily
    // Example: listTransactions, etc.

    public function isConfigured(): bool
    {
        return !empty($this->secret);
    }
}
