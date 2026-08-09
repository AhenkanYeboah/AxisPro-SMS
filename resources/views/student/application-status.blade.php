@extends('layouts.dashboard')

@section('title', 'Application Status')
@section('sidebar-sub', 'Admissions · 2025/2026')
@section('page-label', 'Application Status')
@section('welcome-message', $student->first_name.' '.$student->last_name)

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.application-status') }}" class="active"><i class="nav-icon">✎</i> Application Status</a>
    @if ($canTakeExam)
        <a href="{{ route('student.exam') }}"><i class="nav-icon">📝</i> Entrance Exam</a>
    @endif
@endsection

@section('topbar-right')
    <span class="user-greeting">🎓 <strong>{{ $student->student_id }}</strong></span>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    <div class="card card-padded" style="max-width:640px; margin:0 auto;">
        @if ($student->status === 'declined')
            <h4 style="font-size:16px; font-weight:700; color:#b91c1c; margin-bottom:10px;">Application Decision</h4>
            <p style="color:var(--muted);">Thank you for applying. After review, we're unable to offer admission at this time. If you believe this is an error, please contact the school office directly.</p>
        @else
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:10px;">📋 Your Application Is Under Review</h4>
            <p style="color:var(--muted); margin-bottom:16px;">
                Thanks for applying, {{ $student->first_name }} — your application (ID: <strong>{{ $student->student_id }}</strong>) has been received and is being reviewed by the school. You'll be notified once a decision is made, and full portal access (assignments, timetable, report card) opens automatically the moment you're admitted.
            </p>

            @if ($student->exam_date)
                <div style="border:1.5px solid var(--border); border-radius:8px; padding:14px; margin-top:12px;">
                    <strong>📅 Entrance Exam</strong>
                    <p style="color:var(--muted); font-size:13px; margin-top:6px;">
                        Scheduled for {{ $student->exam_date->format('F j, Y') }}.
                        @if ($canTakeExam)
                            It's ready for you to take online now.
                        @elseif ($student->exam_completed)
                            You've completed this — thank you.
                        @else
                            Please come prepared.
                        @endif
                    </p>
                    @if ($canTakeExam)
                        <a href="{{ route('student.exam') }}" class="auth-btn" style="display:inline-block; margin-top:8px;">Take Exam →</a>
                    @endif
                </div>
            @endif
        @endif
    </div>
@endsection
