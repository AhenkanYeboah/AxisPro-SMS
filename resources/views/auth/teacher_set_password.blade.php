@extends('layouts.public')

@section('title', 'Set Your Password')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <h2>Set Your Password</h2>
        <p class="sub">First time logging in? Enter your Teacher ID to create a password.</p>

        @error('teacher_id')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror
        @error('password')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('teacher.set-password.submit') }}">
            @csrf
            <div class="form-group">
                <label>Teacher ID</label>
                <input type="text" name="teacher_id" value="{{ old('teacher_id') }}" placeholder="e.g., ROCAT441841" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Choose a password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" placeholder="Repeat password" required minlength="6">
            </div>
            <button type="submit" class="btn-submit">Set Password & Log In →</button>
        </form>
    </div>
</div>
@endsection
