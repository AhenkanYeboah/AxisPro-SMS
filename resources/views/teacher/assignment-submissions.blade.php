@extends('layouts.dashboard')

@section('title', 'Submissions — ' . $assignment->title)
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Submissions')
@section('welcome-message', $assignment->title)

@section('nav-links')
    <a href="{{ route('school.home') }}"><i class="nav-icon">⌂</i> Home</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('teacher.dashboard') }}"><i class="nav-icon">▤</i> Teacher Dashboard</a>
    <a href="{{ route('teacher.attendance') }}"><i class="nav-icon">📅</i> Attendance</a>
    <a href="{{ route('teacher.assignments') }}" class="active"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('teacher.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('teacher.report-cards') }}"><i class="nav-icon">📊</i> Report Cards</a>
    <a href="{{ route('teacher.research-assistant') }}"><i class="nav-icon">🔎</i> Research Assistant</a>
    <a href="{{ route('teacher.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
@endsection

@section('topbar-right')
    <a href="{{ route('teacher.assignments') }}" class="auth-btn">← Back to Assignments</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif

    <div class="card card-padded" style="margin-bottom:24px;">
        <strong style="font-size:16px; color:var(--green-deep);">{{ $assignment->title }}</strong>
        <p style="color:var(--muted); margin-top:4px;">Due: {{ $assignment->due_date->format('M d, Y') }} &middot; Class: {{ $assignment->class }}</p>
        @if ($assignment->description)
            <p style="margin-top:8px;">{{ $assignment->description }}</p>
        @endif
    </div>

    <div class="card card-padded">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">👥 Student Submissions</h4>

        @if ($submissions->isNotEmpty())
            <table>
                <thead>
                    <tr><th>Student</th><th>Submitted</th><th>File</th><th>Status</th><th>Marks / Feedback</th></tr>
                </thead>
                <tbody>
                    @foreach ($submissions as $submission)
                        <tr>
                            <td><strong>{{ $submission->student->fullName() }}</strong><br><span style="font-size:11px; color:var(--muted);">{{ $submission->student->student_id }}</span></td>
                            <td>{{ $submission->submitted_at?->format('M d, Y H:i') }}</td>
                            <td><a href="{{ asset('storage/' . $submission->submission_file) }}" target="_blank">📎 View</a></td>
                            <td>
                                @if ($submission->status === 'marked')
                                    <span class="status-badge status-active">Marked</span>
                                @else
                                    <span class="status-badge status-pending">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($submission->status === 'submitted')
                                    <form method="POST" action="{{ route('teacher.submissions.mark', $submission) }}" style="display:flex; gap:8px; align-items:center;">
                                        @csrf
                                        <input type="number" name="marks" step="0.01" min="0" max="100" placeholder="Marks" required style="width:80px; padding:6px 8px; border:1.5px solid var(--border); border-radius:6px;">
                                        <input type="text" name="feedback" placeholder="Feedback (optional)" style="width:180px; padding:6px 8px; border:1.5px solid var(--border); border-radius:6px;">
                                        <button type="submit" class="btn-filter">Save</button>
                                    </form>
                                @else
                                    <span style="color:var(--green-deep); font-weight:600;">✓ Marked ({{ $submission->marks }})</span>
                                    @if ($submission->feedback)
                                        <br><span style="font-size:12px; color:var(--muted);">{{ $submission->feedback }}</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:var(--muted);">No submissions yet.</p>
        @endif
    </div>
@endsection
