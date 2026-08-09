@extends('layouts.public')

@section('title', 'Teacher Login')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            @if($currentSchool->logo_path ?? false)
                <img src="{{ Storage::disk('public')->url($currentSchool->logo_path) }}" alt="{{ $currentSchool->name }}" style="height:40px; width:40px; object-fit:contain;" onerror="this.style.display='none'">
            @endif
            <h2 style="margin:0;">Teacher Login</h2>
        </div>
        <p class="sub">Step 1 of 2 — enter your credentials to receive a verification code.</p>

        @error('username')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('teacher.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Username, Email, or Teacher ID</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Username, email, or ROCAT######" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Continue →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            First time here? <a href="{{ route('teacher.set-password') }}">Set your password</a>
            &nbsp;·&nbsp;
            New teacher? <a href="{{ route('teacher.signup') }}">Create an account</a>
            &nbsp;·&nbsp;
            Admin? <a href="{{ route('admin.login') }}">Log in here instead</a>.
        </div>
    </div>
</div>
@endsection
