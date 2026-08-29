<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\School;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlatformBillingController extends Controller
{
    public function __construct(private PaystackService $paystack)
    {
    }

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

        $billingEmail = $school->contact_email ?: auth('platform')->user()->email;

        try {
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

            // FIX: Check Paystack response before accessing data
            if (!isset($result['status']) || $result['status'] !== true) {
                \Log::error('Paystack init failed', ['response' => $result, 'school' => $school->id]);
                $payment->update(['status' => 'failed', 'gateway_response' => $result]);
                return back()->with('error', $result['message'] ?? 'Paystack failed to initialize. Check your API key.');
            }

            if (!isset($result['data']['authorization_url'])) {
                \Log::error('Paystack missing auth url', ['response' => $result]);
                $payment->update(['status' => 'failed', 'gateway_response' => $result]);
                return back()->with('error', 'Paystack did not return payment URL: ' . json_encode($result));
            }

            return redirect()->away($result['data']['authorization_url']);

        } catch (\Throwable $e) {
            \Log::error('Paystack checkout exception', ['error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            return back()->with('error', 'Payment error: ' . $e->getMessage());
        }
    }

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

    private function isGenuineSuccess(array $verifiedData, Payment $payment): bool
    {
        return ($verifiedData['status'] ?? null) === 'success'
            && (int) ($verifiedData['amount'] ?? 0) === (int) $payment->amount_pesewas
            && ($verifiedData['currency'] ?? null) === $payment->currency;
    }

    public function markPaymentSuccessful(Payment $payment, array $gatewayData): void
    {
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
