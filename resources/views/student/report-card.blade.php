@extends('layouts.dashboard')

@section('title', 'Report Card')
@section('sidebar-sub', 'Student Dashboard')
@section('page-label', 'Report Card')
@section('welcome-message', $student->class ?: 'Not assigned')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}"><i class="nav-icon">⌂</i> Dashboard</a>
    <a href="{{ route('activities.index') }}"><i class="nav-icon">📋</i> Activities</a>
    <a href="{{ route('student.assignments') }}"><i class="nav-icon">📝</i> Assignments</a>
    <a href="{{ route('student.results') }}"><i class="nav-icon">📊</i> My Results</a>
    <a href="{{ route('student.timetable') }}"><i class="nav-icon">🗓</i> Timetable</a>
    <a href="{{ route('student.report-card') }}" class="active"><i class="nav-icon">📊</i> Report Card</a>
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
        <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📊 My Report Cards</h4>

        @if ($reportCards->isNotEmpty())
            <table>
                <thead>
                    <tr><th>Term</th><th>Uploaded</th><th>File</th></tr>
                </thead>
                <tbody>
                    @foreach ($reportCards as $reportCard)
                        <tr>
                            <td><strong>{{ $reportCard->term }}</strong></td>
                            <td>{{ $reportCard->uploaded_at?->format('M d, Y') }}</td>
                            <td><a href="{{ asset('storage/' . $reportCard->file_path) }}" target="_blank">📎 View / Download</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:var(--muted);">No report cards have been uploaded for you yet.</p>
        @endif
    </div>
@endsection
