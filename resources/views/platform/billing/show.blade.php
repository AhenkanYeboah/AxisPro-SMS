@extends('layouts.dashboard')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Billing — {{ $school->name }}</h1>
        <a href="{{ route('platform.schools.show', $school) }}" class="text-sm text-gray-600 hover:text-black">← Back to school</a>
    </div>

    @if(!$paystackReady)
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded mb-6">
            <p class="font-semibold text-yellow-800">Paystack not configured</p>
            <p class="text-sm text-yellow-700">Add PAYSTACK_SECRET_KEY in Render → Environment Variables.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- BASIC --}}
        <div class="bg-white rounded shadow p-6 border-t-4 border-gray-300">
            <h3 class="font-bold text-lg mb-2">Basic</h3>
            <p class="text-3xl font-bold mb-1">GHS 100<span class="text-sm font-normal">/mo</span></p>
            <p class="text-xs text-gray-500 mb-4">For small schools</p>
            <ul class="text-sm space-y-2 mb-6">
                <li>✓ Up to 200 students</li>
                <li>✓ Fee management (pesewas)</li>
                <li>✓ 5 teachers</li>
            </ul>
            <form method="POST" action="{{ route('platform.billing.checkout', $school) }}">
                @csrf
                <input type="hidden" name="plan" value="basic">
                <input type="hidden" name="amount" value="100">
                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded hover:bg-black disabled:opacity-50" {{ !$paystackReady ? 'disabled' : '' }}>Choose Basic</button>
            </form>
        </div>

        {{-- STANDARD - POPULAR --}}
        <div class="bg-white rounded shadow-lg p-6 border-t-4 border-black relative">
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-black text-white text-xs px-3 py-1 rounded-full">Most Popular</span>
            <h3 class="font-bold text-lg mb-2">Standard</h3>
            <p class="text-3xl font-bold mb-1">GHS 250<span class="text-sm font-normal">/mo</span></p>
            <p class="text-xs text-gray-500 mb-4">For growing schools</p>
            <ul class="text-sm space-y-2 mb-6">
                <li>✓ Up to 800 students</li>
                <li>✓ Payroll + Staff (SSNIT/PAYE)</li>
                <li>✓ Unlimited teachers</li>
                <li>✓ Exams + Report cards</li>
            </ul>
            <form method="POST" action="{{ route('platform.billing.checkout', $school) }}">
                @csrf
                <input type="hidden" name="plan" value="standard">
                <input type="hidden" name="amount" value="250">
                <button type="submit" class="w-full bg-black text-white py-2 rounded hover:bg-gray-800 disabled:opacity-50" {{ !$paystackReady ? 'disabled' : '' }}>Choose Standard</button>
            </form>
        </div>

        {{-- PREMIUM --}}
        <div class="bg-white rounded shadow p-6 border-t-4 border-yellow-500">
            <h3 class="font-bold text-lg mb-2">Premium</h3>
            <p class="text-3xl font-bold mb-1">GHS 500<span class="text-sm font-normal">/mo</span></p>
            <p class="text-xs text-gray-500 mb-4">For large schools</p>
            <ul class="text-sm space-y-2 mb-6">
                <li>✓ Unlimited students</li>
                <li>✓ Everything in Standard</li>
                <li>✓ Virtual classes</li>
                <li>✓ Priority support</li>
            </ul>
            <form method="POST" action="{{ route('platform.billing.checkout', $school) }}">
                @csrf
                <input type="hidden" name="plan" value="premium">
                <input type="hidden" name="amount" value="500">
                <button type="submit" class="w-full bg-yellow-600 text-white py-2 rounded hover:bg-yellow-700 disabled:opacity-50" {{ !$paystackReady ? 'disabled' : '' }}>Choose Premium</button>
            </form>
        </div>
    </div>

    <div class="mt-8 pt-6 border-t text-xs text-gray-500">
        <p>Callback: {{ route('platform.billing.callback', $school) }}</p>
        <p>Webhook: {{ route('paystack.webhook') }}</p>
    </div>
</div>
@endsection
