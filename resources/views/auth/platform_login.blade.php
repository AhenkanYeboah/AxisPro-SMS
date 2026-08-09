@extends('layouts.public')

@section('title', 'Platform Admin — AxisPro School Management System')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro" style="height:40px; width:40px; object-fit:contain;">
            <h2 style="margin:0;">Platform Admin</h2>
        </div>
        <p class="sub">Manage every school on AxisPro School Management System.</p>

        @error('email')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('platform.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@axispro.example" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Log In →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            Looking for your school's admin login? Go to your school's own subdomain instead.
        </div>
    </div>
</div>
@endsection
