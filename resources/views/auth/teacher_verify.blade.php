@extends('layouts.public')

@section('title', 'Verify Your Account')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <h2>Verify Your Identity</h2>
        <p class="sub">Step 2 of 2 — enter the 6-digit code and your password to finish logging in.</p>

        @if (session('dev_code_preview'))
            <div class="msg" style="background:#EDE4FB; color:#3B1F6B;">
                🔧 Dev preview only — in production this code is emailed/SMS'd, not shown here:
                <strong>{{ session('dev_code_preview') }}</strong>
            </div>
        @endif

        @error('code')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror
        @error('password')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('teacher.verify.submit') }}">
            @csrf
            <div class="form-group">
                <label>Verification Code</label>
                <input type="text" name="code" maxlength="6" placeholder="6-digit code" required autofocus>
            </div>
            <div class="form-group">
                <label>Password (confirm again)</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Verify & Log In →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            Code expires 5 minutes after login. <a href="{{ route('teacher.login') }}">Start over</a>.
        </div>
    </div>
</div>
@endsection
