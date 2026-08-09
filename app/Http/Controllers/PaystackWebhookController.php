<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Platform\PlatformBillingController;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives Paystack's server-to-server webhook for PLATFORM SUBSCRIPTION
 * payments (school -> RCA-SaaS). This - not the browser callback in
 * PlatformBillingController - is the actual source of truth for "did this
 * payment succeed", because it comes directly from Paystack's servers and
 * can't be spoofed by a user editing their browser URL.
 *
 * Note: this is separate from school-fee payments (school -> parent),
 * which in Phase 1 are recorded manually by admin, not via Paystack at all.
 *
 * CRITICAL: every request here MUST have its signature verified before any
 * of its contents are trusted. Skipping this check means anyone on the
 * internet can POST a fake "charge.success" event and get free access.
 */
class PaystackWebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private PlatformBillingController $billing,
    ) {
    }

    public function handle(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $rawBody = $request->getContent();

        if (! $this->paystack->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Paystack webhook rejected: invalid signature', [
                'ip' => $request->ip(),
            ]);

            // 401, not 200 - a real Paystack retry will still succeed once
            // properly signed; a forged request just gets rejected.
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success') {
            $this->handleChargeSuccess($data);
        }

        // Always 200 for anything else we don't act on yet (e.g. subscription
        // events) - Paystack will keep retrying non-200 responses, and an
        // event we don't handle isn't a failure on our end.
        return response('OK', Response::HTTP_OK);
    }

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (! $reference) {
            return;
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            Log::warning('Paystack webhook: no matching payment for reference', [
                'reference' => $reference,
            ]);
            return;
        }

        if ($payment->status === 'success') {
            return; // already processed, e.g. via the browser callback - avoid double-extending the subscription
        }

        $this->billing->markPaymentSuccessful($payment, $data);
    }
}
