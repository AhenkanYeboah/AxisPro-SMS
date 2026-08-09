@extends('layouts.dashboard')

@section('title', 'Assignments')
@section('sidebar-sub', 'Teacher Dashboard')
@section('page-label', 'Assignments')
@section('welcome-message', 'Class: ' . ($teacher->assigned_class ?: 'Not assigned'))

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
    <span class="user-greeting">👤 <strong>{{ $teacher->teacher_id }}</strong></span>
    <form method="POST" action="{{ route('teacher.logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-btn auth-btn-logout">🚪 Logout</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="message success">✅ {{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="message error">❌ {{ session('error') }}</div>
    @endif

    @if ($noClassAssigned)
        <div class="card card-padded">
            <p style="color:var(--muted);">You don't have a class assigned. Please contact the admin.</p>
        </div>
    @else
        <div class="card card-padded" style="margin-bottom:24px;">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📝 Post Assignment — {{ $teacher->assigned_class }}</h4>

            <form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:end;">
                @csrf
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Title *</label>
                    <input type="text" name="title" placeholder="e.g., Mathematics Homework" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Description</label>
                    <textarea name="description" rows="3" placeholder="Assignment details..." style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px; font-family:inherit;"></textarea>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Due Date *</label>
                    <input type="date" name="due_date" required style="width:100%; padding:8px 12px; border:1.5px solid var(--border); border-radius:6px;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; text-transform:uppercase; color:var(--muted); display:block; margin-bottom:4px;">Attachment (optional)</label>
                    <input type="file" name="assignment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" style="width:100%; padding:6px 0;">
                </div>
                <div style="grid-column:1 / -1;">
                    <button type="submit" class="btn-filter">➕ Post Assignment</button>
                </div>
            </form>
        </div>

        <div class="card card-padded">
            <h4 style="font-size:16px; font-weight:700; color:var(--green-deep); margin-bottom:16px;">📋 Posted Assignments</h4>

            @if ($assignments->isNotEmpty())
                <table>
                    <thead>
                        <tr><th>Title</th><th>Due Date</th><th>Attachment</th><th>Submissions</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($assignments as $assignment)
                            <tr>
                                <td><strong>{{ $assignment->title }}</strong></td>
                                <td>{{ $assignment->due_date->format('M d, Y') }} @if($assignment->isPastDue()) <span style="color:#991B1B; font-size:11px;">(past due)</span> @endif</td>
                                <td>
                                    @if ($assignment->file_path)
                                        <a href="{{ asset('storage/' . $assignment->file_path) }}" target="_blank">📎 View</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('teacher.assignments.submissions', $assignment) }}">{{ $assignment->submissions_count }} submitted</a>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" style="display:inline;" onsubmit="return confirm('Delete this assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="del-btn">🗑 Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color:var(--muted);">No assignments posted yet.</p>
            @endif
        </div>
    @endif
@endsection
