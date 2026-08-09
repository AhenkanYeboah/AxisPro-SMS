@extends('layouts.public')

@section('title', 'Start Your School — AxisPro School Management System')

@section('content')
<div class="auth-page">
    <div class="auth-card" style="max-width:460px;">
        <h2 style="margin:0;">Start Your School</h2>
        <p class="sub">Set up your own school management system in a minute. You'll get your own private admissions portal, admin dashboard, and applicant/student/teacher accounts — completely separate from every other school on the platform.</p>

        @if ($errors->any())
            <div class="msg error">
                ❌ {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('school.signup.store') }}">
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
                <p style="font-size:11px; color:var(--muted); margin-top:4px;">Lowercase letters, numbers, and hyphens only. This becomes your school's own web address.</p>
            </div>

            <p class="form-section-title">Curriculum</p>
            <p style="font-size:12px; color:var(--muted); margin-top:-8px;">Select every curriculum your school teaches. This shapes class names, subjects, and grading throughout the platform — you can activate more later in settings.</p>
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

            <p class="form-section-title">Your Admin Account</p>
            <p style="font-size:12px; color:var(--muted); margin-top:-8px;">You'll be this school's administrator — the only admin account created during signup.</p>
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

            <button type="submit" class="btn-submit">Create My School →</button>
        </form>
    </div>
</div>
@endsection
