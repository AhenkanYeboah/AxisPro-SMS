@extends('layouts.dashboard')

@section('title', $school->name . ' — Billing — Platform Admin')
@section('sidebar-sub', 'Platform Admin')
@section('page-label', $school->name . ' · Billing')
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
    @if (session('success'))
        <div class="message success">✅ {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    <p style="margin-top:-8px; margin-bottom:20px;">
        <a href="{{ route('platform.schools.show', $school) }}" style="font-size:13px; color:var(--muted);">← Back to {{ $school->name }}</a>
    </p>

    <div class="card card-padded" style="margin-bottom:24px;">
        <h3 style="margin-top:0;">Subscription Status</h3>
        <p>
            @if ($school->displayStatus() === 'active')
                <span class="status-badge status-active">Active</span>
                — subscribed to the <strong>{{ $school->plan ? ucfirst($school->plan) : 'a' }}</strong> plan.
                @if ($school->subscription_ends_at)
                    Renews/expires {{ $school->subscription_ends_at->format('d M Y') }}.
                @endif
            @elseif ($school->displayStatus() === 'trial')
                <span class="status-badge status-pending">Trial</span>
                @if ($school->trial_ends_at)
                    — trial ends {{ $school->trial_ends_at->format('d M Y') }}.
                @else
                    — on an open-ended trial.
                @endif
            @elseif ($school->displayStatus() === 'expired')
                <span class="status-badge status-declined">Expired</span>
                — subscription period has ended.
            @else
                <span class="status-badge status-declined">Suspended</span>
            @endif
        </p>
    </div>

    <h3 style="margin-bottom:12px;">Start a Checkout on {{ $school->name }}'s Behalf</h3>
    <p style="font-size:13px; color:var(--muted); margin-top:-4px;">
        Use this to generate a Paystack payment link for this school — e.g. if you're
        walking them through payment on a call, or renewing on their behalf.
    </p>
    <div class="stats-grid">
        @foreach ($plans as $key => $plan)
            <div class="card card-padded" style="display:flex; flex-direction:column; gap:12px;">
                <div>
                    <h4 style="margin:0 0 4px 0;">{{ $plan['name'] }}</h4>
                    <p style="font-size:22px; font-weight:700; margin:8px 0;">
                        GHS {{ number_format($plan['amount_pesewas'] / 100, 2) }}
                        <span style="font-size:12px; font-weight:400; color:var(--muted);">/ {{ $plan['interval'] }}</span>
                    </p>
                    <p style="font-size:13px; color:var(--muted);">{{ $plan['description'] }}</p>
                </div>
                <form method="POST" action="{{ route('platform.billing.checkout', $school) }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $key }}">
                    <button type="submit" class="btn-gold" style="width:100%; padding:12px;">
                        {{ $school->plan === $key ? 'Renew this plan' : 'Start checkout' }} →
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <h4 style="margin:28px 0 12px 0;">Payment History</h4>
    <div class="card table-scroll" style="overflow:hidden; overflow-x:auto;">
        <table style="min-width:800px;">
            <thead>
                <tr>
                    <th>Reference</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($school->payments as $payment)
                    <tr>
                        <td style="font-size:12px; color:var(--muted);">{{ $payment->reference }}</td>
                        <td>{{ ucfirst($payment->plan) }}</td>
                        <td>GHS {{ number_format($payment->amount_pesewas / 100, 2) }}</td>
                        <td>
                            @if ($payment->status === 'success')
                                <span class="status-badge status-active">Success</span>
                            @elseif ($payment->status === 'pending')
                                <span class="status-badge status-pending">Pending</span>
                            @else
                                <span class="status-badge status-declined">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--muted);">{{ $payment->created_at->format('d M Y, g:ia') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; color:var(--muted); padding:24px;">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="font-size:12px; color:var(--muted); margin-top:20px;">
        Payments are processed securely by Paystack.
    </p>
@endsection
