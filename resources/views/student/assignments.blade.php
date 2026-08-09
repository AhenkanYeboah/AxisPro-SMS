@extends('layouts.dashboard')

@section('title', 'Assignments')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Assignments')
@section('welcome-message', $student->class ?: 'Not assigned')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}" class="active"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}"><i class="nav-icon">📊</i> Report Card</a>
    <a href="{{ route('student.fees') }}"><i class="nav-icon">💳</i> My Fees</a>
    <a href="{{ route('student.virtual-classes') }}"><i class="nav-icon">🎥</i> Virtual Classes</a>
    <form method="POST" action="{{ route('student.logout') }}" style="display:inline;">
        @csrf
        <a href="#" onclick="this.closest('form').submit(); return false;"><i class="nav-icon">🚪</i> Logout</a>
    </form>
@endsection

@section('topbar-right')
    <span class="user-greeting">Status: <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span></span>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    @if ($assignments->isNotEmpty())
        @foreach ($assignments as $assignment)
            @php $submission = $submissions->get($assignment->id); @endphp
            <div class="card card-padded" style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                    <div>
                        <strong style="font-size:16px; color:var(--green-deep);">{{ $assignment->title }}</strong>
                        <p style="color:var(--muted); font-size:13px; margin-top:2px;">
                            Due: {{ $assignment->due_date->format('M d, Y') }}
                            @if ($assignment->isPastDue() && !$submission) <span style="color:#991B1B;">(past due)</span> @endif
                        </p>
                    </div>
                    @if ($assignment->file_path)
                        <a href="{{ asset('storage/' . $assignment->file_path) }}" target="_blank" download class="btn-filter" style="text-decoration:none;">📥 Download Assignment</a>
                    @endif
                </div>

                @if ($assignment->description)
                    <p style="margin-top:12px;">{{ $assignment->description }}</p>
                @endif

                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border);">
                    @if (!$submission)
                        @if ($assignment->isPastDue())
                            <p style="color:#991B1B; font-size:13px;">The due date has passed. You can no longer submit this assignment.</p>
                        @else
                            <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                @csrf
                                <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" required style="padding:6px 0;">
                                <button type="submit" class="btn-filter">⬆ Submit Assignment</button>
                            </form>
                        @endif
                    @elseif ($submission->status === 'marked')
                        <div class="message success" style="margin-bottom:0;">
                            ✅ Marked — Score: <strong>{{ $submission->marks }}</strong>
                            @if ($submission->feedback)
                                <br><span style="font-weight:400;">Feedback: {{ $submission->feedback }}</span>
                            @endif
                        </div>
                        <p style="font-size:12px; color:var(--muted); margin-top:8px;">
                            Submitted {{ $submission->submitted_at?->format('M d, Y H:i') }} &middot;
                            <a href="{{ asset('storage/' . $submission->submission_file) }}" target="_blank">View your submission</a>
                        </p>
                    @else
                        <div class="message info" style="margin-bottom:0;">📤 Submitted — awaiting marks.</div>
                        <p style="font-size:12px; color:var(--muted); margin-top:8px;">
                            Submitted {{ $submission->submitted_at?->format('M d, Y H:i') }} &middot;
                            <a href="{{ asset('storage/' . $submission->submission_file) }}" target="_blank">View your submission</a>
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="card card-padded">
            <p style="color:var(--muted);">No assignments have been posted for your class yet.</p>
        </div>
    @endif
@endsection
