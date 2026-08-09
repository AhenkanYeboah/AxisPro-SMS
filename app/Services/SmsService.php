<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Arkesel's SMS REST API. Chosen over Hubtel for the
 * first cut per the expansion design doc - single API key, plain REST
 * endpoint, no merchant account setup. Mirrors PaystackService's shape
 * (dependency-free, uses Laravel's Http facade) for consistency.
 *
 * Unlike PaystackService, this does NOT throw on missing config - a school
 * without SMS configured should still be able to use email notices, so
 * send() degrades to a logged no-op rather than blocking the whole notice
 * pipeline. Callers (SendNoticeJob) check isConfigured() if they need to
 * know in advance whether SMS will actually go out.
 */
class SmsService
{
    private const BASE_URL = 'https://sms.arkesel.com/api/v2/sms/send';

    private ?string $apiKey;
    private ?string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.arkesel.api_key');
        $this->senderId = config('services.arkesel.sender_id');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->senderId);
    }

    /**
     * Send a single SMS. Returns false (and logs) rather than throwing on
     * any failure - a bad number or a down Arkesel API shouldn't crash a
     * whole batch of 30 notice sends; SendNoticeJob records the per-student
     * failure in notice_recipients and moves on.
     */
    public function send(string $phone, string $message): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('SmsService: ARKESEL_API_KEY/ARKESEL_SENDER_ID not set - SMS not sent.', [
                'phone' => $this->maskPhone($phone),
            ]);

            return false;
        }

        $normalized = $this->normalizeGhanaNumber($phone);

        if (! $normalized) {
            Log::warning('SmsService: could not normalize phone number, skipping.', [
                'phone' => $this->maskPhone($phone),
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders(['api-key' => $this->apiKey])
                ->post(self::BASE_URL, [
                    'sender' => $this->senderId,
                    'message' => $message,
                    'recipients' => [$normalized],
                ]);

            if (! $response->successful()) {
                Log::warning('SmsService: Arkesel send failed.', [
                    'phone' => $this->maskPhone($phone),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('SmsService: exception sending SMS.', [
                'phone' => $this->maskPhone($phone),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalizes common Ghana number formats to Arkesel's expected
     * 233XXXXXXXXX shape: strips spaces/dashes, converts a leading 0 to
     * 233, strips a leading +, and rejects anything that still doesn't
     * look like a valid Ghana mobile number afterward.
     */
    public function normalizeGhanaNumber(string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '233' . substr($digits, 1);
        }

        if (str_starts_with($digits, '233') && strlen($digits) === 12) {
            return $digits;
        }

        return null;
    }

    private function maskPhone(string $phone): string
    {
        return substr($phone, 0, 4) . str_repeat('*', max(0, strlen($phone) - 6)) . substr($phone, -2);
    }
}
