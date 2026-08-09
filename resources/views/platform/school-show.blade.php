@extends('layouts.dashboard')

@section('title', $school->name . ' — Platform Admin')
@section('sidebar-sub', 'Platform Admin')
@section('page-label', $school->name)
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

    <div class="card card-padded" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
            <div>
                <h3 style="margin:0 0 4px 0;">{{ $school->name }}</h3>
                <p style="color:var(--muted); font-size:13px; margin:0;">{{ $school->subdomain }}.{{ config('saas.base_domain') }}</p>
                <p style="margin-top:8px;">
                    @if ($school->displayStatus() === 'active')
                        <span class="status-badge status-active">Active</span>
                    @elseif ($school->displayStatus() === 'trial')
                        <span class="status-badge status-pending">Trial</span>
                    @elseif ($school->displayStatus() === 'expired')
                        <span class="status-badge status-declined" title="Subscription period has ended - the school is currently locked out even though it wasn't manually suspended.">Expired</span>
                    @else
                        <span class="status-badge status-declined">Suspended</span>
                    @endif
                    @if ($school->plan)
                        <span style="margin-left:8px; color:var(--muted); font-size:13px;">Plan: {{ ucfirst($school->plan) }}</span>
                    @endif
                </p>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="{{ route('platform.billing.show', $school) }}" class="btn-gold" style="text-decoration:none; display:inline-flex; align-items:center;">💳 Billing</a>
                @if ($school->status === 'suspended')
                    <form method="POST" action="{{ route('platform.schools.reactivate', $school) }}">
                        @csrf
                        <button type="submit" class="btn-gold">Reactivate</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.schools.suspend', $school) }}" onsubmit="return confirm('Suspend {{ $school->name }}? Their staff/students will be locked out immediately.');">
                        @csrf
                        <button type="submit" class="auth-btn auth-btn-logout">Suspend</button>
                    </form>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('platform.schools.extend-trial', $school) }}" style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border); display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label>Extend trial by (days)</label>
                <input type="number" name="days" min="1" max="365" value="14" style="width:120px;" required>
            </div>
            <button type="submit" class="btn-gold" style="padding:10px 20px;">Extend Trial</button>
        </form>
    </div>

    <h4 style="margin-bottom:12px;">Recent Payments</h4>
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
@endsection
