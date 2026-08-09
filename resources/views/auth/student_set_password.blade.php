@extends('layouts.public')

@section('title', 'Set Your Password')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <h2>Set Your Password</h2>
        <p class="sub">Enter your Student ID (sent after enrollment) to create a password.</p>

        @error('student_id')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror
        @error('password')
            <div class="msg error">❌ {{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('student.set-password.submit') }}">
            @csrf
            <div class="form-group">
                <label>Student ID</label>
                <input type="text" name="student_id" value="{{ old('student_id', request('id')) }}" placeholder="e.g., ROCAS123456" required>
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
