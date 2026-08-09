<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\School;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Platform-admin-facing billing: THIS is subscription billing, i.e. a school
 * paying RCA-SaaS (you) for use of the platform. This is distinct from
 * school fees (a school's own students/parents paying the school), which
 * lives in FeeController/StudentFeeController under the 'admin' guard.
 *
 * Runs on the 'platform' guard and is scoped to a specific School via route
 * model binding, since a platform admin manages many schools - unlike the
 * old admin-guard version, there's no single "current" school here.
 *
 * The webhook (see PaystackWebhookController) is the actual source of truth
 * for "did this payment succeed" - this controller's verify() step is a
 * best-effort immediate confirmation, not the only place status changes.
 */
class PlatformBillingController extends Controller
{
    public function __construct(private PaystackService $paystack)
    {
    }

    /**
     * Show subscription status + plan picker for one school. Platform admin
     * uses this to review, or to generate/start a checkout on the school's
     * behalf (e.g. if a school pays offline and you're recording it, or
     * you're sending them a payment link).
     */
    public function show(School $school)
    {
        $school->load(['payments' => fn ($q) => $q->latest()->limit(20)]);

        return view('platform.billing', [
            'school' => $school,
            'plans' => config('saas.plans'),
        ]);
    }

    public function checkout(Request $request, School $school)
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:' . implode(',', array_keys(config('saas.plans'))),
        ]);

        $planKey = $validated['plan'];
        $plan = config("saas.plans.{$planKey}");

        $reference = 'AXP-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'school_id' => $school->id,
            'reference' => $reference,
            'plan' => $planKey,
            'amount_pesewas' => $plan['amount_pesewas'],
            'currency' => 'GHS',
            'status' => 'pending',
        ]);

        // Bill to the school's own contact email (platform admin is
        // initiating this on the school's behalf), falling back to the
        // logged-in platform admin's email if the school has none on file.
        $billingEmail = $school->contact_email ?: auth('platform')->user()->email;

        $result = $this->paystack->initializeTransaction([
            'email' => $billingEmail,
            'amount' => $plan['amount_pesewas'],
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => route('platform.billing.callback', $school),
            'metadata' => [
                'school_id' => $school->id,
                'plan' => $planKey,
                'payment_id' => $payment->id,
            ],
        ]);

        return redirect()->away($result['data']['authorization_url']);
    }

    /**
     * Paystack redirects the browser here after checkout. This is a
     * convenience confirmation - the webhook is what actually marks the
     * payment/subscription as paid, since browser redirects can be
     * skipped, interrupted, or spoofed by a user editing the URL.
     */
    public function callback(Request $request, School $school)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('platform.billing.show', $school)->with('error', 'Missing payment reference.');
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return redirect()->route('platform.billing.show', $school)->with('error', 'Payment not found.');
        }

        // Re-verify against Paystack directly rather than trusting the query
        // string alone - the webhook may not have arrived yet, but we can
        // still check status right now via the verify endpoint.
        $result = $this->paystack->verifyTransaction($reference);
        $isGenuineSuccess = $this->isGenuineSuccess($result['data'] ?? [], $payment);

        if ($isGenuineSuccess && $payment->status !== 'success') {
            $this->markPaymentSuccessful($payment, $result['data']);
        }

        return redirect()->route('platform.billing.show', $school)->with(
            $isGenuineSuccess ? 'success' : 'error',
            $isGenuineSuccess
                ? 'Payment confirmed - subscription is now active.'
                : 'Payment was not successful. Please try again.'
        );
    }

    /**
     * A transaction only counts as genuinely successful if Paystack both
     * says "success" AND confirms the amount/currency actually charged
     * match what we invoiced for this reference. Checking status alone
     * would trust Paystack's word on WHETHER something was paid, but not
     * HOW MUCH - this closes that gap.
     */
    private function isGenuineSuccess(array $verifiedData, Payment $payment): bool
    {
        return ($verifiedData['status'] ?? null) === 'success'
            && (int) ($verifiedData['amount'] ?? 0) === (int) $payment->amount_pesewas
            && ($verifiedData['currency'] ?? null) === $payment->currency;
    }

    public function markPaymentSuccessful(Payment $payment, array $gatewayData): void
    {
        // Belt-and-suspenders: the webhook and the callback both eventually
        // call this, and each independently re-checks amount/currency here
        // too (not just at their own call sites) so this method is safe to
        // call from anywhere, not just the two paths that currently do.
        if (! $this->isGenuineSuccess($gatewayData, $payment)) {
            $payment->update(['status' => 'failed', 'gateway_response' => $gatewayData]);

            return;
        }

        $payment->update([
            'status' => 'success',
            'paystack_transaction_id' => $gatewayData['id'] ?? null,
            'gateway_response' => $gatewayData,
        ]);

        $school = $payment->school;
        $plan = config("saas.plans.{$payment->plan}");

        // Extends from whichever is later: right now, or their existing
        // subscription_ends_at - so renewing before the current period
        // lapses adds on top of remaining time instead of discarding it.
        // The extension length comes from the plan config
        // (interval_months), not a hardcoded number, so adding a
        // differently-paced plan later can't silently mis-extend it here.
        $currentEnd = $school->subscription_ends_at && $school->subscription_ends_at->isFuture()
            ? $school->subscription_ends_at
            : now();

        $school->update([
            'status' => 'active',
            'plan' => $payment->plan,
            'subscription_ends_at' => $currentEnd->copy()->addMonths($plan['interval_months']),
        ]);
    }
}
