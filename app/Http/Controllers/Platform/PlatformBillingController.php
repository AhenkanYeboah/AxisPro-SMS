<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class PlatformBillingController extends Controller
{
    public function show(School $school, PaystackService $paystack)
    {
        return view('platform.billing.show', [
            'school' => $school,
            'paystackReady' => $paystack->isConfigured(),
        ]);
    }

    public function checkout(Request $request, School $school, PaystackService $paystack)
    {
        $request->validate([
            'plan' => 'nullable|string',
            'amount' => 'nullable|numeric|min:1',
        ]);

        // Default amount if not provided - adjust to your pricing
        $amount = $request->input('amount', 100); // GHS 100 example
        $amountInKobo = (int) ($amount * 100); // Paystack uses kobo/pesewas

        try {
            $response = $paystack->initializeTransaction([
                'email' => $school->email ?? auth()->user()->email,
                'amount' => $amountInKobo,
                'callback_url' => route('platform.billing.callback', $school),
                'metadata' => [
                    'school_id' => $school->id,
                    'school_name' => $school->name,
                    'custom_fields' => [
                        ['display_name' => 'School', 'variable_name' => 'school', 'value' => $school->name]
                    ]
                ]
            ]);

            // FIX: Check if Paystack returned success and data
            if (!isset($response['status']) || $response['status'] !== true) {
                // Log full response for debugging
                \Log::error('Paystack init failed', ['response' => $response, 'school' => $school->id]);
                
                $message = $response['message'] ?? 'Paystack failed to initialize transaction. Check your PAYSTACK_SECRET_KEY and that amount/callback_url are valid.';
                
                // If it's an auth error, be explicit
                if (str_contains(strtolower(json_encode($response)), 'invalid') || str_contains(strtolower(json_encode($response)), 'unauthorized')) {
                    $message .= ' (Invalid API key? Use sk_test_... from Paystack dashboard)';
                }

                return back()->withErrors(['paystack' => $message])->withInput();
            }

            if (!isset($response['data']['authorization_url'])) {
                \Log::error('Paystack missing authorization_url', ['response' => $response]);
                return back()->withErrors(['paystack' => 'Paystack did not return payment URL. Response: ' . json_encode($response)])->withInput();
            }

            // Store reference if you have a transactions table
            // $school->update(['paystack_reference' => $response['data']['reference']]);

            return redirect($response['data']['authorization_url']);

        } catch (\Throwable $e) {
            \Log::error('Paystack checkout exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['paystack' => 'Payment error: ' . $e->getMessage()])->withInput();
        }
    }

    public function callback(Request $request, School $school, PaystackService $paystack)
    {
        $reference = $request->query('reference') ?? $request->input('reference');

        if (!$reference) {
            return redirect()->route('platform.billing.show', $school)
                ->withErrors(['paystack' => 'No transaction reference returned from Paystack']);
        }

        try {
            $response = $paystack->verifyTransaction($reference);

            if (($response['status'] ?? false) === true && ($response['data']['status'] ?? '') === 'success') {
                // Mark school as paid / extend subscription
                // Example: $school->update(['is_paid' => true, 'subscription_ends_at' => now()->addMonth()]);
                
                return redirect()->route('platform.schools.show', $school)
                    ->with('success', 'Payment successful! Reference: ' . $reference);
            }

            return redirect()->route('platform.billing.show', $school)
                ->withErrors(['paystack' => 'Payment verification failed: ' . ($response['message'] ?? 'Unknown error')]);

        } catch (\Throwable $e) {
            return redirect()->route('platform.billing.show', $school)
                ->withErrors(['paystack' => 'Verification error: ' . $e->getMessage()]);
        }
    }
}
