@extends('layouts.public')

@section('title', 'Admin / Teacher Access')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            @if($currentSchool->logo_path ?? false)
                <img src="{{ Storage::disk('public')->url($currentSchool->logo_path) }}" alt="{{ $currentSchool->name }}" style="height:40px; width:40px; object-fit:contain;" onerror="this.style.display='none'">
            @endif
            <h2 style="margin:0;">Admin Access</h2>
        </div>
        <p class="sub">Log in with your admin credentials.</p>

        @error('username')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Username or Admin ID</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Username or ROCAA######" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Log In →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            Are you a teacher? <a href="{{ route('teacher.login') }}">Log in here instead</a>.
        </div>
        <div style="margin-top:8px; text-align:center; font-size:12px; color:var(--muted);">
            Running a different school? <a href="{{ route('school.signup') }}">Start your own school here</a>.
        </div>
    </div>
</div>
@endsection
