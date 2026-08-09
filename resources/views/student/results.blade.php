@extends('layouts.dashboard')

@section('title', 'My Results')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'My Results')
@section('welcome-message', 'Marks & Feedback')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> My Timetable</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> My Assignments</a>
    <a href="{{ route('student.results') }}" class="active"><i class="nav-icon">📊</i> My Results</a>
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
    <div class="card card-padded">
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📊 Submitted Assignments</h4>

        @if ($submissions->isNotEmpty())
            <table>
                <thead>
                    <tr><th>Assignment</th><th>Submitted On</th><th>Marks</th><th>Feedback</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($submissions as $submission)
                        <tr>
                            <td><strong>{{ $submission->assignment->title }}</strong></td>
                            <td>{{ $submission->submitted_at?->format('M d, Y') }}</td>
                            <td>
                                @if ($submission->status === 'marked')
                                    <strong>{{ number_format($submission->marks, 2) }}</strong>
                                @else
                                    <span style="color:var(--muted);">—</span>
                                @endif
                            </td>
                            <td>{{ $submission->feedback ?: '—' }}</td>
                            <td>
                                <span class="status-badge status-{{ $submission->status === 'marked' ? 'admitted' : 'pending' }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:var(--muted);">You have not submitted any assignments yet.</p>
        @endif
    </div>
@endsection
