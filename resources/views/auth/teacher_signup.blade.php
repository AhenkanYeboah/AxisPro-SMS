@extends('layouts.public')

@section('title', 'Teacher Sign Up')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
            @if($currentSchool->logo_path ?? false)
                <img src="{{ Storage::disk('public')->url($currentSchool->logo_path) }}" alt="{{ $currentSchool->name }}" style="height:40px; width:40px; object-fit:contain;" onerror="this.style.display='none'">
            @endif
            <h2 style="margin:0;">Teacher Sign Up</h2>
        </div>
        <p class="sub">Create a teacher account. You'll set your password next.</p>

        @if ($errors->any())
            <div class="msg error">❌ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('teacher.signup.submit') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g., Kwame Mensah" required>
            </div>
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Choose a username" required>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="teacher@example.com" required>
            </div>
            <div class="form-group">
                <label>Assigned Class *</label>
                @if ($classLevels->isEmpty())
                    <p class="msg error" style="font-size:12px;">No classes have been set up yet - ask your school admin to add classes first (Admin → Classes) before signing up.</p>
                @else
                    <select name="class_level_id" required>
                        <option value="">Select Class</option>
                        @foreach ($classLevels as $cl)
                            <option value="{{ $cl->id }}" {{ old('class_level_id') == $cl->id ? 'selected' : '' }}>{{ $cl->displayName() }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="form-group">
                <label>Profile Image</label>
                <input type="file" name="teacher_profile_image" accept="image/*">
                <p style="font-size:11px; color:var(--muted); margin-top:4px;">JPG, PNG, GIF, or WEBP. Max 2MB.</p>
            </div>
            <div class="form-group">
                <label>Invite Code *</label>
                <input type="text" name="invite_code" value="{{ old('invite_code') }}" placeholder="e.g., K7M2-QX9P" required>
                <p style="font-size:11px; color:var(--muted); margin-top:4px;">Ask your admin for a one-time invite code — each code works once.</p>
            </div>
            <button type="submit" class="btn-submit">Create Teacher Account →</button>
        </form>

        <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border); text-align:center; font-size:12px; color:var(--muted);">
            Already have an account? <a href="{{ route('teacher.login') }}">Log in</a>.
        </div>
    </div>
</div>
@endsection
