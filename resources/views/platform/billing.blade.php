@extends('layouts.dashboard')

@section('title', 'Billing — ' . $school->name)
@section('sidebar-sub', 'Platform Admin')
@section('page-label', 'Billing')
@section('welcome-message', 'Welcome, ' . auth('platform')->user()->name . ' 👋')

@section('nav-links')
    <a href="{{ route('platform.dashboard') }}"><i class="nav-icon">▤</i> Schools</a>
@endsection

@section('topbar-right')
    <form method="POST" action="{{ route('platform.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    @if (!$paystackReady)
        <div class="message info">
            ⚠️ Paystack is not configured. Add <strong>PAYSTACK_SECRET_KEY</strong> in Render → Environment Variables to enable payments.
        </div>
    @endif

    <div class="plan-header">
        <h3>Choose a package for {{ $school->name }}</h3>
        <p>
            <a href="{{ route('platform.schools.show', $school) }}" style="color:var(--green-mid); text-decoration:none;">← Back to school</a>
        </p>
    </div>

    <div class="plan-grid">
        @foreach ($plans as $key => $plan)
            @php
                $isFeatured = $key === 'standard';
                $isCurrent = $school->plan === $key;
            @endphp
            <div class="plan-card {{ $isFeatured ? 'is-featured' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                @if ($isCurrent)
                    <span class="plan-badge plan-badge--current">Current plan</span>
                @elseif ($isFeatured)
                    <span class="plan-badge">Most popular</span>
                @endif

                <div class="plan-name" style="padding-right:0;">{{ $plan['name'] }}</div>
                <div class="plan-price">
                    <span class="plan-currency">GHS</span>
                    <span class="plan-amount">{{ number_format($plan['amount_pesewas'] / 100) }}</span>
                    <span class="plan-interval">/ term</span>
                </div>
                <p class="plan-desc">{{ $plan['description'] }}</p>

                <ul class="plan-features">
                    @foreach ($plan['features'] ?? [] as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('platform.billing.checkout', $school) }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $key }}">
                    <button type="submit" class="plan-btn {{ $isFeatured || $isCurrent ? '' : 'plan-btn--outline' }}" {{ !$paystackReady ? 'disabled' : '' }}>
                        {{ $isCurrent ? 'Renew ' . $plan['name'] : 'Choose ' . $plan['name'] }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <p class="plan-note">Prices are per school term and billed in Ghana cedis (GHS) through Paystack.</p>
@endsection
