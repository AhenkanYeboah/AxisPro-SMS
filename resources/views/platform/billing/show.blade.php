@extends('layouts.dashboard')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Billing — {{ $school->name }}</h1>
        <a href="{{ route('platform.schools.show', $school) }}" class="text-sm text-gray-600 hover:text-black">← Back to school</a>
    </div>

    @if(!$paystackReady)
        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded mb-6">
            <p class="font-semibold text-yellow-800">Paystack not configured</p>
            <p class="text-sm text-yellow-700">Add PAYSTACK_SECRET_KEY in Render → Environment Variables. Use sk_test_... for testing.</p>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 p-4 rounded mb-6">
            <p class="font-semibold text-red-800">Error</p>
            <ul class="list-disc ml-5 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 p-4 rounded mb-6 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Subscription / Payment</h2>
        
        <div class="mb-4 text-sm text-gray-600">
            <p>School: <strong>{{ $school->name }}</strong> (ID: {{ $school->id }})</p>
            <p>Email: {{ $school->email ?? 'N/A' }}</p>
        </div>

        <form method="POST" action="{{ route('platform.billing.checkout', $school) }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium mb-1">Amount (GHS)</label>
                <input type="number" name="amount" value="{{ old('amount', 100) }}" min="1" step="1"
                    class="w-full border rounded px-3 py-2" placeholder="100">
                <p class="text-xs text-gray-500 mt-1">Will be charged in pesewas (×100) via Paystack</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Plan (optional)</label>
                <input type="text" name="plan" value="{{ old('plan', 'monthly') }}"
                    class="w-full border rounded px-3 py-2" placeholder="monthly">
            </div>

            <button type="submit" 
                class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 disabled:opacity-50"
                {{ !$paystackReady ? 'disabled' : '' }}>
                Pay with Paystack
            </button>
        </form>

    </div>
</div>
@endsection
