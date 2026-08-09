@extends('layouts.public')

@section('title', 'Student Login')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            @if($currentSchool->logo_path ?? false)
                <img src="{{ Storage::disk('public')->url($currentSchool->logo_path) }}" alt="{{ $currentSchool->name }}" style="height:40px; width:40px; object-fit:contain;" onerror="this.style.display='none'">
            @endif
            <h2 style="margin:0;">Student Login</h2>
        </div>
        <p class="sub">Log in to track your admission status.</p>

        @error('student_id')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('student.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" value="{{ old('student_id') }}" placeholder="e.g., ROCAS123456" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Log In →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            Not enrolled yet? <a href="{{ route('student.form') }}">Apply here</a>.
        </div>
    </div>
</div>
@endsection
