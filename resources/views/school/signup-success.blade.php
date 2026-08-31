@extends('layouts.public')

@section('title', 'Your School Is Ready — AxisPro School Management System')

@section('content')
<div class="auth-page">
    <div class="auth-card" style="max-width:480px; text-align:center;">
        <h2 style="margin:0;">🎉 {{ $school->name }} is ready!</h2>
        <p class="sub">Your school now has its own private instance on the <strong>{{ $plan['name'] ?? ucfirst($school->plan) }}</strong> plan trial.</p>

        <div style="background:#F1EBFB; border-radius:8px; padding:16px; margin:20px 0; word-break:break-all;">
            <a href="{{ $loginUrl }}" style="font-weight:600; color:#5B3A9E;">{{ $loginUrl }}</a>
        </div>

        <div style="text-align:left; background:#fafafa; border:1px solid #eee; border-radius:8px; padding:14px; font-size:13px;">
            <div><strong>Plan:</strong> {{ $plan['name'] ?? $school->plan }} — GHS {{ isset($plan) ? number_format($plan['amount_pesewas']/100, 2) : 'N/A' }} / {{ $plan['interval'] ?? 'term' }}</div>
            <div><strong>Status:</strong> Trial — {{ config('saas.trial_days') }} days free</div>
            <div><strong>Trial ends:</strong> {{ $school->trial_ends_at?->format('d M Y') }}</div>
            <div style="margin-top:8px; font-size:12px; color:var(--muted);">{{ $plan['description'] ?? '' }}</div>
        </div>

        <p style="font-size:13px; color:var(--muted); margin-top:16px;">
            Log in with the admin username and password you just created.
            No payment needed now — you'll be billed for <strong>{{ $plan['name'] ?? $school->plan }}</strong> after trial.
        </p>

        <a href="{{ $loginUrl }}" class="btn-submit" style="display:inline-block; text-decoration:none; margin-top:12px;">Go to My School →</a>
        
        <p style="font-size:11px; color:var(--muted); margin-top:12px;">You can upgrade/downgrade plan in Platform → Billing anytime.</p>
    </div>
</div>
@endsection
