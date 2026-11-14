@extends('layouts.public')

@section('title', 'Start Your School — AxisPro School Management System')

@section('content')
<div class="auth-page">
    <div class="auth-card" style="max-width:760px;">
        <h2 style="margin:0;">Start Your School</h2>
        <p class="sub">Set up your own school management system in a minute. Choose your plan now — you get {{ $trialDays }} days free trial on that plan.</p>

        @if ($errors->any())
            <div class="msg error">
                ❌ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('school.signup.store') }}" class="auth-form active">
            @csrf

            <p class="form-section-title">Your School</p>
            <div class="form-group">
                <label>School Name</label>
                <input type="text" name="school_name" value="{{ old('school_name') }}" placeholder="e.g., St. Mary's Preparatory School" required>
            </div>
            <div class="form-group">
                <label>Choose Your Subdomain</label>
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="text" name="subdomain" value="{{ old('subdomain') }}" placeholder="stmarys" pattern="[a-z0-9]+(-[a-z0-9]+)*" required style="flex:1;">
                    <span style="color:var(--muted); font-size:13px; white-space:nowrap;">.{{ config('saas.base_domain') }}</span>
                </div>
            </div>

            <p class="form-section-title">Curriculum *</p>
            <div class="form-group">
                @foreach ($curricula as $curriculum)
                    <label style="display:flex; align-items:flex-start; gap:8px; font-weight:normal; margin-bottom:10px;">
                        <input type="checkbox" name="curricula[]" value="{{ $curriculum->id }}"
                            {{ in_array($curriculum->id, old('curricula', [])) ? 'checked' : '' }}
                            style="margin-top:3px;">
                        <span>
                            <strong>{{ $curriculum->name }}</strong>
                            @if ($curriculum->grade_naming_convention)
                                <br><span style="font-size:11px; color:var(--muted);">{{ $curriculum->grade_naming_convention }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- Variation A: Mandatory plan selection --}}
            <p class="form-section-title">Choose Your Plan * — {{ $trialDays }}-day free trial</p>
            <p style="font-size:12px; color:var(--muted); margin-top:-8px;">You must pick a subscription now. No payment today — you pay after trial ends.</p>
            <div class="form-group">
                <div class="plan-grid plan-grid--select">
                    @foreach ($plans as $key => $plan)
                        <label class="plan-option">
                            <input type="radio" name="plan" value="{{ $key }}" {{ old('plan', 'standard') === $key ? 'checked' : '' }} required>
                            <div class="plan-card {{ $key === 'standard' ? 'is-featured' : '' }}">
                                <span class="plan-radio"></span>
                                @if ($key === 'standard')
                                    <span class="plan-badge">Most popular</span>
                                @endif
                                <div class="plan-name">{{ $plan['name'] }}</div>
                                <div class="plan-price">
                                    <span class="plan-currency">GHS</span>
                                    <span class="plan-amount">{{ number_format($plan['amount_pesewas'] / 100) }}</span>
                                    <span class="plan-interval">/ term</span>
                                </div>
                                <p class="plan-desc">{{ $plan['description'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <p class="form-section-title">Your Admin Account</p>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="admin_name" value="{{ old('admin_name') }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="admin_username" value="{{ old('admin_username') }}" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="admin_password" minlength="8" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="admin_password_confirmation" minlength="8" required>
            </div>

            <button type="submit" class="btn-submit">Create My School on {{ $trialDays }}-day trial →</button>
            <p style="font-size:11px; color:var(--muted); text-align:center; margin-top:8px;">You selected plan will be charged after trial. Change anytime in billing.</p>
        </form>
    </div>
</div>
@endsection
